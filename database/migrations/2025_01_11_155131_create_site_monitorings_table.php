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
        Schema::create('site_monitor', function (Blueprint $table) {
            $table->id();
            $table->string('site_id');
            $table->enum('modem', ['Up', 'Down']);
            $table->enum('mikrotik', ['Up', 'Down']);
            $table->enum('ap1', ['Up', 'Down']);
            $table->enum('ap2', ['Up', 'Down']);
            $table->timestamp('modem_last_up')->nullable();
            $table->timestamp('mikrotik_last_up')->nullable();
            $table->timestamp('ap1_last_up')->nullable();
            $table->timestamp('ap2_last_up')->nullable();
            $table->enum('status', ['Normal', 'Major', 'Critical']);
            $table->timestamps();

            $table->foreign('site_id')->references('site_id')->on('site_details')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_monitor');
    }
};
