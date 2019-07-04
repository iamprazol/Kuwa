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
Route::post('verify-user/{id}', 'UserController@verifyUser');
Route::post('forgot-password', 'UserController@passwordResetRequest');
Route::post('change-password/{id}', 'UserController@changePassword');
Route::post('resend-code/{id}', 'UserController@resendVerification');

 Route::group(['middleware' => ['jwt.verify', 'verifyuser']], function() {
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


 Route::middleware(['jwt.verify','verifyuser', 'admin'])->prefix('admin')->group( function () {
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
     Route::get('sales-report', 'OrderController@salesReport');

 });
