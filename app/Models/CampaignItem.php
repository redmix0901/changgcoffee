<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignItem extends Model
{
    use HasFactory;

    protected $fillable = ['campaign_id', 'label', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
