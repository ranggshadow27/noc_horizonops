<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('check_updates', function (Blueprint $table) {
            $table->id();
            $table->string('update_name', 50);
            $table->dateTime('update_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_updates');
    }
};
