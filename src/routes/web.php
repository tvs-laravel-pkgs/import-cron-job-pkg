<?php

Route::group(['namespace' => 'Abs\ImportCronJobPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'import-cron-job-pkg'], function () {
	Route::get('/import-jobs/get-list', 'ImportJobController@getImportJobList')->name('getImportJobList');
	Route::post('/import-jobs/save', 'ImportJobController@saveImportCronJob')->name('saveImportCronJob');
	Route::get('/import-job/delete/{id}', 'ImportJobController@deleteImportJob')->name('deleteImportJob');

	Route::get('/import-job-cron/run', 'ImportJobCronController@executeImportJob')->name('executeImportJob');

});