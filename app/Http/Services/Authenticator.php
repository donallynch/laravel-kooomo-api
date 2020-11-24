<?php

namespace App\Http\Services;
use App\Http\Repository\UserRepository;
use Illuminate\Http\Request;

/**
 * Class Authenticator
 * @package App\Http\Services
 */
class Authenticator
{
    /** @var UserRepository $userRepository */
    private $userRepository;

    /** @var null|array $user */
    public $user = null;

    /** @var bool $isRequestAuthenticated */
    public $isRequestAuthenticated = false;

    /**
     * Authenticator constructor.
     * @param UserRepository $userRepository
     */
    public function __construct(
        UserRepository $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    /**
     * @param Request $request
     * @return array|null
     */
    public function handle(Request $request)
    {
        /* Detect authentication in request */
        $token = $request->token;

        /* If token detected in request */
        if ($token !== null) {

            /* Lookup user with specified token */
            $user = $this->userRepository->where([
                'token' => $token
            ]);

            /* Ensure Authenticated User associated with token */
            if (!count($user)) {
                return null;
            }

            /* Specify Authenticated User */
            $this->isRequestAuthenticated = true;
            $this->user = $user[0];

            return $this->getUser();
        }

        return null;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function notAuthenticated()
    {
        return response()->json([
            'status' => 401,
            'mesg' => 'Unauthorised'
        ], 401);
    }

    /**
     * @return array|null
     */
    public function getUser()
    {
        return $this->user;
    }
}