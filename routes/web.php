<?php

use App\Http\Controllers\API\V1\ImageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::apiResource('images', ImageController::class, ['except' => 'update']);
Route::post('api/v1/imagesBase64', 'App\Http\Controllers\API\V1\ImageController@storeBase64');
Route::post('api/v1/imagesFromUrl', 'App\Http\Controllers\API\V1\ImageController@imagesFromUrl');
