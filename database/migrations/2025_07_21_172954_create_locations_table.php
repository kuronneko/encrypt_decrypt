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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key to users table
            $table->string('name'); // Location name (not encrypted)
            $table->text('address')->nullable(); // Encrypted field
            $table->string('city')->nullable(); // Not encrypted
            $table->string('state')->nullable(); // Not encrypted
            $table->string('country')->nullable(); // Not encrypted
            $table->string('postal_code')->nullable(); // Encrypted field
            $table->string('region')->nullable(); // Encrypted field
            $table->decimal('latitude', 10, 8)->nullable(); // GPS coordinates (not encrypted)
            $table->decimal('longitude', 11, 8)->nullable(); // GPS coordinates (not encrypted)
            $table->text('notes')->nullable(); // Encrypted field for sensitive notes
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
