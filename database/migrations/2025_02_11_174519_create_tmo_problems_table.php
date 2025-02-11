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
        Schema::create('tmo_problems', function (Blueprint $table) {
            $table->string('problem_id', 20)->primary();
            $table->string('problem_classification', 100);
            $table->string('problem_type', 100);
            $table->enum('problem_category', ['Teknis', 'Non-Teknis']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::dropIfExists('tmo_problems');
    }
};
