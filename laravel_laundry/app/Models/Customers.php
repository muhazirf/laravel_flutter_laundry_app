<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customers extends Model
{
    protected $table = 'Customers';

    protected $primaryKey = 'id';

    protected $fillable = [
        'outlet_id',
        'name',
        'phone',
        'email',
        'address',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    public function outlet()
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }
}
