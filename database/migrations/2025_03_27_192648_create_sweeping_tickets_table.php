<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sweeping_tickets', function (Blueprint $table) {
            $table->string('sweeping_id')->primary(); // Primary key string
            $table->string('site_id'); // Foreign key dari site_details
            $table->string('status', 100); // String max 100
            $table->string('classification', 30); // String max 30
            $table->text('problem_classification'); // Text
            $table->string('cboss_tt', 30)->nullable(); // String max 30, nullable
            $table->text('cboss_problem')->nullable(); // Text, nullable

            // Foreign key constraint
            $table->foreign('site_id')
                ->references('site_id')
                ->on('site_details')
                ->onDelete('cascade'); // Atau 'cascade' sesuai kebutuhan

            $table->timestamps(); // Created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sweeping_tickets');
    }
};
