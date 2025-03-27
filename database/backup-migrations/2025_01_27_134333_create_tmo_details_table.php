<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTmoDetailsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tmo_details', function (Blueprint $table) {
            $table->string('tmo_id')->primary(); // Primary key langsung di tmo_id
            $table->string('transceiver_sn');
            $table->string('feedhorn_sn');
            $table->string('antenna_sn');
            $table->string('stabillizer_sn');
            $table->string('rack_sn');
            $table->string('modem_sn');
            $table->string('router_sn');
            $table->string('ap1_sn');
            $table->string('ap2_sn');
            $table->string('transceiver_type');
            $table->string('modem_type');
            $table->string('router_type');
            $table->string('ap1_type');
            $table->string('ap2_type');
            $table->timestamps();

            $table->foreign('tmo_id')->references('tmo_id')->on('tmo_data')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmo_details');
    }
}
