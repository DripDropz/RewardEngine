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
        Schema::create('project_account_session_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->string('reference', 512);
            $table->string('event_id', 128);
            $table->string('event_type', 128);
            $table->unsignedBigInteger('event_timestamp');
            $table->string('game_id', 128)->index()->nullable();
            $table->string('target_reference', 512)->nullable();
            $table->unique(['event_id', 'reference', 'event_type'], 'UQ_project_account_session_events');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_account_session_events');
    }
};
