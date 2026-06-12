<?php

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * A UUID for the admin user.
     *
     * @var string
     */
    protected const UUID = '00000000-0000-0000-0000-000000000000';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dispatcher = User::getEventDispatcher();

        User::unsetEventDispatcher();

        $user = User::find(static::UUID);
        $password = null;

        if ($user) {
            $user->forceFill([
                'email' => config('site.admin.email') ?? 'admin@example.com',
                'username' => config('site.admin.username') ?? 'admin',
                'name' => config('site.admin.name') ?? 'Admin',
                'level' => User::DEVELOPER,
                'email_verified_at' => now('UTC'),
            ])->save();
        } else {
            $password = Str::random(64);

            $user = User::forceCreate([
                'id' => static::UUID,
                'email' => config('site.admin.email') ?? 'admin@example.com',
                'username' => config('site.admin.username') ?? 'admin',
                'password' => bcrypt($password),
                'name' => config('site.admin.name') ?? 'Admin',
                'level' => User::DEVELOPER,
                'email_verified_at' => now('UTC'),
            ]);
        }

        User::setEventDispatcher($dispatcher);

        echo "Admin email: {$user->email}".PHP_EOL;

        if ($password !== null) {
            echo "Admin password: {$password}".PHP_EOL;
        }
    }
}
