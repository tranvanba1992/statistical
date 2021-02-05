<?php

Route::group(['namespace' => 'Toh\Statistical\Http\Controllers'], function () {
    Route::get('/demo', 'StatisticalController@getIndex');
});