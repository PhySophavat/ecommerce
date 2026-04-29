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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('shop_name');
            $table->enum('business_type', ['Fashion', 'Beauty', 'Electronic', 'Sport', 'Home', 'Food', 'Health', 'Book', 'Toy', 'Other'])->nullable();
            $table->text('business_description')->nullable();
            $table->string('shop_logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Suspended'])->default('Pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};