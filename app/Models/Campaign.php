<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'public_token', 'is_active', 'last_result_item_id'];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CampaignItem::class)->orderBy('sort_order');
    }

    public function lastResultItem(): BelongsTo
    {
        return $this->belongsTo(CampaignItem::class, 'last_result_item_id');
    }
}
