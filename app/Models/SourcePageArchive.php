<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourcePageArchive extends Model
{
    protected $fillable = [
        'user_id',
        'source_url',
        'domain',
        'title',
        'storage_disk',
        'storage_path',
        'content_hash',
        'content_bytes',
        'mime_type',
        'compression',
        'captured_at',
    ];

    protected $appends = [];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
