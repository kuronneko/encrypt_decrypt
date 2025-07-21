<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
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

    /**
     * Encrypt email when setting it
     */
    public function setEmailAttribute($value)
    {
        // Only encrypt if it's not already encrypted
        try {
            Crypt::decryptString($value);
            // If decryption succeeds, it's already encrypted
            $this->attributes['email'] = $value;
        } catch (\Exception $e) {
            // If decryption fails, it's plain text, so encrypt it
            $this->attributes['email'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt email when getting it
     */
    public function getEmailAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // If decryption fails, return the value as is (for backward compatibility)
            return $value;
        }
    }

    /**
     * Get the encrypted email value (useful for direct database operations)
     */
    public function getEncryptedEmailAttribute()
    {
        return $this->attributes['email'];
    }
}
