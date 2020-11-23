<?php

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
    echo phpinfo();
    return view('welcome');
});
// Route::get('/wx','WxController@wx');
Route::get('wx/token','WxController@getAccessToken');
Route::post('/wx','WxController@wxEvent');    
Route::get('wx/getweather','WxController@getweather');
Route::get('wx/createMenu','WxController@createMenu');
Route::get('wx/wxEvent','WxController@wxEvent');



//小程序接口
// Route::prefix('/api')->group(function(){
    Route::get('/goods','xcx\ApiController@goods');   
    Route::get('/wxlogin','xcx\ApiController@wxlogin'); 
    Route::get('/wxgoods','xcx\ApiController@wxgoods'); 
    Route::get('/datails','xcx\ApiController@datails'); 
// });
