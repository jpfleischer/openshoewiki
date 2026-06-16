<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCandidateEdit extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ACCEPTED_PENDING_STAFF = 'accepted_pending_staff';

    public const RISK_LOW = 'low';
    public const RISK_MEDIUM = 'medium';
    public const RISK_HIGH = 'high';

    protected $fillable = [
        'item_id',
        'base_revision_id',
        'user_id',
        'status',
        'title',
        'summary',
        'proposed_snapshot',
        'diff_payload',
        'risk_level',
        'vote_window_ends_at',
        'review_started_at',
        'resolved_at',
        'resolver_user_id',
        'applied_revision_id',
        'meta',
    ];

    protected $casts = [
        'proposed_snapshot' => 'array',
        'diff_payload' => 'array',
        'meta' => 'array',
        'vote_window_ends_at' => 'datetime',
        'review_started_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'id';
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function baseRevision(): BelongsTo
    {
        return $this->belongsTo(ItemRevision::class, 'base_revision_id');
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolver_user_id');
    }

    public function appliedRevision(): BelongsTo
    {
        return $this->belongsTo(ItemRevision::class, 'applied_revision_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ItemCandidateEditVote::class, 'candidate_edit_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function riskLabel(): string
    {
        return match ($this->risk_level) {
            self::RISK_LOW => 'Low Risk',
            self::RISK_HIGH => 'High Risk',
            default => 'Medium Risk',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_ACCEPTED_PENDING_STAFF => 'Accepted Pending Staff Review',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_APPLIED => 'Applied',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Open',
        };
    }

    public function approveVotesCount(): int
    {
        if (array_key_exists('approve_votes_count', $this->attributes)) {
            return (int) $this->attributes['approve_votes_count'];
        }

        return (int) $this->votes()->where('vote', ItemCandidateEditVote::APPROVE)->count();
    }

    public function rejectVotesCount(): int
    {
        if (array_key_exists('reject_votes_count', $this->attributes)) {
            return (int) $this->attributes['reject_votes_count'];
        }

        return (int) $this->votes()->where('vote', ItemCandidateEditVote::REJECT)->count();
    }
}
