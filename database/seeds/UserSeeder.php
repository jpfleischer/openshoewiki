<?php

use App\Models\User;
use Illuminate\Database\Seeder;

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

        if ($user) {
            $user->forceFill([
                'username' => config('site.admin.username') ?? 'admin',
                'name' => config('site.admin.name') ?? 'Admin',
                'level' => User::DEVELOPER,
            ])->save();
        } else {
            $user = User::forceCreate([
                'id' => static::UUID,
                'username' => config('site.admin.username') ?? 'admin',
                'name' => config('site.admin.name') ?? 'Admin',
                'level' => User::DEVELOPER,
            ]);
        }

        User::setEventDispatcher($dispatcher);

        echo "Admin username: {$user->username}".PHP_EOL;
    }
}
