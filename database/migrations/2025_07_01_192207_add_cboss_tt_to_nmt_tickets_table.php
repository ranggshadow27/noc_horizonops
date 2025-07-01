<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCbossTtToNmtTicketsTable extends Migration
{
    public function up()
    {
        Schema::table('nmt_tickets', function (Blueprint $table) {
            $table->string('cboss_tt')->nullable()->after('ticket_id');
        });
    }

    public function down()
    {
        Schema::table('nmt_tickets', function (Blueprint $table) {
            $table->dropColumn('cboss_tt');
        });
    }
}
