<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * works.allow_multiple_submissions let one worker apply to the same task
     * repeatedly. That contradicts the unique index added in 0068, so it is
     * switched off everywhere.
     *
     * The column is deliberately NOT dropped. Forcing the values to 0 makes the
     * behaviour consistent while keeping the change reversible, and stops the
     * admin UI showing a toggle that no longer does anything.
     *
     * Run order matters: this must come AFTER 0068 so that if 0068 aborts on
     * duplicate rows, the flag has not already been silently flipped.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('works', 'allow_multiple_submissions')) {
            return;
        }

        $affected = DB::table('works')
            ->where('allow_multiple_submissions', 1)
            ->update(['allow_multiple_submissions' => 0]);

        if ($affected > 0) {
            \Illuminate\Support\Facades\Log::info(
                "0070: cleared allow_multiple_submissions on {$affected} work(s)."
            );
        }
    }

    public function down(): void
    {
        // No-op. We cannot know which tasks originally had the flag set, and
        // re-enabling it would immediately conflict with the 0068 unique index.
    }
};
