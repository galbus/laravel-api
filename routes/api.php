<?php

use Illuminate\Http\Request;

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

Route::get('/test', function (Request $request) {
    return [
        'wibble' => 'wobble',
        'wobble' => 'wibble',
    ];
});

Route::get('/', function () {
    return view('welcome');
});


Route::post('/user/login', 'Api\User\AuthController@login')->name('api.user.auth.login');
Route::post('/user/refreshToken', 'Api\User\AuthController@refreshToken')->name('api.user.auth.refreshToken');
Route::post('/user/logout', 'Api\User\AuthController@logout')->middleware('auth:api')->name('api.user.auth.logout');

Route::get('/user/account', 'Api\User\AccountController@index')->middleware('auth:api')->name('api.user.auth.account');
Route::post('/user/account', 'Api\User\AccountController@update')->middleware('auth:api')->name('api.user.auth.account.update');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/user/register', 'Api\User\RegisterController@register')->name('api.user.register');

Route::post('/user/password/forgot', 'Api\User\ForgotPasswordController@sendResetLinkEmail')->name('password.reset');
Route::post('/user/password/reset', 'Api\User\ResetPasswordController@reset')->name('password.reset.token');

