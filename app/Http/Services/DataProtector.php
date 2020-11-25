<?php

namespace App\Http\Services;
use App\Http\Repository\UserRepository;

/**
 * Class DataProtector
 * @package App\Http\Services
 */
class DataProtector
{
    /**
     * DataProtector constructor.
     * @param UserRepository $userRepository
     */
    public function __construct(
        UserRepository $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    /**
     * @param array $user   An authenticated User
     * @param array $entity An entity with a user_id field
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public function handle(array $user, array $entity)
    {
        /**
         * Ensure entity is owned
         *  and User owns it
         */
        if (array_key_exists('user_id', $entity)
            && (int)$user['id'] !== (int)$entity['user_id']
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param array $user
     * @param array $entity
     * @return array
     */
    public function enforceOwnership(array $user, array $entity)
    {
        $entity['user_id'] = $user['id'];

        return $entity;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function badOwnership()
    {
        return response()->json([
            'status' => 404,
            'mesg' => 'entity-not-found:bad-ownership-request'
        ]);
    }
}