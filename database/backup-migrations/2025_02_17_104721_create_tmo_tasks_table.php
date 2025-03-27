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
        Schema::create('tmo_task', function (Blueprint $table) {
            $table->id('task_id');
            $table->string('spmk_number')->unique();
            $table->string('site_id');
            $table->string('site_name', 100);
            $table->string('province', 50);
            $table->text('address');
            $table->string('engineer', 50);
            $table->string('engineer_number', 25)->nullable();

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
        Schema::dropIfExists('tmo_task');
    }
};
