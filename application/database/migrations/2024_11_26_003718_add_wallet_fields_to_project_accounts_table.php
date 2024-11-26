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
            $table->string('stake_key_address', 64)->index()->nullable();
            $table->string('auth_nonce', 36)->nullable();
            $table->dateTime('auth_issued')->nullable();
            $table->dateTime('auth_expiration')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_accounts', function (Blueprint $table) {
            $table->dropColumn('stake_key_address');
            $table->dropColumn('auth_nonce');
            $table->dropColumn('auth_issued');
            $table->dropColumn('auth_expiration');
        });
    }
};
