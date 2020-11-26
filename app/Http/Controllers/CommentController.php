<?php

namespace App\Http\Controllers;

use App\Http\Repository\CommentRepository;
use App\Http\Services\Authenticator;
use App\Http\Services\DataProtector;
use Illuminate\Http\Request;

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

    /** @var DataProtector $dataProtector */
    private $dataProtector;

    /* CONSTANTS */
    const OK = 200;
    const NOT_FOUND = 404;
    const OK_CREATED = 201;
    const BAD_REQUEST = 400;
    const BAD_REQUEST_STRING = 'bad-request';
    const NOT_FOUND_STRING = 'entity-not-found';
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
     * CommentController constructor.
     * @param CommentRepository $commentRepository
     * @param Authenticator $authenticator
     * @param DataProtector $dataProtector
     */
    public function __construct(
        CommentRepository $commentRepository,
        Authenticator $authenticator,
        DataProtector $dataProtector
    ) {
        $this->commentRepository = $commentRepository;
        $this->authenticator = $authenticator;
        $this->dataProtector = $dataProtector;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getComment(Request $request)
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
        $commentId = $request->id;

        /* Prepare GET params */
        $params = [
            'id' => $commentId
        ];
        if ($user !== null) {
            $params['user_id'] = $user['id'];
        }

        /* Retrieve specified Comment */
        $collection = $this->commentRepository->where($params);

        /* Ensure Comment exists */
        if (!count($collection) || !$collection[0]['is_active']) {
            return response()->json([
                self::STATUS => self::NOT_FOUND,
                self::MESSAGE => self::NOT_FOUND_STRING
            ]);
        }

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
    public function getComments(Request $request)
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
        $page = (int)$request->get('p', 1);

        /* Prepare GET params */
        $params = [
            'is_active' => 1,
            'is_published' => 1,
        ];
        if ($user !== null) {
            $params['user_id'] = $user['id'];
        }
        if ($postId !== null) {
            $params['post_id'] = $postId;
        }

        /* Retrieve Comments collection */
        $collection = $this->commentRepository->where($params, [
            'page' => $page
        ]);

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

        /* Prepare data for insert */
        $data = [
            'post_id' => $request->post_id,
            'content' => $request->content,
            'is_published' => $request->is_published
        ];
        $data = $this->dataProtector->enforceOwnership($user, $data);

        /* Perform insert */
        $created = $this->commentRepository->create($data);

        /* Respond */
        return response()->json([
            self::STATUS => self::OK_CREATED,
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
        $entity = $this->commentRepository->where([
            'id' => $request->id
        ]);
        
        /* Ensure specified entity exists */
        if (!count($entity) || !$entity[0]['is_active']) {
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

        /* Perform update */
        $updated = $this->commentRepository->update($request->id, [
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
                self::STATUS => self::BAD_REQUEST,
                self::MESSAGE => self::BAD_REQUEST_STRING,
                self::ERRORS => $validation
            ]);
        }

        /* Retrieve specified Comment; ensure it exists */
        $collection = $this->commentRepository->where([
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
        $this->commentRepository->delete($request->id);

        /* Respond */
        return response()->json([
            self::STATUS => self::OK,
            self::MESSAGE =>self::DELETED
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
            'post_id' => self::VAL_REQUIRED_INT,
            'is_published' => self::VAL_REQUIRED_BOOL,
            'content' => self::VAL_CONTENT,
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
            'is_published' => self::VAL_REQUIRED_BOOL,
            'content' => self::VAL_CONTENT,
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
            'post_id' => self::VAL_OPTIONAL_INT,
            'token' => self::VAL_TOKEN
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }

        return true;
    }
}
