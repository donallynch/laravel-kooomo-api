<?php

use Illuminate\Database\Seeder;
use App\User;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * User have 3 possible roles:
     * 0 - SP  - SubmitProduct
     * 1 - AP  - ApproveProduct
     * 2 - SAP - SeeApprovedProduct
     *
     * @return void
     */
    public function run()
    {
        User::truncate();

        $faker = \Faker\Factory::create();

        // Insert same password for every created user
        $password = \Illuminate\Support\Facades\Hash::make('password');

        // Generate 1 user with the role 0 - SP
        User::create([
            'name'     => 'admin',
            'username'    => 'admin@admin.com',
            'role'     => 0,
            'password' => $password
        ]);

        // Generate 10 users with the role 1 - SAP
        for ($i = 0; $i < 10; $i++) {
            User::create([
                'name'     => $faker->name,
                'username'    => $faker->userName,
                'role'     => 1,
                'password' => $password
            ]);
        }
    }
}
