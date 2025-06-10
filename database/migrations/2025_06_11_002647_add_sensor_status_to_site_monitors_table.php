<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSensorStatusToSiteMonitorsTable extends Migration
{
    public function up()
    {
        Schema::table('site_monitor', function (Blueprint $table) {
            $table->string('sensor_status')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('site_monitor', function (Blueprint $table) {
            $table->dropColumn('sensor_status');
        });
    }
}
