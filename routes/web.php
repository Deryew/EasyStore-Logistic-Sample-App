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
    Route::post('/pickup_methods', 'EasyStoreController@listPickupMethods');

    // Shipping Rates
    Route::post('/storefront/rates', 'EasyStoreController@getRatesSF');

    // Pickup Rates
    Route::post('/pickup_verify_rate', 'EasyStoreController@pickupVerifyRate');
    Route::get('/proxy/non-cod', 'EasyStoreController@pickupIFrame');
    Route::get('/proxy/cod', 'EasyStoreController@pickupIFrame');
    Route::post('/proxy/pickup-rate', 'EasyStoreController@pickupiFrameRate');
    Route::get('/proxy/pickup-rate', 'EasyStoreController@pickupIFrameRate');
    Route::get('proxy/tracking', 'EasyStoreController@orderTracking');



});
