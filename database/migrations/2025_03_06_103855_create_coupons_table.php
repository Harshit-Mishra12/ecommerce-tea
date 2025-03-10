<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('discount', 8, 2); // Discount amount or percentage
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed'); // Type of discount
            $table->integer('usage_limit')->nullable(); // Max times a coupon can be used
            $table->integer('used_count')->default(0); // Track usage
            $table->dateTime('expires_at')->nullable(); // Expiry date
            $table->boolean('is_active')->default(true); // Active status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};

