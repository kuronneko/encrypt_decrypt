<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;
use App\Traits\HandlesEncryptedFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\OptimizedEncryptedFields; // Using the optimized version for better performance

class Location extends Model
{
    use HasFactory, HandlesEncryptedFields;

    /**
     * Define which fields are encrypted
     */
    protected $encryptedFields = [
        'address',      // Street address - sensitive
        'postal_code',  // Postal/ZIP code - can be sensitive
        'region',       // Specific region info - might be sensitive
        'notes',        // Notes field - likely contains sensitive info
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'region',
        'latitude',
        'longitude',
        'notes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }

    // Encrypted field accessors and mutators

    /**
     * Encrypt address when setting it
     */
    public function setAddressAttribute($value)
    {
        if (!empty($value)) {
            try {
                Crypt::decryptString($value);
                $this->attributes['address'] = $value;
            } catch (\Exception $e) {
                $this->attributes['address'] = Crypt::encryptString($value);
            }
        }
    }

    /**
     * Decrypt address when getting it
     */
    public function getAddressAttribute($value)
    {
        if (!empty($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    /**
     * Encrypt postal_code when setting it
     */
    public function setPostalCodeAttribute($value)
    {
        if (!empty($value)) {
            try {
                Crypt::decryptString($value);
                $this->attributes['postal_code'] = $value;
            } catch (\Exception $e) {
                $this->attributes['postal_code'] = Crypt::encryptString($value);
            }
        }
    }

    /**
     * Decrypt postal_code when getting it
     */
    public function getPostalCodeAttribute($value)
    {
        if (!empty($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    /**
     * Encrypt region when setting it
     */
    public function setRegionAttribute($value)
    {
        if (!empty($value)) {
            try {
                Crypt::decryptString($value);
                $this->attributes['region'] = $value;
            } catch (\Exception $e) {
                $this->attributes['region'] = Crypt::encryptString($value);
            }
        }
    }

    /**
     * Decrypt region when getting it
     */
    public function getRegionAttribute($value)
    {
        if (!empty($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    /**
     * Encrypt notes when setting it
     */
    public function setNotesAttribute($value)
    {
        if (!empty($value)) {
            try {
                Crypt::decryptString($value);
                $this->attributes['notes'] = $value;
            } catch (\Exception $e) {
                $this->attributes['notes'] = Crypt::encryptString($value);
            }
        }
    }

    /**
     * Decrypt notes when getting it
     */
    public function getNotesAttribute($value)
    {
        if (!empty($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    /**
     * Create a new model instance for the encrypted fields trait
     */
    public function newModelInstance()
    {
        return new static();
    }

    // Relationships

    /**
     * The user that owns this location
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    /**
     * Scope to get only active locations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get locations by country
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    // Helper methods

    /**
     * Get full address as a single string
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if location has GPS coordinates
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }
}
