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
        Schema::create('cboss_tickets', function (Blueprint $table) {
            $table->string('ticket_id')->primary();
            $table->string('site_id'); // Foreign key dari site_details
            $table->string('province'); // Foreign key dari site_details
            $table->string('spmk')->nullable();
            $table->string('problem_map')->nullable();
            $table->string('trouble_category')->nullable();
            $table->string('status');
            $table->text('detail_action')->nullable();
            $table->dateTime('ticket_start')->nullable();
            $table->dateTime('ticket_last_update')->nullable();
            $table->dateTime('ticket_end')->nullable();

            $table->foreign('site_id')->references('site_id')->on('site_details')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('province')->references('province')->on('area_list')->onDelete('cascade')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cboss_tickets');
    }
};
