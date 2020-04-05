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

Route::group(['namespace' => 'Social\Layout'], function (){
    Route::get('/video-conference', 'VideoConferenceController@index')->name('social.videoconference');
});



Auth::routes();

/* Route::get('/home', 'HomeController@index')->name('home'); */
