<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Post
 * @package App
 */
class Post extends Model
{
    /** @var string $table */
    protected $table = 'post';

    /** @var array $fillable */
    protected $fillable = [
        'is_active', 'user_id', 'title', 'slug', 'content', 'is_published'
    ];
}
