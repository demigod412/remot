<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkCategory;
use App\Models\WorkSubcategory;
use App\Services\ResultSchemaValidator;
use Illuminate\Http\Request;

class WorkCategoryController extends Controller
{
    public function index()
    {
        $categories = WorkCategory::withCount(['works', 'subcategories'])->orderBy('name')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        [$schema, $error] = $this->parseSchema($request->input('result_schema'));
        if ($error) {
            return back()->withInput()->withErrors(['result_schema' => $error]);
        }
        $data['result_schema'] = $schema;
        $data['schema_strict'] = $request->boolean('schema_strict');
        // NOT NULL with a default of 0, so an omitted field must be 0, not null.
        $data['daily_application_limit'] = (int) ($data['daily_application_limit'] ?? 0);
        $data['min_approval_rate']       = (int) ($data['min_approval_rate'] ?? 40);
        $data['max_approval_rate']       = (int) ($data['max_approval_rate'] ?? 70);
        $data['batch_approval_enabled']  = $request->boolean('batch_approval_enabled');
        $data['status']        = 1;

        WorkCategory::create($data);
        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, int $id)
    {
        $cat  = WorkCategory::findOrFail($id);
        $data = $request->validate($this->rules($id));

        [$schema, $error] = $this->parseSchema($request->input('result_schema'));
        if ($error) {
            return back()->withInput()->withErrors(['result_schema' => $error]);
        }
        $data['result_schema'] = $schema;
        $data['schema_strict'] = $request->boolean('schema_strict');
        // NOT NULL with a default of 0, so an omitted field must be 0, not null.
        $data['daily_application_limit'] = (int) ($data['daily_application_limit'] ?? 0);

        $cat->update($data);
        return back()->with('success', 'Category updated.');
    }

    /**
     * Commission, application cost and eligibility are set per category and
     * inherited by every task in it. They are deliberately not overridable on the
     * individual task form, so pricing stays consistent within a category.
     */
    protected function rules(?int $id = null): array
    {
        $unique = $id ? "unique:work_categories,name,{$id}" : 'unique:work_categories,name';

        return [
            'name'               => ['required', 'string', 'max:100', $unique],
            'icon'               => ['nullable', 'string', 'max:50'],
            'description'        => ['nullable', 'string', 'max:2000'],
            // Platform cut on the worker payout.
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            // Coins a worker spends to apply to any task in this category.
            'application_cost'   => ['required', 'numeric', 'min:0', 'max:99999999'],
            // 0 = unlimited, so existing categories are unaffected.
            'daily_application_limit' => ['nullable', 'integer', 'min:0', 'max:1000'],
            // Percentages. max must not sit below min or the random draw has no range.
            'min_approval_rate'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_approval_rate'       => ['nullable', 'integer', 'min:0', 'max:100', 'gte:min_approval_rate'],
            // 0 = both, 1 = individuals only, 2 = businesses only.
            'eligible_user_type' => ['required', 'integer', 'in:0,1,2'],
        ];
    }

    /**
     * Turn the textarea contents into a stored schema.
     *
     * Blank clears it, which switches this category back to "any valid JSON".
     * A malformed schema is refused rather than saved, because a broken schema
     * would silently reject every submission from then on.
     *
     * @return array{0: ?array, 1: ?string}  [schema, error message]
     */
    protected function parseSchema(?string $raw): array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return [null, null];
        }

        $decoded = json_decode($raw, true, 32);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [null, 'The schema is not valid JSON: ' . json_last_error_msg() . '.'];
        }

        if (! is_array($decoded) || $decoded === []) {
            return [null, 'The schema must be a JSON object.'];
        }

        $problems = app(ResultSchemaValidator::class)->validateSchema($decoded);

        if ($problems !== []) {
            return [null, 'Schema problems: ' . implode(' ', array_slice($problems, 0, 5))];
        }

        return [$decoded, null];
    }

    public function destroy(int $id)
    {
        $cat = WorkCategory::findOrFail($id);
        if ($cat->works()->count()) {
            return back()->with('error', 'Cannot delete category with existing works.');
        }
        $cat->subcategories()->delete();
        $cat->delete();
        return back()->with('success', 'Category deleted.');
    }

    public function toggleStatus(int $id)
    {
        $cat = WorkCategory::findOrFail($id);
        $cat->status = $cat->status ? 0 : 1;
        $cat->save();
        return back()->with('success', 'Category status updated.');
    }

    // -------------------------------------------------------------------------
    // Subcategories (JSON endpoint used by create/edit work forms)
    // -------------------------------------------------------------------------

    public function subcategories(int $id)
    {
        $subs = WorkSubcategory::where('category_id', $id)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($subs);
    }

    public function storeSubcategory(Request $request, int $id)
    {
        WorkCategory::findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);
        $data['category_id'] = $id;
        $data['status']      = 1;
        WorkSubcategory::create($data);
        return back()->with('success', 'Subcategory added.');
    }

    public function updateSubcategory(Request $request, int $sid)
    {
        $sub  = WorkSubcategory::findOrFail($sid);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);
        $sub->update($data);
        return back()->with('success', 'Subcategory updated.');
    }

    public function destroySubcategory(int $sid)
    {
        $sub = WorkSubcategory::findOrFail($sid);
        if ($sub->works()->count()) {
            return back()->with('error', 'Cannot delete subcategory with existing works.');
        }
        $sub->delete();
        return back()->with('success', 'Subcategory deleted.');
    }
}
