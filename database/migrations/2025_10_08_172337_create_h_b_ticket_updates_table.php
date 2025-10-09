<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hb_ticket_updates', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_id');  // FK string karna ticket_id string
            $table->foreign('ticket_id')->references('ticket_id')->on('halo_bakti_tickets')->onDelete('cascade');
            $table->text('comment');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_updates');
    }
};
