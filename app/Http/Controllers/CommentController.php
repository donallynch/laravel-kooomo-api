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
        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

        /* Determine get */
        $commentId = $request->id;

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
                'status' => 404,
                'mesg' => 'comment-not-found'
            ]);
        }

        /* Respond */
        return response()->json([
            'status' => 200,
            'comment' => $collection
        ], 200);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getComments(Request $request)
    {
        /* Authenticate Request */
        $user = $this->authenticator->handle($request);

        /* Determine get */
        $postId = $request->id;
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
            'status' => 200,
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
            'status' => 201,
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

        /* Retrieve specified Comment; ensure it exists */
        $collection = $this->commentRepository->where([
            'id' => $id
        ]);

        /* Ensure entity exists */
        if (!count($collection) || !$collection[0]['is_active']) {
            return response()->json([
                'status' => 404,
                'mesg' => 'comment-not-found'
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
            'status' => 200,
            'mesg' => 'comment-deleted'
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
