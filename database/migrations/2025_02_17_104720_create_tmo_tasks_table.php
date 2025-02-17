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
        Schema::create('tmo_tasks', function (Blueprint $table) {
            $table->id('task_id');
            $table->string('spmk_number')->unique();
            $table->string('site_id');
            $table->string('site_name');
            $table->string('province');
            $table->string('address');
            $table->string('engineer');

            $table->string('tmo_id');


            $table->foreign('site_id')->references('site_id')->on('site_details')->onDelete('cascade');
            $table->foreign('tmo_id')->references('tmo_id')->on('tmo_data')->onDelete('cascade'); // FK ke tmo_data

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmo_tasks');
    }
};
