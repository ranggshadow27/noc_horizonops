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
        Schema::create('nmt_tickets', function (Blueprint $table) {
            $table->string('ticket_id')->primary();  // PK, string dan unique
            $table->string('site_id')->nullable();  // Foreign key dari tabel site_details
            $table->string('status');  // Status ticket
            $table->dateTime('date_start');  // Waktu mulai
            $table->integer('aging');  // Umur ticket dalam hitungan integer
            $table->string('problem_classification', 100);  // Klasifikasi masalah
            $table->string('problem_detail', 100);  // Detail masalah
            $table->string('problem_type', 20);  // Jenis masalah
            $table->text('update_progress');  // Progress yang diperbarui
            $table->timestamps();  // Untuk created_at dan updated_at

            $table->foreign('site_id')->references('site_id')->on('site_details')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nmt_tickets');
    }
};
