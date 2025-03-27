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
        Schema::table('tmo_data', function (Blueprint $table) {
            $table->json('problem_json')->nullable()->after('tmo_type');
            $table->json('action_json')->nullable()->after('tmo_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tmo_data', function (Blueprint $table) {
            $table->dropColumn(['problem_json', 'action_json']);
        });
    }
};
