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
        Schema::create('site_logs', function (Blueprint $table) {
            $table->string('site_log_id')->primary();
            $table->string('site_id');
            $table->integer('modem_uptime')->default(0);
            $table->dateTime('modem_last_up')->nullable();
            $table->integer('traffic_uptime')->default(0);
            $table->string('sensor_status')->nullable();
            $table->string('nmt_ticket')->nullable();
            $table->timestamps();

            // Foreign key ke SiteDetails
            $table->foreign('site_id')->references('site_id')->on('site_details')->onDelete('cascade');
            // $table->foreign('nmt_ticket')->references('ticket_id')->on('nmt_tickets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_logs');
    }
};
