<?php

namespace App\Models;

use App\Models\Traits\AccessLevels;
use App\Models\Traits\Closet;
use App\Models\Traits\DateHandling;
use App\Models\Traits\HasUuid;
use App\Models\Traits\Wishlist;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * A user of this application.
 *
 * @property string $name           The user's name.
 * @property string $username       The user's login username.
 *
 * @property int  $level    The user's level (permissions).
 * @property bool $banned   If the user is banned or not.
 * @property \App\Models\Image $image The user's profile image.
 * @property string $image_id         The user's profile image ID.
 *
 * @property \App\Models\Item[]|\Illuminate\Database\Eloquent\Collection $items    The {@link \App\Item items} this user has submitted.
 * @property \App\Models\Item[]|\Illuminate\Database\Eloquent\Collection $wishlist The {@link \App\Item items} this user has favourited.
 * @property \App\Models\Item[]|\Illuminate\Database\Eloquent\Collection $closet   The {@link \App\Item items} this user owns.
 * @property \App\Models\Post[]|\Illuminate\Database\Eloquent\Collection $posts    The posts this user has created.
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasApiTokens, HasUuid, DateHandling, Wishlist, Closet, AccessLevels;

    public const DEVELOPER = 1000;
    public const ADMIN = 500;
    public const MANAGER = 100;
    public const MODERATOR = 50;
    public const EDITOR = 10;
    public const REGULAR = 0;
    public const BANNED = -1;

    // Legacy aliases retained while older codepaths are migrated off inherited role names.
    public const SENIOR_LOLIBRARIAN = self::MANAGER;
    public const LOLIBRARIAN = self::MODERATOR;
    public const JUNIOR_LOLIBRARIAN = self::EDITOR;

    /**
     * Whether or not this model has an incrementing timestamp.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'discord_id',
        'discord_username',
        'discord_avatar',
    ];

    /**
     * Casts for attributes.
     *
     * @var array
     */
    protected $casts = [
        'banned' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * Visible attributes.
     *
     * @var array
     */
    protected $visible = [
        'username',
        'profile',
        'created_at',
        'level'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The items a user has submitted.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function contributionEvents(): HasMany
    {
        return $this->hasMany(ContributionEvent::class);
    }

    public function candidateEdits(): HasMany
    {
        return $this->hasMany(ItemCandidateEdit::class);
    }

    public function candidateEditVotes(): HasMany
    {
        return $this->hasMany(ItemCandidateEditVote::class);
    }

    public function contributionPoints(): int
    {
        if (array_key_exists('contribution_points', $this->attributes)) {
            return (int) $this->attributes['contribution_points'];
        }

        return (int) $this->contributionEvents()
            ->where('status', ContributionEvent::STATUS_AWARDED)
            ->sum('points');
    }

    public function contributionCount(): int
    {
        if (array_key_exists('contribution_count', $this->attributes)) {
            return (int) $this->attributes['contribution_count'];
        }

        return (int) $this->contributionEvents()
            ->where('status', ContributionEvent::STATUS_AWARDED)
            ->count();
    }

    /**
     * The items a user has favourited/wishlisted.
     *
     * @param string $order
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Models\Item[]
     */
    public function wishlist($order = 'added_new')
    {
        return $this->belongsToMany(Item::class, 'wishlist')->withTimestamps()->orderBy(...(sorted($order, 'wishlist')));
    }

    /**
     * The items a user owns.
     *
     * @param string $order
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\App\Models\Item[]
     */
    public function closet($order = 'added_new')
    {
        return $this->belongsToMany(Item::class, 'closet')->withTimestamps()->orderBy(...(sorted($order, 'closet')));
    }

    /**
     * The posts a user has.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Models\Post[]
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * The profile image for a user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\Image
     */
    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * Get a user's profile.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne|\App\Models\Profile
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Determine whether the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->moderator();
    }
}
