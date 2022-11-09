<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'PagesController@root')->name('root');
Auth::routes(['verify'=>true]);

Route::group(['middleware' => ['auth', 'verified']], function(){
    Route::get('user_addresses', 'UserAddressesController@index')->name('user_addresses.index');
});