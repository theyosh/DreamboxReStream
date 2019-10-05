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
//
//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});



//Route::group(['middleware' => ['web']], function () {
    Route::get('dreambox/{dreambox}','DreamboxController@load')->where('dreambox', '[0-9]+')->name('load_dreambox');
    Route::get('dreambox/{dreambox}/channel/{channel}/epg','DreamboxController@epg')->where('dreambox', '[0-9]+')->where('channel', '[0-9]+')->name('epg_dreambox');
    Route::get('dreambox/{dreambox}/recording','DreamboxController@recordings')->where('dreambox', '[0-9]+')->name('recordings');

    Route::get('dreambox/{dreambox}/status','DreamboxController@status')->where('dreambox', '[0-9]+')->name('status_dreambox');
    Route::post('dreambox/{dreambox}/channel/{channel}/stream','DreamboxController@stream')->where('dreambox', '[0-9]+')->where('channel', '[0-9]+')->name('stream_channel');
    Route::post('dreambox/{dreambox}/recording/{recording}/stream','DreamboxController@stream_recording')->where('dreambox', '[0-9]+')->where('recording', '[0-9]+')->name('stream_recording');


    //Route::get('dreambox/{dreambox}/setup','DreamboxController@store');

    Route::post('dreambox','DreamboxController@store');

   // Route::put('dreambox/{dreambox}','DreamboxController@update')->name('update.dreambox');
//});
