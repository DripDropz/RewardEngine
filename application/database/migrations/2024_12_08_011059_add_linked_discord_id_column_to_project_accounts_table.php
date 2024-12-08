<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_accounts', function (Blueprint $table) {
            $table->string('linked_discord_id', 1024)->after('linked_wallet_stake_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_accounts', function (Blueprint $table) {
            $table->dropColumn('linked_discord_id');
        });
    }
};
