<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Post;
use Faker\Generator as Faker;

$factory->define(Post::class, function (Faker $faker) {
    $users = App\User::pluck('id')->toArray();
    return [
        'is_active' => $faker->boolean(),
        'is_published' => $faker->boolean(),
        'user_id' => $faker->randomElement($users),
        'title' => $faker->sentence(),
        'slug' => $faker->paragraph(),
        'content' => $faker->paragraph()
    ];
});
