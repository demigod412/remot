<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Splits the single legacy `status` column into two independent axes:
     *
     *   application_status  did admin let this worker onto the task at all
     *   delivery_status     how is the actual work going
     *
     * The legacy `status` column is intentionally LEFT IN PLACE. WorkSubmission
     * keeps it in sync on every save so that query-level code which has not been
     * migrated yet (scopes, ProcessWorkTimers, reports) keeps working. See the
     * syncLegacyStatus() method on the model.
     */
    public function up(): void
    {
        Schema::table('work_submissions', function (Blueprint $table) {
            $table->tinyInteger('application_status')
                ->default(0)
                ->comment('0=applied, 1=approved_to_work, 2=rejected')
                ->after('status');

            $table->tinyInteger('delivery_status')
                ->default(0)
                ->comment('0=not_started, 1=submitted, 2=revision_requested, 3=approved, 4=rejected, 5=expired')
                ->after('application_status');

            $table->json('task_files')
                ->nullable()
                ->comment('Task package delivered by admin on application approval')
                ->after('delivery_status');

            $table->longText('task_instructions')->nullable()->after('task_files');
            $table->timestamp('task_delivered_at')->nullable()->after('task_instructions');

            $table->unsignedInteger('revision_count')->default(0)->after('task_delivered_at');

            // What the worker actually paid to apply, and the ledger reference of
            // that debit. Stored per submission rather than re-read from the
            // category, because the category price can change between the charge
            // and a later refund, and refunding the wrong amount moves real money.
            $table->decimal('fee_paid', 10, 2)->default(0)->after('revision_count');
            $table->string('fee_reference', 40)->nullable()->after('fee_paid');
        });

        // ---------------------------------------------------------------------
        // Backfill from the legacy status column
        //
        //   0 (applied)      -> application 0 (applied),          delivery 0 (not started)
        //   1 (under review) -> application 1 (approved to work), delivery 1 (submitted)
        //   2 (approved)     -> application 1 (approved to work), delivery 3 (approved)
        //   3 (rejected)     -> application 1 (approved to work), delivery 4 (rejected)
        // ---------------------------------------------------------------------
        $map = [
            0 => ['application_status' => 0, 'delivery_status' => 0],
            1 => ['application_status' => 1, 'delivery_status' => 1],
            2 => ['application_status' => 1, 'delivery_status' => 3],
            3 => ['application_status' => 1, 'delivery_status' => 4],
        ];

        foreach ($map as $legacy => $values) {
            DB::table('work_submissions')->where('status', $legacy)->update($values);
        }

        // ---------------------------------------------------------------------
        // Flag ambiguous legacy rows for manual review.
        //
        // A legacy status=3 row with no submitted_at cannot be distinguished
        // between "application was rejected before any work started" (should be
        // application_status=2) and "work was submitted then rejected" (which is
        // what the backfill above assumed). Admin has to eyeball these.
        // ---------------------------------------------------------------------
        $ambiguous = DB::table('work_submissions')
            ->where('status', 3)
            ->whereNull('submitted_at')
            ->pluck('id')
            ->all();

        if (! empty($ambiguous)) {
            $payload = [
                'migration'   => '0067_add_task_delivery_to_work_submissions_table',
                'flagged_at'  => now()->toDateTimeString(),
                'reason'      => 'Legacy status=3 with no submitted_at. Backfilled as '
                               . 'application_status=1 / delivery_status=4 (rejected after '
                               . 'submission). If the application was actually rejected before '
                               . 'work started, set application_status=2 / delivery_status=0.',
                'count'       => count($ambiguous),
                'submission_ids' => array_values($ambiguous),
            ];

            Log::warning('work_submissions rows need manual review after 0067 backfill', $payload);

            $dir = storage_path('app/migration-review');
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            file_put_contents(
                $dir . '/0067-ambiguous-submissions.json',
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }
    }

    public function down(): void
    {
        Schema::table('work_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'application_status',
                'delivery_status',
                'task_files',
                'task_instructions',
                'task_delivered_at',
                'revision_count',
                'fee_paid',
                'fee_reference',
            ]);
        });
    }
};
