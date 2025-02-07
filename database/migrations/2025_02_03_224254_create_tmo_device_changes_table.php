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
        Schema::create('tmo_device_change', function (Blueprint $table) {
            $table->string('tmo_device_change_id')->primary();
            $table->string('device_name');
            $table->string('device_sn');
            $table->string('device_img')->nullable(); // jika boleh kosong
            $table->string('tmo_id');

            // Foreign key constraint
            $table->foreign('tmo_id')
                  ->references('tmo_id')
                  ->on('tmo_data')
                  ->onDelete('cascade'); // opsional: hapus child jika parent dihapus

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmo_device_changes');
    }
};
