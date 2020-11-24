<?php

use Illuminate\Database\Seeder;
use App\Post;

/**
 * Class PostTableSeeder
 */
class PostTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Post::truncate();

        $faker = \Faker\Factory::create();

        // 20 fake unpublished posts
        for ($i = 0; $i < 20; $i++) {
            Post::create([
                'is_active' => $faker->boolean(),
                'title' => $faker->sentence,
                'slug' => $faker->sentence,
                'content' => $faker->paragraph,
                'is_published' => 0
            ]);
        }

        // 30 fake published posts
        for ($i = 0; $i < 30; $i++) {
            Post::create([
                'is_active' => $faker->boolean(),
                'title' => $faker->sentence,
                'slug' => $faker->sentence,
                'content' => $faker->paragraph,
                'is_published' => 1
            ]);
        }
    }
}
