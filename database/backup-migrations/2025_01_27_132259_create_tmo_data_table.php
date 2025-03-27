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
        Schema::create('tmo_data', function (Blueprint $table) {
            $table->string('tmo_id')->primary();
            $table->string('site_id');
            $table->string('site_name');
            $table->string('site_province');
            $table->string('site_address');
            $table->string('site_latitude');
            $table->string('site_longitude');
            $table->string('engineer_name');
            $table->integer('engineer_number');
            $table->string('pic_name');
            $table->integer('pic_number');
            $table->string('sqf');
            $table->string('esno');
            $table->string('power_source');
            $table->string('power_source_backup');
            $table->string('fan_rack1');
            $table->string('fan_rack2');
            $table->string('grounding');
            $table->string('ifl_length');
            $table->string('signal');
            $table->string('weather');
            $table->text('problem');
            $table->text('action');
            $table->enum('tmo_type', ['Preventive Maintenance', 'Corrective Maintenance']);
            $table->dateTime('tmo_start_date');
            $table->dateTime('tmo_end_date');
            $table->string('cboss_tmo_code')->nullable();
            $table->timestamps();

            $table->foreign('site_id')->references('site_id')->on('site_details')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmo_data');
    }
};
