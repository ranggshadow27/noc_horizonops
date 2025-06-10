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
        Schema::table('sops', function (Blueprint $table) {
            // Hapus kolom id yang otomatis dibuat oleh Laravel
            $table->dropPrimary('sops_id_primary');

            // Tambahkan kolom baru untuk ID dengan format kustom
            $table->string('id')->primary()->change();

            // Jika Anda ingin menambahkan kolom number sebagai counter, bisa tambahkan di sini jika diperlukan
            // $table->integer('number')->autoIncrement()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sops', function (Blueprint $table) {
            // Kembalikan kolom id menjadi integer auto increment
            $table->dropPrimary('sops_id_primary');
            $table->id()->autoIncrement()->first();

            // Jika Anda menambahkan kolom number, hapusnya di sini
            // $table->dropColumn('number');
        });
    }
};
