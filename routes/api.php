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
Route::post('regi', 'UserController@regi');
Route::post('login', 'UserController@authenticate');

 Route::group(['middleware' => ['jwt.verify']], function() {
     Route::get('my-profile', 'UserController@getAuthenticatedUser');
     Route::put('profile-edit', 'UserController@update');
     Route::post('order', 'OrderController@placeOrder');
     Route::get('my-order', 'OrderController@myOrder');
     Route::get('my-inventory', 'InventoryController@myInventory');
     Route::post('remove-from-inventory', 'InventoryController@removeFromInventory');
     Route::get('my-notifications', 'NotificationController@myNotification');
     Route::get('notification/{id}', 'NotificationController@readNotification');
     Route::post('logout', 'UserController@logout');
 });


 Route::middleware(['jwt.verify', 'admin'])->prefix('admin')->group( function () {
     Route::get('my-profile', 'UserController@getAuthenticatedUser');
     Route::put('profile-edit', 'UserController@update');
     Route::get('order-list', 'OrderController@orderList');
     Route::post('verify-order/{id}', 'OrderController@verifyOrder');
     Route::post('deliver-order/{id}', 'OrderController@orderDelivered');
     Route::post('reject-order/{id}', 'OrderController@rejectOrder');
     Route::get('rejected-list', 'OrderController@rejectedList');
     Route::get('inventory-list', 'InventoryController@listInventory');
     Route::get('customers', 'UserController@customerList');
     Route::post('search', 'UserController@searchCustomer');
     Route::get('pending-customers', 'UserController@pendingCustomer');
     Route::post('add-items', 'InventoryController@adminInventory');
     Route::get('my-inventory', 'InventoryController@myInventory');
     Route::post('remove-from-inventory', 'InventoryController@removeFromInventory');

 });
