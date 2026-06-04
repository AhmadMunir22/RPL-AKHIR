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
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // e.g., Indoor, Outdoor
            $table->text('description')->nullable();
            $table->decimal('price_per_hour', 10, 2);
            $table->json('photos')->nullable(); // Store gallery paths as JSON array
            $table->string('status')->default('active'); // active, maintenance
            $table->decimal('rating_avg', 3, 2)->default(0.00);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
