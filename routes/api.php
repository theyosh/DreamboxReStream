<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DreamboxController;

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

Route::get('dreambox/{dreambox}',[DreamboxController::class, 'load'])->where('dreambox', '[0-9]+')->name('load_dreambox');
Route::get('dreambox/{dreambox}/channel/{channel}/epg',[DreamboxController::class, 'epg'])->where('dreambox', '[0-9]+')->where('channel', '[0-9]+')->name('epg_dreambox');
Route::get('dreambox/{dreambox}/recording',[DreamboxController::class, 'recordings'])->where('dreambox', '[0-9]+')->name('recordings');

Route::get('dreambox/{dreambox}/status',[DreamboxController::class, 'status'])->where('dreambox', '[0-9]+')->name('status_dreambox');
Route::post('dreambox/{dreambox}/channel/{channel}/stream',[DreamboxController::class, 'stream'])->where('dreambox', '[0-9]+')->where('channel', '[0-9]+')->name('stream_channel');
Route::post('dreambox/{dreambox}/recording/{recording}/stream',[DreamboxController::class, 'stream_recording'])->where('dreambox', '[0-9]+')->where('recording', '[0-9]+')->name('stream_recording');

Route::post('dreambox/{dreambox}/stop',[DreamboxController::class, 'stop'])->where('dreambox', '[0-9]+')->name('stop_streaming');

Route::put('dreambox/{dreambox}',[DreamboxController::class, 'update'])->name('update.dreambox');
Route::post('dreambox',[DreamboxController::class, 'store'])->name('new.dreambox');
