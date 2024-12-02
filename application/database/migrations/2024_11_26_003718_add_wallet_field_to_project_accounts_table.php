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
            $table->string('auth_wallet', 64)->after('auth_provider_id')->nullable();
            $table->string('generated_wallet_mnemonic', 1024)->after('auth_avatar')->nullable();
            $table->string('generated_wallet_stake_address', 128)->after('generated_wallet_mnemonic')->nullable();
            $table->string('linked_wallet_stake_address', 128)->after('generated_wallet_stake_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_accounts', function (Blueprint $table) {
            $table->dropColumn('auth_wallet');
            $table->dropColumn('generated_wallet_mnemonic');
            $table->dropColumn('generated_wallet_stake_address');
            $table->dropColumn('linked_wallet_stake_address');
        });
    }
};
