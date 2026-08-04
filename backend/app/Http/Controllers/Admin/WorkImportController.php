<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubcategory;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk task import from CSV.
 *
 * Design decisions worth knowing before changing this:
 *
 * ALL OR NOTHING. The whole file is validated first, and nothing is written unless
 * every row passes. A half-imported batch is worse than a rejected one: you cannot
 * tell which rows landed without reading the database, and re-uploading the fixed
 * file then duplicates whatever did import.
 *
 * CATEGORY BY NAME, NOT ID. A spreadsheet prepared by a human will have "Starter",
 * not "3". Matching is case-insensitive on the category name; an unknown name is a
 * row error rather than a silent default, because a task in the wrong category
 * charges the wrong application fee and pays the wrong commission.
 *
 * NO PARTIAL DEFAULTS FOR MONEY. payout_usd is required per row. Defaulting it to
 * zero would import tasks that pay nothing, which is exactly the state that made
 * every existing task pay nothing when payout_usd was first added.
 *
 * Reads with SplFileObject rather than loading the file, so a large batch does not
 * sit in memory twice.
 */
class WorkImportController extends Controller
{
    /**
     * Columns the importer understands. Order in the file does not matter; the header
     * row is what maps them.
     */
    private const COLUMNS = [
        'title'                     => 'required',
        'description'               => 'required',
        'category'                  => 'required',
        'worker_slots'              => 'required',
        'payout_usd'                => 'required',
        'subcategory'               => 'optional',
        'avg_minutes'               => 'optional',
        'requires_kyc'              => 'optional',
        'display_application_boost' => 'optional',
        'work_status'               => 'optional',
        'expires_at'                => 'optional',
    ];

    private const MAX_ROWS = 500;

    public function form()
    {
        return view('admin.works.import', [
            'categories' => WorkCategory::orderBy('name')->get(),
            'columns'    => self::COLUMNS,
            'maxRows'    => self::MAX_ROWS,
        ]);
    }

    /**
     * A template with the header row and one worked example, so the first attempt
     * does not have to be a guess.
     */
    public function template(): StreamedResponse
    {
        $headers = array_keys(self::COLUMNS);

        $example = [
            'Tag 20 product images for an online store',
            'Open the supplied spreadsheet, review each image and add the three tags described in the brief. Full instructions are in the task package.',
            WorkCategory::orderBy('name')->value('name') ?: 'Starter',
            '25',
            '3.50',
            '',
            '15',
            '0',
            '0',
            '1',
            '',
        ];

        return response()->streamDownload(function () use ($headers, $example) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $example);
            fclose($out);
        }, 'task-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        [$rows, $readError] = $this->readCsv($request->file('file')->getRealPath());

        if ($readError) {
            return back()->with('error', $readError);
        }

        if ($rows === []) {
            return back()->with('error', 'That file has a header row but no data rows.');
        }

        // Validate everything before writing anything.
        [$prepared, $errors] = $this->validateRows($rows);

        if ($errors !== []) {
            return back()
                ->with('error', sprintf(
                    '%d row(s) have problems, so nothing was imported. Fix them and upload again.',
                    count($errors)
                ))
                ->with('import_errors', $errors);
        }

        DB::transaction(function () use ($prepared) {
            foreach ($prepared as $attributes) {
                Work::create($attributes);
            }
        });

        ActivityLogger::log('work.bulk_import', null, [
            'rows'     => count($prepared),
            'filename' => $request->file('file')->getClientOriginalName(),
        ]);

