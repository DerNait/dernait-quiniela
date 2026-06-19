<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiniela_id')->constrained()->cascadeOnDelete();
            $table->string('team'); // 'home' | 'away'
            $table->string('name');
            $table->unsignedSmallInteger('number')->nullable();
            // 'player' = normal, 'own_goal' = autogol option for the scorer dropdown
            $table->string('kind')->default('player');
            $table->timestamps();

            $table->index(['quiniela_id', 'team']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
