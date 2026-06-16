<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemCandidateEditVote extends Model
{
    public const APPROVE = 1;
    public const REJECT = -1;

    protected $fillable = [
        'candidate_edit_id',
        'user_id',
        'vote',
        'reason',
    ];

    public function getRouteKeyName()
    {
        return 'id';
    }

    public function candidateEdit(): BelongsTo
    {
        return $this->belongsTo(ItemCandidateEdit::class, 'candidate_edit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
