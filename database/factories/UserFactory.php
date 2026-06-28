<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        $username = $this->faker->unique()->userName;

        return [
            'id' => uuid4(),
            'name' => $this->faker->name,
            'username' => $username,
            'banned' => false,
            'level' => User::REGULAR,
        ];
    }

    public function editor()
    {
        return $this->state([
            'level' => User::EDITOR,
        ]);
    }

    public function moderator()
    {
        return $this->state([
            'level' => User::MODERATOR,
        ]);
    }

    public function manager()
    {
        return $this->state([
            'level' => User::MANAGER,
        ]);
    }

    public function junior()
    {
        return $this->editor();
    }

    public function lolibrarian()
    {
        return $this->moderator();
    }

    public function senior()
    {
        return $this->manager();
    }

    public function admin()
    {
        return $this->state([
            'level' => User::ADMIN,
        ]);
    }

    public function developer()
    {
        return $this->state([
            'level' => User::DEVELOPER,
        ]);
    }

    public function banned()
    {
        return $this->state([
            'level' => User::BANNED,
            'banned' => true,
        ]);
    }

}
