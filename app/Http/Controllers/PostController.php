<?php

namespace App\Http\Controllers;

use App\Http\Repository\CommentRepository;
use App\Http\Repository\PostRepository;
use App\Http\Services\Authenticator;
use App\Http\Services\DataProtector;
use Illuminate\Http\Request;

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

    /** @var Authenticator $authenticator */
    private $authenticator;

    /** @var DataProtector $dataProtector */
    private $dataProtector;

    /**
     * PostController constructor.
     * @param CommentRepository $commentRepository
     * @param PostRepository $postRepository
     * @param Authenticator $authenticator
     * @param DataProtector $dataProtector
     */
    public function __construct(
        CommentRepository $commentRepository,
        PostRepository $postRepository,
        Authenticator $authenticator,
        DataProtector $dataProtector
    ) {
        $this->commentRepository = $commentRepository;
        $this->postRepository = $postRepository;
        $this->authenticator = $authenticator;
        $this->dataProtector = $dataProtector;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPost(Request $request)
    {
        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

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

        /* Prepare GET params */
        $params = [
            'id' => $postId
        ];
        if ($user !== null) {
            $params['user_id'] = $user['id'];
        }

        /* Retrieve specified Post */
        $collection = $this->postRepository->where($params);

        /* Ensure instance exists */
        if (!count($collection) || !$collection[0]['is_active']) {
            return response()->json([
                'status' => 404,
                'mesg' => 'post-not-found'
            ]);
        }

        /* Decorate Collection */
        $collection = $this->decorate($collection);

        /* Respond */
        return response()->json([
            'status' => 200,
            'post' => $collection
        ], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPosts(Request $request)
    {
        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

        /* Validate request */
        $validation = $this->handleValidateGet($request);
        if ($validation !== true) {
            return response()->json([
                'status' => 400,
                'mesg' => 'bad-request',
                'errors' => $validation
            ]);
        }

        /* Prepare GET params */
        $params = [
            'is_active' => 1,
            'is_published' => 1,
        ];
        if ($user !== null) {
            $params['user_id'] = $user['id'];
        }

        /* Retrieve Posts Collection */
        $collection = $this->postRepository->where($params);

        /* Decorate Collection */
        $collection = $this->decorate($collection);

        /* Respond */
        return response()->json([
            'status' => 200,
            'posts' => $collection
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

        /* Must be authorised/authenticated to continue */
        if ($user === null) {
            return $this->authenticator->notAuthenticated();
        }

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

        /* Prepare data for insert */
        $data = [
            'title' => $request->title,
            'slug' => $request->slug,
            'content' => $request->content,
            'is_published' => $request->is_published
        ];
        $data = $this->dataProtector->enforceOwnership($user, $data);

        /* Perform insert */
        $created = $this->postRepository->create($data);

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
        $user = $this->authenticator->handle($request);

        /* Must be authorised/authenticated to continue */
        if ($user === null) {
            return $this->authenticator->notAuthenticated();
        }

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

        /* Ensure User owns the entity */
        $ownership = $this->dataProtector->handle($user, $entity[0]);
        if (!$ownership) {
            return $this->dataProtector->badOwnership();
        }

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

        /* Perofrm update */
        $updated = $this->postRepository->update($request->id, [
            'title' => $request->title,
            'slug' => $request->slug,
            'content' => $request->content,
            'is_published' => $request->is_published
        ]);

        /* Respond */
        return response()->json([
            'status' => 200,
            'updated' => $updated
        ], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

        /* Must be authorised/authenticated to continue */
        if ($user === null) {
            return $this->authenticator->notAuthenticated();
        }

        /* Determine get */
        $id = $request->id;

        /* Validate request */
        $validation = $this->handleValidateGet($request);
        if ($validation !== true) {
            return response()->json([
                'status' => 400,
                'mesg' => 'bad-request',
                'errors' => $validation
            ]);
        }

        /* Retrieve specified Post; ensure it exists */
        $collection = $this->postRepository->where([
            'id' => $id
        ]);

        /* Ensure entity exists */
        if (!count($collection) || !$collection[0]['is_active']) {
            return response()->json([
                'status' => 404,
                'mesg' => 'post-not-found'
            ]);
        }

        /* Data Protection (ensure ownership before read/write) */
        $ownership = $this->dataProtector->handle($user, $collection[0]);
        if (!$ownership) {
            return $this->dataProtector->badOwnership();
        }

        /* Perform Delete operation */
        $this->postRepository->delete($request->id);

        /* Respond */
        return response()->json([
            'status' => 200,
            'mesg' => 'post-deleted'
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
