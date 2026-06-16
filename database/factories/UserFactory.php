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

    public function junior()
    {
        return $this->state([
            'level' => User::JUNIOR_LOLIBRARIAN,
        ]);
    }

    public function lolibrarian()
    {
        return $this->state([
            'level' => User::LOLIBRARIAN,
        ]);
    }

    public function senior()
    {
        return $this->state([
            'level' => User::SENIOR_LOLIBRARIAN,
        ]);
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
