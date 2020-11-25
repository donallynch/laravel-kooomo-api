<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Temporary setup for local testing //
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization');
///////////////////////////////////////

Route::get('test', 'TestController@index');

Route::post('post/post', 'PostController@post');
Route::put('post/put', 'PostController@put');
Route::get('posts/get', 'PostController@getPosts');
Route::get('post/get', 'PostController@getPost');
Route::delete('post/delete', 'PostController@delete');

Route::post('comment/post', 'CommentController@post');
Route::put('comment/put', 'CommentController@put');
Route::get('comments/get', 'CommentController@getComments');
Route::get('comment/get', 'CommentController@getComment');
Route::delete('comment/delete', 'CommentController@delete');

//Route::middleware(['auth:api'])->group(function () {
//    Route::get('comment', 'CommentController@get');
//    Route::post('comment', 'CommentController@post');
//    Route::put('comment/{id}', 'CommentController@put');
//});
