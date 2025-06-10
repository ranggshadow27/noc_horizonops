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
        Schema::create('chat_templates', function (Blueprint $table) {
            $table->string('id', 20)->primary(); // Ubah ke string untuk format CHT-TMP-XXX
            $table->string('name');
            $table->string('type');
            $table->text('template');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_templates');
    }
};
