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
 Route::post('register', 'Customer\CustomerController@register');
 Route::post('login', 'Customer\CustomerController@authenticate');

 Route::group(['middleware' => ['jwt.verify']], function() {
     Route::get('my-profile', 'Customer\CustomerController@getAuthenticatedUser');
     Route::post('search', 'Customer\CustomerController@searchUser');
     Route::post('order', 'OrderController@placeOrder');
     Route::get('my-order', 'OrderController@myOrder');
     Route::get('my-inventory', 'InventoryController@myInventory');
     Route::post('remove-from-inventory', 'InventoryController@removeFromInventory');

 });
