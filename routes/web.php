<?php

Route::group(['namespace' => 'Toh\Statistical\Http\Controllers'], function () {
    Route::get('/demo', 'StatisticalController@getIndex');

    Route::get('count-visited', 'StatisticalController@countVisited');
	Route::get('save-cache-visited-website', 'StatisticalController@saveVisitedWebsite');
	Route::get('get-data-ajax-highchart','StatisticalController@getDataAjaxHighchart')->name('client.highchart');
	Route::get('get-statistical-7-day-nearest','StatisticalController@getStatistical7DayNearest');
	Route::get('get-info-git-pull-nearest','StatisticalController@getInfoGitPullNearest')->name('client.get-info-git-pull-nearest');
});