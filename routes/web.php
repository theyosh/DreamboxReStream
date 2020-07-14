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

Route::get('', 'DreamboxController@index')->name('index_dreambox');
Route::get('new','DreamboxController@new_dreambox')->name('new_dreambox');

//Route::middleware(PrivateMode::class)->group(function () {

Route::get('dreambox/{dreambox}','DreamboxController@show')->where('dreambox', '[0-9]+')->name('show_dreambox');
Route::get('dreambox/{dreambox}/setup','DreamboxController@setup')->where('dreambox', '[0-9]+')->name('setup_dreambox');
Route::get('dreambox/{dreambox}/channel/{channel}/epg','DreamboxController@show_epg')->where('dreambox', '[0-9]+')->where('channel', '[0-9]+')->name('epg_dreambox');
//
//Route::put('dreambox/{dreambox}','DreamboxController@update')->name('update.dreambox');
//Route::post('dreambox','DreamboxController@store')->name('new.dreambox');

//});
