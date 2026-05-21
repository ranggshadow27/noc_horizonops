<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sweeping_tickets_followup_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('broadcast_session_id')
                ->constrained('broadcast_sessions')
                ->onDelete('cascade');

            // Disini yang diubah karena sweeping_tickets pakai sweeping_id
            $table->string('sweeping_id');                    // varchar(255)

            $table->string('number_key');
            $table->string('pic_phone');
            $table->string('pic_name')->nullable();

            $table->text('message');
            $table->json('api_response')->nullable();

            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])
                ->default('pending');

            $table->integer('attempt')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Index
            $table->index(['broadcast_session_id', 'status']);
            $table->index(['sweeping_id', 'pic_phone']);
            $table->index('number_key');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sweeping_tickets_followup_logs');
    }
};
