<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perfumes extends Model
{
    protected $table = 'perfumes';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'scent_profile',
        'price',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    public function services()
    {
        return $this->belongsToMany(Services::class, 'service_perfume', 'perfume_id', 'service_id');
    }
}
