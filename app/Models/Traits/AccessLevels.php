<?php

namespace App\Models\Traits;

use App\Models\User;

/**
 * User Access Levels for {@see \App\Models\User}.
 *
 * @property bool $banned
 * @property int $level
 */
trait AccessLevels
{
    /**
     * Return the user's permission level.
     *
     * @return int
     */
    public function accessLevel(): int
    {
        if ($this->banned) {
            return User::BANNED;
        }

        return $this->level;
    }

    /**
     * Check if a user is a developer.
     *
     * Used for guarding sensitive functions,
     *   e.g. debug info and feature flags.
     *
     * @return bool
     */
    public function developer(): bool
    {
        return $this->accessLevel() >= User::DEVELOPER;
    }

    /**
     * Check if a user is a moderator (above admin).
     *
     * @return bool
     */
    public function admin(): bool
    {
        return $this->accessLevel() >= User::ADMIN;
    }

    /**
     * Check if a user is a higher-trust staff manager.
     *
     * @return bool
     */
    public function manager(): bool
    {
        return $this->accessLevel() >= User::MANAGER;
    }

    /**
     * Check if a user can moderate and publish content.
     *
     * @return bool
     */
    public function moderator(): bool
    {
        return $this->accessLevel() >= User::MODERATOR;
    }

    /**
     * Check if a user has basic trusted editor access.
     *
     * @return bool
     */
    public function editor(): bool
    {
        return $this->accessLevel() >= User::EDITOR;
    }

    /**
     * Legacy alias for editor().
     */
    public function junior(): bool
    {
        return $this->editor();
    }

    /**
     * Legacy alias for moderator().
     */
    public function lolibrarian(): bool
    {
        return $this->moderator();
    }

    /**
     * Legacy alias for manager().
     */
    public function senior(): bool
    {
        return $this->manager();
    }

    /**
     * Check a user's access role.
     *
     * @return string
     */
    public function getRoleAttribute()
    {
        switch (true) {
            case $this->developer():
                return 'Developer';
            case $this->admin():
                return 'Administrator';
            case $this->manager():
                return 'Manager';
            case $this->moderator():
                return 'Moderator';
            case $this->editor():
                return 'Editor';
            case $this->banned:
                return 'Banned User';
            default:
                return 'Regular User';
        }
    }
}
