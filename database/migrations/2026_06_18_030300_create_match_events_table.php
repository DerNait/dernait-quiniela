<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The live timeline the admin builds during the match. Goal events are
        // also the source of truth from which quiniela_results is recomputed.
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiniela_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'goal' | 'red_card' | 'penalty'
            $table->string('team')->nullable(); // 'home' | 'away'
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->unsignedSmallInteger('minute');
            $table->unsignedTinyInteger('half')->default(1); // 1 | 2, drives HT score
            $table->timestamps();

            $table->index(['quiniela_id', 'minute']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
