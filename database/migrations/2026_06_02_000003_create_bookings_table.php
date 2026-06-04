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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('court_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->json('slots'); // e.g., ["08:00", "09:00", "10:00"]
            $table->decimal('total_price', 10, 2);
            $table->string('payment_status')->default('pending'); // pending, paid, partial, refunded, failed
            $table->string('payment_method')->nullable(); // wallet, midtrans
            $table->decimal('dp_amount', 10, 2)->default(0.00); // partial DP amount if chosen
            $table->string('status')->default('pending'); // pending, confirmed, completed, cancelled
            $table->string('qr_code')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
