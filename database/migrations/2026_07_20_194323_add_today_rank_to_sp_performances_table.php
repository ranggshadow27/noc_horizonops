<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sp_performances', function (Blueprint $table) {
            $table->integer('today_rank')->nullable()->after('today_ticket');
        });
    }

    public function down(): void
    {
        Schema::table('sp_performances', function (Blueprint $table) {
            $table->dropColumn('today_rank');
        });
    }
};
