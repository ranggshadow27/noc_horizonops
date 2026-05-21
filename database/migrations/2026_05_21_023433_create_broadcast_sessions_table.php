<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('broadcast_sessions', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('area');
            $table->string('number_key');

            $table->text('template_message');

            $table->integer('interval_minutes')->default(10);

            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'stopped'])
                ->default('active');

            $table->integer('total_logs')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_sessions');
    }
};
