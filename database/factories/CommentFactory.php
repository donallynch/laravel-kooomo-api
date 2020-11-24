<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Comment;
use Faker\Generator as Faker;

$factory->define(Comment::class, function (Faker $faker) {
    $users = App\User::pluck('id')->toArray();
    $posts = App\Post::pluck('id')->toArray();
    return [
        'is_active' => $faker->boolean(),
        'is_published' => $faker->boolean(),
        'user_id' => $faker->randomElement($users),
        'post_id' => $faker->randomElement($posts),
        'title' => $faker->sentence(),
        'slug' => $faker->paragraph(),
        'content' => $faker->paragraph()
    ];
});
