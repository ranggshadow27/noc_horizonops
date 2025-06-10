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
        Schema::table('nmt_tickets', function (Blueprint $table) {
            $table->dateTime('target_online')->nullable()->after('aging');
        });
    }

    public function down()
    {
        Schema::table('nmt_tickets', function (Blueprint $table) {
            $table->dropColumn('target_online');
        });
    }
};
