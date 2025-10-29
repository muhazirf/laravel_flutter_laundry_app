<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    protected $table = 'services';

    protected $primaryKey = 'id';

    protected $fillable = [
        'outlet_id',
        'name',
        'description',
        'price',
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
