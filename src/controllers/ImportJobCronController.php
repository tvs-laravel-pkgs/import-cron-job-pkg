<?php

namespace Abs\ImportCronJobPkg;

use Abs\ImportCronJobPkg\ImportCronJob;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

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
			//START TIME
			$get_current_start_time = Carbon::now();
			$start_time = $get_current_start_time->hour . ':' . $get_current_start_time->minute . ':' . $get_current_start_time->second;
			$job->start_time = $start_time;

			call_user_func($job->type->action, $job);

			//END TIME
			$get_current_end_time = Carbon::now();
			$end_time = $get_current_end_time->hour . ':' . $get_current_end_time->minute . ':' . $get_current_end_time->second;
			$job->end_time = $end_time;

			$from_time = strtotime($start_time);
			$to_time = strtotime($end_time);

			if ($to_time < $from_time) {
				$to_time += 86400;
			}
			$duration = date('H:i:s', strtotime("00:00:00") + ($to_time - $from_time));
			$job->duration = $duration;

			$job->save();
		} catch (\Throwable $e) {
			$job->status_id = 7203; //Error
			$job->error_details = 'Error:' . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(); //Error
			$job->save();
			dump($job->error_details);

		}
	}

}
