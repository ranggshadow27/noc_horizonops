<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sp_performances', function (Blueprint $table) {
            $table->string('sp_perf_id', 50)->primary(); // Ubah ke string buat custom format
            $table->string('sp_id', 50);
            $table->integer('today_ticket');
            $table->timestamps();

            $table->foreign('sp_id')->references('sp_id')->on('service_providers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_performances');
    }
};
