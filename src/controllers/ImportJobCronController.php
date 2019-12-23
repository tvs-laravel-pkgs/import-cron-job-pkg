<?php

namespace Abs\ImportCronJobPkg;

use Abs\ImportCronJobPkg\ImportCronJob;
use App\Http\Controllers\Controller;

class ImportJobCronController extends Controller {

	public function executeImportJob($params = '{}') {
		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);

		try {
			$params = json_decode($params);

			$query = ImportCronJob::from('import_jobs');
			if (isset($params->type_id) && $params->type_id) {
				$query->where('type_id', $params->type_id);
			}
			$query->where('status_id', 7200); //PENDING

			$job = $query->orderBy('import_jobs.created_at')->first();
			if (!$job) {
				dump('Hurray! No Import Job are pending');
				return;
			}
			dump($job);
			dump($job->type->action);
			call_user_func($job->type->action, $job);
		} catch (\Throwable $e) {
			$job->status_id = 7203; //Error
			$job->error_details = 'Error:' . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(); //Error
			$job->save();
			dump($job->error_details);

		}
	}

}
