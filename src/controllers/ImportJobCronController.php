<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\ImportJob;

class ImportJobCronController extends Controller {

	public function importJobs($params = '{}') {
		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);

		$new_count = $updated_count = $success_count = $error_count = $processed_count = 0;
		$params = json_decode($params);

		$query = ImportJob::from('import_jobs');
		if (isset($params->type_id) && $params->type_id) {
			$query->where('type_id', $params->type_id);
		}
		$query->where('status_id', 7200); //PENDING

		$job = $query->orderBy('import_jobs.created_at')->first();
		if (!$job) {
			dump('No Import Jobs Found');
			return;
		}
		$error_count = 0;
		$newCount = 0;
		$updatedcount = 0;
		$total_records = $job->total_record_count;
		$outputfile = $job->output_file;
		$records_per_request = 50;

	}

}
