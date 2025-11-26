<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tymon\JWTAuth\Contracts\JWTSubject;


class Users extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'email_verified_at',
        'is_active',
        'remember_token',
        'api_key',
        'created_at',
        'updated_at',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function outlets()
    {
        return $this->hasMany(Outlets::class, 'owner_user_id');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Generate a unique API key for the user
     *
     * @return string
     */
    public function generateApiKey()
    {
        $apiKey = 'lk_' . bin2hex(random_bytes(16));
        $this->api_key = $apiKey;
        $this->save();
        return $apiKey;
    }

    /**
     * Check if user has an active API key
     *
     * @return bool
     */
    public function hasApiKey()
    {
        return !empty($this->api_key);
    }

    /**
     * Revoke user's API key
     *
     * @return void
     */
    public function revokeApiKey()
    {
        $this->api_key = null;
        $this->save();
    }
}
