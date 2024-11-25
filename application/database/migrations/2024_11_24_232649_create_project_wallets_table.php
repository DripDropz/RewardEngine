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
        Schema::create('project_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->string('stake_key_hex', 64);
            $table->string('stake_key_address', 64);
            $table->string('auth_nonce', 36);
            $table->dateTime('auth_issued');
            $table->dateTime('auth_expiration');
            $table->unique(['project_id', 'stake_key_hex', 'stake_key_address'], 'UQ_project_wallets');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_wallets');
    }
};
