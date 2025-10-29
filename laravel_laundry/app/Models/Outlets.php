<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outlets extends Model
{
    protected $table = 'outlets';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'ownerr_user_id',
        'address',
        'phone',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    public function owner()
    {
        return $this->belongsTo(Users::class, 'owner_user_id');
    }
}
