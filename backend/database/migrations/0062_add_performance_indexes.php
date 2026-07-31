<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the indexes the app actually queries on (foreign keys, status filters,
 * reference lookups, "latest by user" listings). The original schema shipped
 * almost none, so every wallet/ledger/works/contracts listing was a full scan.
 *
 * Safe + idempotent: each index is only created when the table and all of its
 * columns exist, and "already exists" errors are swallowed so the migration can
 * run on fresh installs and re-runs alike.
 */
return new class extends Migration
{
    /** table => list of column-sets to index */
    private array $map = [
        'ledger_entries'           => [['user_id', 'created_at'], ['category'], ['reference'], ['entry_type']],
        'coin_topups'              => [['user_id', 'status'], ['reference'], ['package_id'], ['channel_code'], ['status']],
        'cashouts'                 => [['user_id', 'status'], ['reference'], ['payout_method_id'], ['gateway_reference'], ['status']],
        'work_submissions'         => [['work_id', 'status'], ['worker_id'], ['status']],
        'works'                    => [['poster_id', 'poster_type'], ['category_id'], ['subcategory_id'], ['approval_status', 'work_status'], ['slug'], ['is_featured']],
        'job_listings'             => [['employer_id', 'status'], ['category_id'], ['slug'], ['status'], ['is_featured']],
        'job_applications'         => [['job_listing_id', 'status'], ['applicant_id'], ['status']],
        'contracts'                => [['employer_id', 'status'], ['worker_id', 'status'], ['reference']],
        'contract_milestones'      => [['contract_id', 'status']],
        'referral_earnings'        => [['earner_id'], ['referred_user_id']],
        'user_notifications'       => [['user_id', 'read_at']],
        'notification_logs'        => [['user_id']],
        'admin_notifications'      => [['user_id', 'read_status']],
        'ratings'                  => [['ratee_id'], ['rater_id'], ['ratable_id', 'ratable_type']],
        'job_bookmarks'            => [['user_id'], ['job_listing_id']],
        'work_bookmarks'           => [['user_id'], ['work_id']],
        'help_tickets'             => [['user_id', 'status'], ['ticket_number']],
        'help_messages'            => [['help_ticket_id']],
        'job_application_messages' => [['job_application_id']],
        'user_device_tokens'       => [['user_id'], ['token']],
        'user_payout_accounts'     => [['user_id']],
        'users'                    => [['referred_by'], ['status'], ['kyc_status'], ['firebase_uid'], ['account_type']],
    ];

    public function up(): void
    {
        foreach ($this->map as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $cols) {
                if (! $this->columnsExist($table, $cols)) {
                    continue;
                }

                $name = $this->indexName($table, $cols);

                try {
                    Schema::table($table, fn (Blueprint $t) => $t->index($cols, $name));
                } catch (\Throwable $e) {
                    // Index already exists (or the driver rejected a duplicate) — safe to skip.
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->map as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $cols) {
                $name = $this->indexName($table, $cols);

                try {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                } catch (\Throwable $e) {
                    //
                }
            }
        }
    }

    private function columnsExist(string $table, array $cols): bool
    {
        foreach ($cols as $col) {
            if (! Schema::hasColumn($table, $col)) {
                return false;
            }
        }

        return true;
    }

    private function indexName(string $table, array $cols): string
    {
        // Deterministic, < 64 chars for every table/column combination here.
        return 'idx_' . $table . '_' . implode('_', $cols);
    }
};
