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
        Schema::table('tmo_data', function (Blueprint $table) {
            $table->json('problem_json')->nullable()->change();
            $table->json('action_json')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tmo_data', function (Blueprint $table) {
            $table->text('problem_json')->nullable()->change(); // Kembalikan ke tipe sebelumnya
            $table->text('action_json')->nullable()->change();
        });
    }
};
