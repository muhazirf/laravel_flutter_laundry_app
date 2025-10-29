<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Orders extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'outlet_id',
        'customer_id',
        'invoice_no',
        'status',
        'payment_status',
        'payment_method_id',
        'perfume_id',
        'discount_id',
        'discount_value_snapshot',
        'subtotal',
        'total',
        'notes',
        'checkin_at',
        'eta_at',
        'finished_at',
        'canceled_at',
        'collected_at',
        'collected_by_user_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_value_snapshot' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'checkin_at' => 'datetime',
            'eta_at' => 'datetime',
            'finished_at' => 'datetime',
            'canceled_at' => 'datetime',
            'collected_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlets::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customers::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function perfume(): BelongsTo
    {
        return $this->belongsTo(Perfumes::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function collectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by_user_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'order_id');
    }
}
