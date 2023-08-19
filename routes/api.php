<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//nodeMCU
Route::get('alat/data', "iotC@data");
Route::post('alat/sensor/post', "iotC@post");


//android
Route::post('login', "iotC@login");
Route::post('register', "iotC@register");
Route::get('android/{token_sensor}/data', "iotC@androidData");
Route::get('android/{token_sensor}/logs', "iotC@androidLogs");
Route::get('android/{token_sensor}/pengaturan', "iotC@androidPengaturan");
Route::post('android/{token_sensor}/pengaturan', "iotC@androidPengaturanPost");

