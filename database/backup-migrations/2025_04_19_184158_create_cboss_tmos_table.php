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
        Schema::create('cboss_tmo', function (Blueprint $table) {
            $table->string('tmo_id')->primary();
            $table->string('site_id');
            $table->string('province');
            $table->string('spmk_number');
            $table->string('techinican_name');
            $table->string('techinican_number');
            $table->string('pic_name');
            $table->string('pic_number');
            $table->string('tmo_by');
            $table->string('tmo_code');
            $table->integer('esno');
            $table->integer('sqf');
            $table->integer('ifl_cable');
            $table->text('problem');
            $table->json('action');
            $table->string('homebase');
            $table->dateTime('tmo_date');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('site_id')->references('site_id')->on('site_details')->onDelete('cascade');
            $table->foreign('province')->references('province')->on('area_list')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cboss_tmo');
    }
};
