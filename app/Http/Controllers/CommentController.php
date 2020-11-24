<?php

namespace App\Http\Controllers;

use App\Http\Repository\CommentRepository;
use App\Comment;
use App\Http\Services\Authenticator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class CommentController
 * @package App\Http\Controllers
 */
class CommentController extends Controller
{
    /** @var CommentRepository $commentRepository */
    private $commentRepository;

    /** @var Authenticator $authenticator */
    private $authenticator;

    /**
     * CommentController constructor.
     * @param CommentRepository $commentRepository
     * @param Authenticator $authenticator
     */
    public function __construct(
        CommentRepository $commentRepository,
        Authenticator $authenticator
    ) {
        $this->commentRepository = $commentRepository;
        $this->authenticator = $authenticator;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

        /* Determine get */
        $commentId = $request->id;
        $page = (int)$request->get('p', 1);

        /* Validate request */
        $validation = $this->handleValidateGet($request);
        if ($validation !== true) {
            return response()->json([
                'status' => 400,
                'mesg' => 'bad-request',
                'errors' => $validation
            ]);
        }

        /* If retrieving specific comment */
        if ($commentId !== null) {
            $collection = $this->commentRepository->where([
                'id' => $commentId
            ]);

            /* Ensure instance exists */
            if (!count($collection) || !$collection[0]['is_active']) {
                return response()->json([
                    'status' => 404,
                    'mesg' => 'comment-not-found'
                ]);
            }
        } elseif ($user !== null) {
            $collection = $this->commentRepository->where([
                'is_active' => 1,
                'is_published' => 1,
                'user_id' => $user['id']
            ], [
                'page' => $page
            ]);
        } else {
            /* All active published comments */
            $collection = $this->commentRepository->where([
                'is_active' => 1,
                'is_published' => 1
            ], [
                'page' => $page
            ]);
        }

        /* Respond */
        return response()->json([
            'comment' => $collection
        ], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(Request $request)
    {
        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

        /* Validate request */
        $validation = $this->handleValidatePost($request);
        if ($validation !== true) {
            return response()->json([
                'status' => 400,
                'mesg' => 'bad-request',
                'errors' => $validation
            ]);
        }

        /* perform create */
        $created = $this->commentRepository->create([
            'user_id' => $user['id'],
            'post_id' => $request->post_id,
            'content' => $request->content,
            'is_published' => $request->is_published
        ]);

        /* Respond */
        return response()->json([
            'comment' => $created
        ], 201);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function put(Request $request)
    {
        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

        /* Validate request */
        $validation = $this->handleValidatePut($request);
        if ($validation !== true) {
            return response()->json([
                'status' => 400,
                'mesg' => 'bad-request',
                'errors' => $validation
            ]);
        }

        /* Retrieve specified entity */
        $entity = $this->commentRepository->where([
            'id' => $request->id
        ]);
        
        /* Ensure specified entity exists */
        if (!count($entity) || !$entity[0]['is_active']) {
            return response()->json([
                'status' => 404,
                'mesg' => 'comment-not-found'
            ]);
        }
        $entity = $entity[0];

        /* Ensure Comment.user can only update their own Posts */
        if ((int)$user['id'] !== (int)$entity['user_id']) {
            return response()->json([
                'status' => 404,
                'mesg' => 'comment-not-found'
            ]);
        }

        /* Perofrm update */
        $updated = $this->commentRepository->update($request->id, [
            'is_active' => $request->is_active,
            'user_id' => $user['id'],
            'content' => $request->content,
            'is_published' => $request->is_published
        ]);

        /* Respond */
        return response()->json([
            'updated' => $updated
        ], 200);
    }

    /**
     * @param Request $request
     * @return bool|\Illuminate\Support\MessageBag
     */
    private function handleValidatePost(Request $request)
    {
        /* Validate request */
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'post_id' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'is_published' => 'required|boolean',
            'content' => 'required|max:255'
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }

        return true;
    }

    /**
     * @param Request $request
     * @return bool|\Illuminate\Support\MessageBag
     */
    private function handleValidatePut(Request $request)
    {
        /* Validate request */
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'id' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'is_published' => 'required|boolean',
            'content' => 'required|max:255'
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }

        return true;
    }

    /**
     * @param Request $request
     * @return bool|\Illuminate\Support\MessageBag
     */
    private function handleValidateGet(Request $request)
    {
        /* Validate request */
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'id' => 'integer|min:1',
            'user_id' => 'integer|min:1',
            'post_id' => 'integer|min:1'
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }

        return true;
    }
}
