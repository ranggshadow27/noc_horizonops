<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('halo_bakti_tickets', function (Blueprint $table) {
            $table->string('ticket_id')->primary();  // String PK custom
            $table->string('site_id'); // Ganti foreignId jadi string biasa
            $table->foreign('site_id')->references('site_id')->on('site_details'); // Refer ke site_id
            $table->text('description');
            $table->string('pic_name')->nullable();
            $table->string('pic_number')->nullable();
            $table->enum('status', ['Pending', 'On Progress', 'Closed', 'Unresolved'])->default('Pending');
            $table->timestamps();
            $table->softDeletes();  // Optional buat soft delete
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halo_bakti_tickets');
    }
};
