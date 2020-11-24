<?php

use Illuminate\Database\Seeder;
use App\User;

/**
 * Class UserTableSeeder
 */
class UserTableSeeder extends Seeder
{
    public function run()
    {
        User::truncate();

        $faker = \Faker\Factory::create();

        User::create([
            'name'     => 'admin',
            'username' => 'admin@admin.com',
            'token' => 'AAAAAAAAAAAAAAAASSSSSSSSSSSSSSSSDDDDFFFF'
        ]);
    }
}
