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
        Schema::create('device_networks', function (Blueprint $table) {
            $table->id('device_network_id');  // device_network_id sebagai primary key dan auto-increment
            $table->string('site_id');         // site_id sebagai foreign key yang mengacu ke tabel site_details
            $table->string('modem_ip');
            $table->string('router_ip');
            $table->string('ap1_ip');
            $table->string('ap2_ip');
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
        Schema::dropIfExists('device_networks');
    }
};
