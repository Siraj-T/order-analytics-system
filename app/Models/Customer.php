<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'full_name',
        'email',
        'country',
        'signup_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'signup_date' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCountry(Builder $query, ?string $country): Builder
    {
        if (!empty($country)) {
            return $query->where('country', $country);
        }
        return $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!empty($term)) {
            return $query->where(function ($q) use ($term) {
                if (is_numeric($term)) {
                    $q->where('id', (int) $term);
                } else {
                    $q->where('full_name', 'LIKE', $term . '%')
                      ->orWhere('email', 'LIKE', $term . '%');
                }
            });
        }
        return $query;
    }
}
