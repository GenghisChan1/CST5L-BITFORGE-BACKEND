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
        // Cart Items Table
        Schema::create('cart_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id');
            $table->string('item_id');
            $table->foreign('user_id')->references('id')->on('userstable')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('grand_total', 15, 2);
            $table->integer('stocks');
            $table->timestamps();
        });

        // Purchase Receipts Table
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id');
            $table->string('item_id');
            $table->foreign('user_id')->references('id')->on('userstable');
            $table->foreign('item_id')->references('id')->on('items');
            $table->integer('quantity');
            $table->decimal('total_price', 15, 2);
            $table->decimal('shipping_fee', 15, 2);
            $table->decimal('grand_total', 15, 2);
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('deliver_to');
            $table->string('shipped_from');
            $table->string('fullname');
            $table->string('phone_number');
            $table->timestamps();
        });

        // Pending Orders Table
        Schema::create('pending_orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id');
            $table->string('item_id');
            $table->foreign('user_id')->references('id')->on('userstable');
            $table->foreign('item_id')->references('id')->on('items');
            $table->integer('quantity');;
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('shipping_fee', 15, 2);
            $table->decimal('grand_total', 15, 2);
            $table->string('deliver_to');
            $table->string('shipped_from');
            $table->string('fullname');
            $table->string('phone_number');
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('item_id');
            $table->string('user_id');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('userstable')->onDelete('cascade');
            $table->text('comment');
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
        });

         Schema::create('itemsrate' , function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('item_id');
            $table->string('user_id');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('userstable')->onDelete('cascade');
            $table->integer('rate')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('purchase_receipts');
        Schema::dropIfExists('pending_orders');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('itemsrate');
    }
};
