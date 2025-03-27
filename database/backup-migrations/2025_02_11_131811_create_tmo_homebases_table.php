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
        Schema::create('tmo_homebase', function (Blueprint $table) {
            $table->string('homebase_id', 20)->primary();
            $table->string('location', 100);
            $table->string('pic_name', 30)->nullable();
            $table->integer('total_device');
            $table->timestamps();
        });

        Schema::table('tmo_device_change', function (Blueprint $table) {
            $table->string('homebase_id', 20)->nullable();
            $table->foreign('homebase_id')->references('homebase_id')->on('tmo_homebase')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tmo_device_change', function (Blueprint $table) {
            $table->dropForeign(['homebase_id']);
            $table->dropColumn('homebase_id');
        });

        Schema::dropIfExists('tmo_homebase');
    }
};
