<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('total_ticket_summary', function (Blueprint $table) {
            $table->id();
            $table->date('summary_date')->unique(); // Tanggal summary (unique agar gak duplikat per hari)
            $table->integer('ap1_down')->default(0);
            $table->integer('ap2_down')->default(0);
            $table->integer('ap1_and_2_down')->default(0);
            $table->integer('router_down')->default(0);
            $table->integer('all_sensor_down')->default(0);
            $table->integer('total_ticket')->default(0); // Opsional, dari kode asli
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('total_ticket_summary');
    }
};
