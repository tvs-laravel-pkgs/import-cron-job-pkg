<?php
Route::group(['namespace' => 'Abs\ImportCronJobPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'ImportCronJob-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});