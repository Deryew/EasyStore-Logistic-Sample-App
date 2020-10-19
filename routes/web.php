<?php

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

Route::group(['prefix' => 'easystore'], function () {
    Route::get('/', 'EasyStoreController@index');
    Route::get('/install', 'EasyStoreController@install');
    Route::post('/uninstall', 'EasyStoreController@uninstall');
    Route::get('/fulfill', 'EasyStoreController@redirectToFulfillment');
    Route::post('/fulfillment/create', 'EasyStoreController@createFulfillment');

    // Shipping Rates
    Route::post('/storefront/rates', 'EasyStoreController@getRatesSF');

    // Pickup Rates
    Route::post('/pickup_verify_rate', 'Controller@pickupVerifyRate');
    Route::get('/proxy/non-cod', 'Controller@pickupIFrame');
    // Route::get('/proxy/cod', 'Controller@pickupIFrame');
    Route::post('/proxy/pickup-rate', 'Controller@pickupiFrameRate');


});
