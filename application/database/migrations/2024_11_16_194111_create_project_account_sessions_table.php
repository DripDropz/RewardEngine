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
        Schema::create('project_account_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_account_id')->constrained('project_accounts');
            $table->string('reference', 512)->index();
            $table->string('session_id', 64)->index();
            $table->string('auth_country_code', 2)->nullable();
            $table->dateTime('authenticated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_account_sessions');
    }
};
