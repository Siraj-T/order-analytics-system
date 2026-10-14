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
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);

        if (is_numeric($term)) {
            return $query->where('id', (int) $term);
        }

        // Extract alphanumeric tokens for Boolean Mode fulltext search
        $cleaned = preg_replace('/[+\-><()~*\"@.]+/', ' ', $term);
        $words = array_filter(explode(' ', $cleaned), fn ($w) => mb_strlen($w) >= 3);

        if (!empty($words)) {
            // Build Boolean Mode inverted index expression: +word1* +word2*
            $booleanQuery = implode(' ', array_map(fn ($w) => '+' . $w . '*', $words));
            return $query->whereRaw("MATCH(full_name, email) AGAINST(? IN BOOLEAN MODE)", [$booleanQuery]);
        }

        // High-speed fallback for short prefixes (< 3 chars): Use B+Tree prefix indexes
        return $query->where(function ($q) use ($term) {
            $q->where('full_name', 'LIKE', $term . '%')
              ->orWhere('email', 'LIKE', $term . '%');
        });
    }
}
