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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outlet_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('invoice_no');
            $table->enum('status', ['ANTRIAN', 'PROSES', 'SIAP_DIAMBIL', 'SELESAI', 'BATAL']);
            $table->enum('payment_status', ['UNPAID', 'PAID'])->default('UNPAID');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->unsignedBigInteger('perfume_id')->nullable();
            $table->unsignedBigInteger('discount_id')->nullable();
            $table->decimal('discount_value_snapshot', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->text('notes')->nullable();
            $table->dateTime('checkin_at')->useCurrent();
            $table->dateTime('eta_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->dateTime('collected_at')->nullable();
            $table->unsignedBigInteger('collected_by_user_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->unique(['outlet_id', 'invoice_no']);
            $table->index(['outlet_id', 'status']);
            $table->index(['outlet_id', 'created_at']);
            $table->index(['outlet_id', 'eta_at']);

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
            $table->foreign('perfume_id')->references('id')->on('perfumes')->onDelete('set null');
            $table->foreign('discount_id')->references('id')->on('discounts')->onDelete('set null');
            $table->foreign('collected_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
