<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_monitor_csv', function (Blueprint $table) {
            $table->id();                    // bigint unsigned auto increment
            $table->string('site_id')->nullable();
            $table->string('sitecode')->nullable();

            // Status perangkat
            $table->enum('modem', ['Up', 'Down', 'Failed'])->nullable();
            $table->enum('mikrotik', ['Up', 'Down', 'Failed'])->nullable();
            $table->enum('ap1', ['Up', 'Down', 'Failed'])->nullable();
            $table->enum('ap2', ['Up', 'Down', 'Failed'])->nullable();

            // Last Up Time
            $table->timestamp('modem_last_up')->nullable();
            $table->timestamp('mikrotik_last_up')->nullable();
            $table->timestamp('ap1_last_up')->nullable();
            $table->timestamp('ap2_last_up')->nullable();

            // Status keseluruhan
            $table->enum('status', ['Normal', 'Minor', 'Major', 'Critical', 'Warning'])->nullable();

            // Tambahan
            $table->string('sensor_status')->nullable()->comment('Unknown Status');

            $table->timestamps();   // created_at & updated_at
            $table->softDeletes();  // deleted_at (opsional, tapi biasanya bagus ada)

            // Index untuk performa
            $table->index('site_id');
            $table->index('sitecode');
            $table->index(['modem', 'mikrotik', 'ap1', 'ap2']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_monitor_csv');
    }
};
