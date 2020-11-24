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

        // Insert same password for every created user
        $password = \Illuminate\Support\Facades\Hash::make('token');

        User::create([
            'name'     => 'admin',
            'username' => 'admin@admin.com',
            'token' => 'AAAAAAAAAAAAAAAASSSSSSSSSSSSSSSSDDDDFFFF'
        ]);
    }
}
