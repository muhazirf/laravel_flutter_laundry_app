<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceVariants extends Model
{
    protected $table = 'service_variants';

    protected $primaryKey = 'id';

    protected $fillable = [
        'service_id',
        'name',
        'additional_price',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    public function service()
    {
        return $this->belongsTo(Services::class, 'service_id');
    }
}
