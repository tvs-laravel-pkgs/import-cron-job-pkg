<?php

Route::group(['namespace' => 'Abs\ImportCronJobPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'import-cron-job-pkg'], function () {

	//IMPORT JOBS
	Route::get('/import-jobs/get-list', 'ImportJobController@getImportCronJobList')->name('getImportCronJobList');
	Route::get('/import-jobs/get-from-data/{id}', 'ImportJobController@getImportJobFormData')->name('getImportJobFormData');
	Route::post('/import-jobs/save', 'ImportJobController@saveImportCronJob')->name('saveImportCronJob');
	Route::get('/import-job/delete/{id}', 'ImportJobController@deleteImportJob')->name('deleteImportJob');

	Route::get('/import-job-cron/execute', 'ImportJobCronController@executeImportJob')->name('executeImportJob');

	//IMPORT CONFIGURATIONS
	Route::get('/import-types/get-list', 'ImportTypeController@getImportTypeList')->name('getImportTypeList');
	Route::get('/import-type/get-form-data', 'ImportTypeController@getImportTypeFormData')->name('getImportTypeFormData');
	Route::post('/import-type/save', 'ImportTypeController@saveImportType')->name('saveImportType');
	Route::get('/import-type/delete', 'ImportTypeController@deleteImportType')->name('deleteImportType');

});