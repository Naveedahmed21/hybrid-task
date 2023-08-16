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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->unsignedBigInteger('organizer_id');
            $table->string('google_event_id')->nullable();
            $table->foreign('organizer_id')->references('id')->on('users'); // Assuming you have a 'users' table for organizers
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
