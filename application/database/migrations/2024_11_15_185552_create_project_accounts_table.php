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
        Schema::create('project_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->string('auth_provider', 32);
            $table->string('auth_provider_id', 128)->nullable();
            $table->string('auth_name', 1024)->nullable();
            $table->string('auth_email', 1024)->nullable();
            $table->string('auth_avatar', 1024)->nullable();
            $table->unique(['project_id', 'auth_provider', 'auth_provider_id'], 'UQ_project_provider_accounts');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_accounts');
    }
};
