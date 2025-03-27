<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('tmo_task', function (Blueprint $table) {
            $table->enum('tmo_type', ['Preventive Maintenance', 'Corrective Maintenance'])->default('Preventive Maintenance');
            $table->string('latitude', 15)->nullable();
            $table->string('longitude', 15)->nullable();
        });

        Schema::table('tmo_data', function (Blueprint $table) {
            $table->string('spmk_number')->unique()->nullable();
        });
    }

    public function down()
    {
        Schema::table('tmo_task', function (Blueprint $table) {
            $table->dropColumn('tmo_type');
        });

        Schema::table('tmo_data', function (Blueprint $table) {
            $table->dropUnique(['spmk_number']);
            $table->dropColumn('spmk_number');
        });
    }
};
