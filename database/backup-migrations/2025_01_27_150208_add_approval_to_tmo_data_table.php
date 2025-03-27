<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApprovalToTmoDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tmo_data', function (Blueprint $table) {
            $table->string('approval')->default('Pending');  // Kolom approval dengan default 'Pending'
            $table->string('approval_details')->nullable(); // Kolom approval_details yang nullable
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tmo_data', function (Blueprint $table) {
            $table->dropColumn(['approval', 'approval_details']);
        });
    }
}
