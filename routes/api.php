<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiController;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['namespace' => "API"], function(){

    Route::post('/store/category', [ApiController::class, 'store_category']);
    Route::post('/store/subcategory', [ApiController::class, 'store_subcategory']);
    Route::post('/store/article', [ApiController::class, 'store_article']);
    Route::get('/categories', [ApiController::class, 'get_categories']);
    Route::post('/articles', [ApiController::class, 'get_articles']);
    Route::post('/update/article', [ApiController::class, 'update_article']);
    Route::get('/trending', [ApiController::class, 'trending_tags']);

});