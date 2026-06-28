<?php

namespace App\Models;

/**
 * A blog post.
 *
 * @property string $message
 * @property string $user_id The ID of the {@link \App\User user} this post belongs to.
 *
 * @property \App\Models\User $user The {@link \App\User user} who owns this post.
 * @property \App\Models\User $topic The {@link \App\Topic topic} this post belongs to.
 */
class Post extends Model
{
    /**
     * Fillable attributes.
     *
     * @var array
     */
    protected $fillable = ['preview', 'body', 'image', 'slug'];

    /**
     * Date casts for attributes.
     *
     * @var array
     */
    protected $dates = ['published_at'];

    protected static function booted()
    {
        static::saving(function (self $post): void {
            if ($post->preview !== null) {
                $post->preview = purify($post->preview);
            }

            if ($post->body !== null) {
                $post->body = purify($post->body);
            }
        });
    }

    /**
     * The user who created this post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
