<?php

namespace App\Http\Controllers;

use App\Http\Repository\CommentRepository;
use App\Http\Repository\PostRepository;
use App\Http\Services\Authenticator;
use App\Http\Services\DataProtector;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    /* CONSTANTS */
    const OK = 200;
    const NOT_FOUND = 404;
    const OK_CREATED = 201;
    const BAD_REQUEST = 400;
    const BAD_REQUEST_STRING = 'bad-request';
    const NOT_FOUND_STRING = 'entity-not-found';
    const DUPLICATE_SLUG = 'duplicate-slug';
    const INVALID_SLUG = 'invalid-slug';
    const STATUS = 'status';
    const MESSAGE = 'mesg';
    const PAYLOAD = 'payload';
    const DELETED = 'deleted';
    const ERRORS = 'errors';
    const VAL_OPTIONAL_INT = 'integer|min:1';
    const VAL_REQUIRED_INT = 'required|integer|min:1';
    const VAL_REQUIRED_BOOL = 'required|boolean';
    const VAL_CONTENT = 'required|max:255';
    const VAL_TOKEN = 'min:40|max:40';

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
        /* Validate request */
        $validation = $this->handleValidateGet($request);
        if ($validation !== true) {
            return response()->json([
                self::STATUS => self::BAD_REQUEST,
                self::MESSAGE => self::BAD_REQUEST_STRING,
                self::ERRORS => $validation
            ]);
        }

        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

        /* Determine get */
        $postId = $request->id;

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
                self::STATUS => self::NOT_FOUND,
                self::MESSAGE => self::NOT_FOUND_STRING
            ]);
        }

        /* Decorate Collection */
        $collection = $this->decorate($collection);

        /* Respond */
        return response()->json([
            self::STATUS => self::OK,
            self::PAYLOAD => $collection
        ], self::OK);
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
                self::STATUS => self::BAD_REQUEST,
                self::MESSAGE => self::BAD_REQUEST_STRING,
                self::ERRORS => $validation
            ]);
        }

        /* Detect page in request */
        $page = (int)$request->get('page', 1);

        /* Prepare GET params */
        $params = [
            'is_active' => 1,
            'is_published' => 1,
        ];
        if ($user !== null) {
            $params['user_id'] = $user['id'];
        }

        /* Retrieve Posts Collection */
        $collection = $this->postRepository->where($params, ['page' => $page]);

        /* Decorate Collection */
        $collection = $this->decorate($collection);

        /* Respond */
        return response()->json([
            self::STATUS => self::OK,
            self::PAYLOAD => $collection
        ], self::OK);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(Request $request)
    {
        /* Validate request */
        $validation = $this->handleValidatePost($request);
        if ($validation !== true) {
            return response()->json([
                self::STATUS => self::BAD_REQUEST,
                self::MESSAGE => self::BAD_REQUEST_STRING,
                self::ERRORS => $validation
            ]);
        }

        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

        /* Must be authorised/authenticated to continue */
        if ($user === null) {
            return $this->authenticator->notAuthenticated();
        }

        /* Slugify the requested slug */
        $postedSlug = Str::slug($request->slug, '-');

        /* Ensure valid slug */
        if (!strlen($postedSlug)) {
            return response()->json([
                self::STATUS => self::BAD_REQUEST,
                self::MESSAGE => self::INVALID_SLUG
            ]);
        }

        /* Ensure slug is unique */
        $slug = $this->postRepository->where([
            'slug' => $postedSlug
        ]);
        if (count($slug)) {
            return response()->json([
                self::STATUS => self::BAD_REQUEST,
                self::MESSAGE => self::DUPLICATE_SLUG
            ]);
        }

        /* Prepare data for insert */
        $data = [
            'title' => $request->title,
            'slug' => $postedSlug,
            'content' => $request->content,
            'is_published' => $request->is_published
        ];
        $data = $this->dataProtector->enforceOwnership($user, $data);

        /* Perform insert */
        $created = $this->postRepository->create($data);

        /* Respond */
        return response()->json([
            self::PAYLOAD => $created
        ], self::OK_CREATED);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function put(Request $request)
    {
        /* Validate request */
        $validation = $this->handleValidatePut($request);
        if ($validation !== true) {
            return response()->json([
                self::STATUS => self::BAD_REQUEST,
                self::MESSAGE => self::BAD_REQUEST_STRING,
                self::ERRORS => $validation
            ]);
        }

        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

        /* Must be authorised/authenticated to continue */
        if ($user === null) {
            return $this->authenticator->notAuthenticated();
        }

        /* Retrieve specified entity */
        $entity = $this->postRepository->where(['id' => $request->id]);

        /* Ensure specified entity exists */
        if (!$entity) {
            return response()->json([
                self::STATUS => self::NOT_FOUND,
                self::MESSAGE => self::NOT_FOUND_STRING
            ]);
        }

        /* Ensure User owns the entity */
        $ownership = $this->dataProtector->handle($user, $entity[0]);
        if (!$ownership) {
            return $this->dataProtector->badOwnership();
        }

        /* Slugify the requested slug */
        $postedSlug = Str::slug($request->slug, '-');

        /* Ensure valid slug */
        if (!strlen($postedSlug)) {
            return response()->json([
                self::STATUS => self::BAD_REQUEST,
                self::MESSAGE => self::INVALID_SLUG
            ]);
        }

        /* Ensure no other Post has same slug */
        $slug = $this->postRepository->where([
            'slug' => $postedSlug
        ]);
        if ($slug && $entity[0]['id'] !== $slug[0]['id']) {
            return response()->json([
                self::STATUS => self::BAD_REQUEST,
                self::MESSAGE => self::DUPLICATE_SLUG
            ]);
        }

        /* Perofrm update */
        $updated = $this->postRepository->update($request->id, [
            'title' => $request->title,
            'slug' => $postedSlug,
            'content' => $request->content,
            'is_published' => $request->is_published
        ]);

        /* Respond */
        return response()->json([
            self::STATUS => self::OK,
            self::PAYLOAD => $updated
        ], self::OK);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        /* Validate request */
        $validation = $this->handleValidateGet($request);
        if ($validation !== true) {
            return response()->json([
                self::STATUS => self::BAD_REQUEST,
                self::MESSAGE => self::BAD_REQUEST_STRING,
                self::ERRORS => $validation
            ]);
        }

        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

        /* Must be authorised/authenticated to continue */
        if ($user === null) {
            return $this->authenticator->notAuthenticated();
        }

        /* Determine get */
        $id = $request->id;

        /* Retrieve specified Post; ensure it exists */
        $collection = $this->postRepository->where([
            'id' => $id
        ]);

        /* Ensure entity exists */
        if (!count($collection) || !$collection[0]['is_active']) {
            return response()->json([
                self::STATUS => self::NOT_FOUND,
                self::MESSAGE => self::NOT_FOUND_STRING
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
            self::STATUS => self::OK,
            self::MESSAGE => self::DELETED
        ], self::OK);
    }

    /**
     * @param Request $request
     * @return bool|\Illuminate\Support\MessageBag
     */
    private function handleValidatePost(Request $request)
    {
        /* Validate request */
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title' => self::VAL_CONTENT,
            'slug' => self::VAL_CONTENT,
            'content' => self::VAL_CONTENT,
            'is_published' => self::VAL_REQUIRED_BOOL,
            'token' => self::VAL_TOKEN
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
            'id' => self::VAL_REQUIRED_INT,
            'title' => self::VAL_CONTENT,
            'slug' => self::VAL_CONTENT,
            'content' => self::VAL_CONTENT,
            'is_published' => self::VAL_REQUIRED_BOOL,
            'token' => self::VAL_TOKEN
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
            'id' => self::VAL_OPTIONAL_INT,
            'user_id' => self::VAL_OPTIONAL_INT,
            'token' => self::VAL_TOKEN
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
