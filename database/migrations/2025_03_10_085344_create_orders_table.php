<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('subtotal', 10, 2);
            $table->string('order_number', 20)->unique();
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('status')->default('pending'); // pending, processing, completed, failed, etc.
            $table->string('payment_status')->default('pending'); // pending, paid, failed
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->string('razorpay_order_id')->nullable();
            $table->timestamps();
        });

        // order_items table
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('price', 10, 2); // Price at time of order
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        // transactions table
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->string('transaction_id');
            $table->string('payment_method');
            $table->string('status');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('INR');
            $table->json('response_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
