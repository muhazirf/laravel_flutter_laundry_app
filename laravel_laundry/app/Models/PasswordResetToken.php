<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'password_reset_tokens';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'email';

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'token',
        'otp',
        'otp_sent_at',
        'otp_verified',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'otp_sent_at' => 'datetime',
        'otp_verified' => 'boolean',
    ];

    /**
     * Check if OTP is valid and not expired
     */
    public function isOtpValid(string $otp): bool
    {
        if ($this->otp !== $otp) {
            return false;
        }

        // Check if OTP is not expired (valid for 10 minutes)
        if ($this->otp_sent_at && $this->otp_sent_at->addMinutes(10)->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if token is not expired
     */
    public function isTokenValid(): bool
    {
        $expireMinutes = config('auth.passwords.users.expire', 60);

        return $this->created_at->addMinutes($expireMinutes)->isFuture();
    }

    /**
     * Mark OTP as verified
     */
    public function markOtpAsVerified(): void
    {
        $this->update(['otp_verified' => true]);
    }
}
