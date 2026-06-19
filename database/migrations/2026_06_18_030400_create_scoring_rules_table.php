<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Editable points table, one row per category per quiniela.
        Schema::create('scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiniela_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // App\Enums\ScoringCategory value
            $table->unsignedSmallInteger('points');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['quiniela_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_rules');
    }
};
