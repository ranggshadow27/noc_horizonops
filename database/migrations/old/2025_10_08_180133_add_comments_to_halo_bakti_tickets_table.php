<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('halo_bakti_tickets', function (Blueprint $table) {
            $table->json('comments')->nullable()->after('status'); // Tambah kolom JSON
        });
    }

    public function down(): void
    {
        Schema::table('halo_bakti_tickets', function (Blueprint $table) {
            $table->dropColumn('comments');
        });
    }
};
