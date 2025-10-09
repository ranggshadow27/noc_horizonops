<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('halo_bakti_tickets', function (Blueprint $table) {
            $table->string('hb_tt_number')->nullable()->after('pic_number');
        });
    }

    public function down(): void
    {
        Schema::table('halo_bakti_tickets', function (Blueprint $table) {
            $table->dropColumn('hb_tt_number');
        });
    }
};
