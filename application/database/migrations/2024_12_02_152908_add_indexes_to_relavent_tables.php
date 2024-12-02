<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TODO: Remove any hydra doom specific indexes from this in the future
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_data', function (Blueprint $table) {
            $table->index('event_id', 'ED_event_id_index');
            $table->index(['project_id', 'timestamp'], 'ED_project_id_timestamp_index');
        });

        Schema::table('project_accounts', function (Blueprint $table) {
            $table->index(['project_id', 'auth_provider'], 'PA_reference_project_id_auth_provider_index');
        });

        Schema::table('project_account_sessions', function (Blueprint $table) {
            $table->index('reference', 'PAS_reference_index');
        });

        Schema::table('project_account_session_events', function (Blueprint $table) {
            $table->index(['reference', 'project_id'], 'PASE_reference_project_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_data', function (Blueprint $table) {
            $table->dropIndex('ED_event_id_index');
            $table->dropIndex('ED_project_id_timestamp_index');
        });

        Schema::table('project_accounts', function (Blueprint $table) {
            $table->dropIndex('PA_reference_project_id_auth_provider_index');
        });

        Schema::table('project_account_sessions', function (Blueprint $table) {
            $table->dropIndex('PAS_reference_index');
        });

        Schema::table('project_account_session_events', function (Blueprint $table) {
            $table->dropIndex('PASE_reference_project_id_index');
        });
    }
};
