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
        Schema::create('devices', function (Blueprint $table) {
            $table->id('device_id');  // device_id sebagai primary key dan auto-increment
            $table->string('site_id'); // site_id sebagai foreign key yang mengacu ke tabel site_details
            $table->string('rack_sn');
            $table->string('antenna_sn');
            $table->string('antenna_type');
            $table->string('transceiver_sn');
            $table->string('transceiver_type');
            $table->string('modem_sn');
            $table->string('modem_type');
            $table->string('router_sn');
            $table->string('router_type');
            $table->string('ap1_sn');
            $table->string('ap1_type');
            $table->string('ap2_sn');
            $table->string('ap2_type');
            $table->string('stabilizer_sn');
            $table->string('stabilizer_type');
            $table->timestamps();

            // Menambahkan foreign key constraint untuk site_id
            $table->foreign('site_id')->references('site_id')->on('site_details')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
