<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOutlets extends Model
{
    protected $table = 'user_outlets';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'outlet_id',
        'role',
        'permission_json',  
        'is_active',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }
}
