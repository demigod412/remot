<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional per-category schema for the JSON result a worker submits.
     *
     * Nullable on purpose. With no schema set, a result only has to be valid,
     * non-empty JSON (the existing TaskResultRequest behaviour). Once a schema is
     * set, malformed results are rejected at upload time and never reach the admin
     * review queue.
     *
     * schema_strict controls whether keys not named in the schema are rejected.
     * Leave it off while you are still working out the format.
     */
    public function up(): void
    {
        Schema::table('work_categories', function (Blueprint $table) {
            $table->json('result_schema')
                ->nullable()
                ->comment('Expected shape of the worker JSON result. Null = any valid JSON.')
                ->after('description');

            $table->boolean('schema_strict')
                ->default(false)
                ->comment('Reject keys not declared in result_schema')
                ->after('result_schema');
        });
    }

    public function down(): void
    {
        Schema::table('work_categories', function (Blueprint $table) {
            $table->dropColumn(['result_schema', 'schema_strict']);
        });
    }
};
