<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DreamboxController;

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

Route::get('', [DreamboxController::class, 'index'])->name('index_dreambox');
Route::get('new',[DreamboxController::class, 'new_dreambox'])->name('new_dreambox');
Route::get('dreambox/{dreambox}',[DreamboxController::class, 'show'])->where('dreambox', '[0-9]+')->name('show_dreambox');
Route::get('dreambox/{dreambox}/setup',[DreamboxController::class, 'setup'])->where('dreambox', '[0-9]+')->name('setup_dreambox');
Route::get('dreambox/{dreambox}/channel/{channel}/epg',[DreamboxController::class, 'show_epg'])->where('dreambox', '[0-9]+')->where('channel', '[0-9]+')->name('epg_dreambox');
