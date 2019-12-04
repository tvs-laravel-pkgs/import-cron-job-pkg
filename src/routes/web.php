<?php

Route::group(['namespace' => 'Abs\ImportCronJobPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'ImportCronJob-pkg'], function () {
	Route::get('/ImportCronJobs/get-list', 'ImportCronJobController@getImportCronJobList')->name('getImportCronJobList');
	Route::get('/ImportCronJob/get-form-data/{id?}', 'ImportCronJobController@getImportCronJobFormData')->name('getImportCronJobFormData');
	Route::post('/ImportCronJob/save', 'ImportCronJobController@saveImportCronJob')->name('saveImportCronJob');
	Route::get('/ImportCronJob/delete/{id}', 'ImportCronJobController@deleteImportCronJob')->name('deleteImportCronJob');

});