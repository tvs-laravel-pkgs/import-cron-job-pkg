<?php

Route::group(['namespace' => 'Abs\ImportCronJobPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'import-cron-job-pkg'], function () {
	Route::get('/import-jobs/get-list', 'ImportJobController@getImportCronJobList')->name('getImportCronJobList');
	Route::get('/import-jobs/get-from-data/{id}', 'ImportJobController@getImportJobFormData')->name('getImportJobFormData');
	Route::post('/import-jobs/save', 'ImportJobController@saveImportCronJob')->name('saveImportCronJob');
	Route::get('/import-job/delete/{id}', 'ImportJobController@deleteImportJob')->name('deleteImportJob');

	Route::get('/import-job-cron/execute', 'ImportJobCronController@executeImportJob')->name('executeImportJob');

});