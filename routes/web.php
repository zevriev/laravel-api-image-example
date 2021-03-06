<?php

use App\Http\Controllers\API\V1\ImageController;
use App\Http\Controllers\LogController;
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

Route::middleware(['cors'])->group(function () {
    Route::apiResource('images', ImageController::class, ['except' => 'update']);
    Route::post('api/v1/imagesBase64', 'App\Http\Controllers\API\V1\ImageController@storeBase64');
    Route::post('api/v1/imagesFromUrl', 'App\Http\Controllers\API\V1\ImageController@imagesFromUrl');
    Route::get('api/v1/logs', 'App\Http\Controllers\API\V1\LogController@getlist');
});