        return redirect()
            ->route('admin.works.index')
            ->with('success', sprintf('Imported %d task(s).', count($prepared)));
    }

    /**
     * @return array{0: array<int, array<string, string>>, 1: string|null}
     */
    private function readCsv(string $path): array
    {
        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD | \SplFileObject::DROP_NEW_LINE);

        $header = null;
        $rows   = [];

        foreach ($file as $line) {
            if ($line === false || $line === [null] || $line === ['']) {
                continue;
            }

            if ($header === null) {
                // Strip a UTF-8 BOM: Excel adds one, and it corrupts the first header
                // name so "title" arrives as "\xEF\xBB\xBFtitle" and never matches.
                $line[0] = preg_replace('/^\x{FEFF}/u', '', (string) $line[0]);
                $header  = array_map(fn ($h) => Str::snake(trim(strtolower((string) $h))), $line);

                $missing = array_diff(
                    array_keys(array_filter(self::COLUMNS, fn ($r) => $r === 'required')),
                    $header
                );

                if ($missing !== []) {
                    return [[], 'The file is missing required column(s): ' . implode(', ', $missing) . '.'];
                }

                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                return [[], sprintf('That file has more than %d rows. Split it into smaller batches.', self::MAX_ROWS)];
            }

            $row = [];
            foreach ($header as $i => $name) {
                $row[$name] = isset($line[$i]) ? trim((string) $line[$i]) : '';
            }

            // Skip rows that are entirely blank rather than reporting them as errors;
            // trailing empty lines are normal in spreadsheet exports.
            if (implode('', $row) === '') {
                continue;
            }

            $rows[] = $row;
        }

        if ($header === null) {
            return [[], 'That file appears to be empty.'];
        }

        return [$rows, null];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function validateRows(array $rows): array
    {
        $categories = WorkCategory::all()->keyBy(fn ($c) => strtolower($c->name));

        $prepared = [];
        $errors   = [];

        foreach ($rows as $index => $row) {
            // +2: one for the header, one because humans count from 1.
            $line = $index + 2;

            $category = $categories->get(strtolower($row['category'] ?? ''));

            $validator = Validator::make($row, [
                'title'                     => ['required', 'string', 'max:200'],
                'description'               => ['required', 'string', 'min:20'],
                'worker_slots'              => ['required', 'integer', 'min:1', 'max:10000'],
                'payout_usd'                => ['required', 'numeric', 'min:0', 'max:1000000'],
                'avg_minutes'               => ['nullable', 'integer', 'min:1'],
                'display_application_boost' => ['nullable', 'integer', 'min:0', 'max:100000'],
                'requires_kyc'              => ['nullable', 'in:0,1'],
                'work_status'               => ['nullable', 'in:0,1'],
                'expires_at'                => ['nullable', 'date'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Row {$line}: {$message}";
                }
            }

            if (! $category) {
                $errors[] = sprintf(
                    'Row %d: category "%s" does not exist. Create it first, or correct the spelling.',
                    $line,
                    $row['category'] ?? ''
                );
                continue;
            }

            $subcategoryId = null;
            if (($row['subcategory'] ?? '') !== '') {
                $subcategoryId = WorkSubcategory::where('category_id', $category->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($row['subcategory'])])
                    ->value('id');

                if (! $subcategoryId) {
                    $errors[] = sprintf(
                        'Row %d: subcategory "%s" does not exist under %s.',
                        $line,
                        $row['subcategory'],
                        $category->name
                    );
                }
            }

            if ($validator->fails()) {
                continue;
            }

            $slots = (int) $row['worker_slots'];

            $prepared[] = [
                'poster_id'                 => Auth::guard('admin')->id(),
                'poster_type'               => 1,
                'category_id'               => $category->id,
                'subcategory_id'            => $subcategoryId,
                'title'                     => $row['title'],
                'description'               => $row['description'],
                'worker_slots'              => $slots,
                'payout_usd'                => (float) $row['payout_usd'],
                // Vestigial for admin tasks; the fee comes from the category and the
                // reward is payout_usd. Kept at 0 so total_coins stays coherent.
                'coins_per_worker'          => 0,
                'total_coins'               => 0,
                'avg_minutes'               => ($row['avg_minutes'] ?? '') !== '' ? (int) $row['avg_minutes'] : null,
                'display_application_boost' => (int) ($row['display_application_boost'] ?? 0),
                'requires_kyc'              => (bool) ($row['requires_kyc'] ?? false),
                'work_status'               => isset($row['work_status']) && $row['work_status'] !== '' ? (int) $row['work_status'] : 1,
                'approval_status'           => 1,
                'expires_at'                => ($row['expires_at'] ?? '') !== '' ? $row['expires_at'] : null,
                'slug'                      => Str::slug($row['title']) . '-' . Str::random(6),
            ];
        }

        return [$prepared, $errors];
    }
}
