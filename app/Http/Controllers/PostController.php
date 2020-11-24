<?php

namespace App\Http\Controllers;

use App\Http\Repository\CommentRepository;
use App\Http\Repository\PostRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class PostController
 * @package App\Http\Controllers
 */
class PostController extends Controller
{
    /** @var CommentRepository $commentRepository */
    private $commentRepository;

    /** @var PostRepository $postRepository */
    private $postRepository;

    /**
     * PostController constructor.
     * @param CommentRepository $commentRepository
     * @param PostRepository $postRepository
     */
    public function __construct(
        CommentRepository $commentRepository,
        PostRepository $postRepository
    ) {
        $this->commentRepository = $commentRepository;
        $this->postRepository = $postRepository;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        /* Authenticate Request */
        $auth = $this->handleAuthenticateUser();
        if ($auth !== true) {
            return $auth;
        }

        /* Mock User */
        $user = new \stdClass();
        $user->id = 1;

        /* Determine get */
        $postId = $request->id;

        /* Validate request */
        $validation = $this->handleValidateGet($request);
        if ($validation !== true) {
            return response()->json([
                'status' => 400,
                'mesg' => 'bad-request',
                'errors' => $validation
            ]);
        }

        /* If retrieving specific post */
        if ($postId !== null) {
            $collection = $this->postRepository->where([
                'id' => $postId
            ]);

            /* Ensure instance exists */
            if (!count($collection) || !$collection[0]['is_active']) {
                return response()->json([
                    'status' => 404,
                    'mesg' => 'post-not-found'
                ]);
            }
        } elseif ($user !== null) {
            $collection = $this->postRepository->where([
                'is_active' => 1,
                'is_published' => 1,
                'user_id' => $user->id
            ]);
        } else {
            /* All posts */
            $collection = $this->postRepository->where([
                'is_active' => 1,
                'is_published' => 1
            ]);
        }

        /* Decorate Collection */
        $collection = $this->decorate($collection);

        /* Respond */
        return response()->json([
            'post' => $collection
        ], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(Request $request)
    {
        /* Authenticate Request */
        $auth = $this->handleAuthenticateUser();
        if ($auth !== true) {
            return $auth;
        }

        /* Mock User */
        $user = new \stdClass();
        $user->id = 1;

        /* Validate request */
        $validation = $this->handleValidatePost($request);
        if ($validation !== true) {
            return response()->json([
                'status' => 400,
                'mesg' => 'bad-request',
                'errors' => $validation
            ]);
        }

        /* Ensure slug is unique */
        $slug = $this->postRepository->where([
            'slug' => $request->slug
        ]);
        if (count($slug)) {
            return response()->json([
                'status' => 400,
                'mesg' => 'duplicate-slug'
            ]);
        }

        /* perform create */
        $created = $this->postRepository->create([
            'user_id' => $user->id,//<-- Can only create their own Posts
            'title' => $request->title,
            'slug' => $request->slug,
            'content' => $request->content,
            'is_published' => $request->is_published
        ]);

        /* Respond */
        return response()->json([
            'post' => $created
        ], 201);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function put(Request $request)
    {
        /* Authenticate Request */
        $auth = $this->handleAuthenticateUser();
        if ($auth !== true) {
            return $auth;
        }

        /* Mock User */
        $user = new \stdClass();
        $user->id = 1;

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
        $entity = $this->postRepository->where(['id' => $request->id]);

        /* Ensure specified entity exists */
        if (!$entity) {
            return response()->json([
                'status' => 404,
                'mesg' => 'post-not-found'
            ]);
        }
        $entity = $entity;

        /* Ensure no other Post has same slug */
        $slug = $this->postRepository->where([
            'slug' => $request->slug
        ]);
        if ($slug && $entity[0]['id'] !== $slug[0]['id']) {
            return response()->json([
                'status' => 400,
                'mesg' => 'duplicate-slug'
            ]);
        }

        /* Ensure Users can only update their own Posts */
        if ((int)$user->id !== (int)$entity[0]['user_id']) {
            return response()->json([
                'status' => 404,
                'mesg' => 'post-not-found'
            ]);
        }

        /* Perofrm update */
        $updated = $this->postRepository->update($request->id, [
            'is_active' => $request->is_active,
            'user_id' => $user->id,
            'title' => $request->title,
            'slug' => $request->slug,
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
            'is_active' => 'boolean',
            'title' => 'required|max:255',
            'slug' => 'required|max:255',
            'content' => 'required|max:255',
            'is_published' => 'required|boolean'
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
            'is_active' => 'boolean',
            'title' => 'required|max:255',
            'slug' => 'required|max:255',
            'content' => 'required|max:255',
            'is_published' => 'required|boolean',
            'id' => 'required|integer|min:1'
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
            'user_id' => 'integer|min:1'
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }

        return true;
    }

    /**
     * TODO:: AUTHENTICATION
     *  I didn't have time to also implement authentication
     *  through Oauth or basic token passing.
     *  If I had more time, I would have sent an auth token with each request, like this:
     *      'Authorization' => "Bearer ABCDEFG_faketoken_HIJKLMNOP",
     *  For each protected route we query the token to check if it is valid and who owns it.
     *  We can then consider the request authenticated (if the token is valid and identifies a specific User)
     * @return bool|\Illuminate\Http\JsonResponse
     */
    private function handleAuthenticateUser()
    {
        $user = Auth::user();
        if ($user === null) {
//            return response()->json([
//                'status' => 401,
//                'mesg' => 'Unauthorised'
//            ], 401);
        }

        return true;
    }

    /**
     * Posts (published), with 5 recent comments and totalCommentCount
     * @param $collection
     * @return mixed
     */
    private function decorate($collection)
    {
        foreach ($collection as $key => $value) {

            /* Attach comments */
            $comments = $this->commentRepository
                ->where([
                    'post_id' => $value['id']
                ], [
                    'limit' => 5,
                    'orderBy' => [
                        'id','DESC'
                    ]
                ]);
            $collection[$key]['comments'] = $comments;

            /* Attach total comment count */
            $commentCount = $this->commentRepository
                ->count([
                    'post_id' => $value['id']
                ]);
            $collection[$key]['totalCommentCount'] = $commentCount;
        }

        return $collection;
    }
}