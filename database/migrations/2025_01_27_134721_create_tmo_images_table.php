<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTmoImagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tmo_images', function (Blueprint $table) {
            $table->string('tmo_id')->primary(); // Primary key sekaligus Foreign Key
            $table->string('transceiver_img')->nullable();
            $table->string('feedhorn_img')->nullable();
            $table->string('antenna_img')->nullable();
            $table->string('stabillizer_img')->nullable();
            $table->string('rack_img')->nullable();
            $table->string('modem_img')->nullable();
            $table->string('router_img')->nullable();
            $table->string('ap1_img')->nullable();
            $table->string('ap2_img')->nullable();
            $table->string('modem_summary_img')->nullable();
            $table->string('pingtest_img')->nullable();
            $table->string('speedtest_img')->nullable();
            $table->string('cm_ba_img')->nullable();
            $table->string('pm_ba_img')->nullable();
            $table->string('signplace_img')->nullable();
            $table->string('stabillizer_voltage_img')->nullable();
            $table->string('power_source_voltage_img')->nullable();
            $table->timestamps();

            $table->foreign('tmo_id')->references('tmo_id')->on('tmo_data')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmo_images');
    }
}
