<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Comment
 * @package App
 */
class Comment extends Model
{
    /** @var string $table */
    protected $table = 'comment';

    /**
     * @var array
     */
    protected $fillable = [
        'user_id', 'post_id', 'is_active', 'content', 'is_published'
    ];
}
