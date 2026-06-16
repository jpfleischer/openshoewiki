<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemRevision extends Model
{
    protected $fillable = [
        'item_id',
        'user_id',
        'revision_number',
        'event',
        'summary',
        'snapshot_hash',
        'snapshot',
        'meta',
    ];

    protected $appends = [];

    protected $casts = [
        'snapshot' => 'array',
        'meta' => 'array',
    ];

    public function getRouteKeyName()
    {
        return 'id';
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidateEdits(): HasMany
    {
        return $this->hasMany(ItemCandidateEdit::class, 'base_revision_id');
    }
}
