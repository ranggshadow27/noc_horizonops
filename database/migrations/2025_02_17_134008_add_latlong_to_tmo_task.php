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
        Schema::table('tmo_task', function (Blueprint $table) {

            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
        });
    }

    public function down()
    {
        Schema::table('tmo_task', function (Blueprint $table) {
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
        });
    }
};
