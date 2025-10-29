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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('method_id');
            $table->decimal('amount', 12, 2);
            $table->dateTime('paid_at');
            $table->string('ref_no')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['SUCCESS', 'VOID'])->default('SUCCESS');
            $table->timestamps();

            $table->index('order_id');
            $table->index('method_id');
            $table->index('paid_at');
            $table->index('status');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('method_id')->references('id')->on('payment_methods')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
