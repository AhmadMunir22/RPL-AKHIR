<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->unsignedBigInteger('court_id');
            $table->date('date');
            $table->string('slot');
            $table->unique(['court_id', 'date', 'slot'], 'unique_court_date_slot');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('booking_slots');
    }
};
