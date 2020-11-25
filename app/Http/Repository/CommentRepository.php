<?php

namespace App\Http\Repository;

use App\Comment;
use Illuminate\Support\Facades\DB;

/**
 * Class CommentRepository
 * @package App\Http\Repository
 */
class CommentRepository
{
    /**
     * @var Comment
     */
    protected $model;

    /**
     * CommentRepository constructor.
     * @param Comment $model
     */
    public function __construct(Comment $model)
    {
        $this->model = $model;
    }

    /**
     * @param array $where
     * @return mixed
     */
    public function instance(array $where)
    {
        return $this->model
            ->where($where)->get()->toArray();
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes)
    {
        return $this->model->create($attributes);
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    public function update(int $id, array $attributes)
    {
        DB::table('comment')
            ->where('id', $id)
            ->limit(1)// optional - ensure only one record is updated
            ->update($attributes);

        return true;
    }

    /**
     * @param array $where
     * @param array $options
     * @return mixed
     */
    public function where(array $where, array $options = [])
    {
        /* ORDER BY */
        $orderField = 'id';
        $orderDirection = 'DESC';
        if (array_key_exists('orderBy', $options)) {
            $orderField = $options['orderBy'][0];
            $orderDirection = $options['orderBy'][1];
        }

        /* LIMIT & OFFSET */
        $limit = (array_key_exists('limit', $options)) ? $options['limit'] : 10;
        $offset = (array_key_exists('page', $options)) ? ($options['page']*$limit)-$limit : 0;

        return $this->model
            ->where($where)
            ->orderBy($orderField, $orderDirection)
            ->skip($offset)
            ->take($limit)
            ->get()
            ->toArray();
    }

    /**
     * @param array $where
     * @return mixed
     */
    public function count(array $where)
    {
        return $this->model
            ->where($where)
            ->count();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function delete(int $id)
    {
        /* Soft delete only */
        return $this->update($id, [
            'is_active' => 0
        ]);
    }

    /**
     * @param Comment $comment
     * @return Comment
     */
    public function publish(Comment $comment)
    {
        $comment->is_published = 1;
        $comment->save();

        return $comment;
    }
}