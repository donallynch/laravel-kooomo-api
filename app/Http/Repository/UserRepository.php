<?php

namespace App\Http\Repository;

use App\User;

class UserRepository
{
    /**
     * @var User
     */
    protected $model;

    /**
     * UserRepository constructor.
     * @param User $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
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
}