<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionEvent extends Model
{
    public const STATUS_AWARDED = 'awarded';
    public const STATUS_REVOKED = 'revoked';

    public const ITEM_CREATED = 'item_created';
    public const ITEM_PUBLISHED = 'item_published';
    public const ITEM_UPDATED = 'item_updated';
    public const IMAGE_ADDED = 'image_added';
    public const ATTRIBUTES_ADDED = 'attributes_added';
    public const CANDIDATE_EDIT_APPLIED = 'candidate_edit_applied';

    protected $fillable = [
        'user_id',
        'item_id',
        'item_revision_id',
        'event_type',
        'points',
        'status',
        'summary',
        'meta',
        'awarded_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'awarded_at' => 'datetime',
        'points' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function itemRevision(): BelongsTo
    {
        return $this->belongsTo(ItemRevision::class);
    }
}
