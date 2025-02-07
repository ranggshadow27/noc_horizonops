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
        Schema::create('site_details', function (Blueprint $table) {
            $table->string('site_id')->primary();  // site_id sebagai primary key
            $table->string('site_name');
            $table->string('province');
            $table->string('administrative_area');
            $table->string('address');
            $table->string('latitude');   // Menggunakan tipe decimal untuk latitude
            $table->string('longitude');  // Menggunakan tipe decimal untuk longitude
            $table->string('spotbeam');
            $table->string('gateway');
            $table->string('batch');
            $table->string('pic_number');
            $table->string('pic_name');
            $table->string('installer_name');
            $table->string('installer_number');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_details');
    }
};
