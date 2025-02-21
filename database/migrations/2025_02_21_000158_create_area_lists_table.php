<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::create('area_list', function (Blueprint $table) {
        //     $table->string('province')->primary(); // ID pakai nama provinsi
        //     $table->string('area'); // Nama Area, misalnya "Sumatera", "Jawa", dll.
        //     $table->timestamps();
        // });

        Schema::table('tmo_data', function (Blueprint $table) {
            $table->string('site_province')->nullable()->change(); // Pastikan kolom bisa menampung FK
            $table->foreign('site_province')->references('province')->on('area_list')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_list');
    }
};
