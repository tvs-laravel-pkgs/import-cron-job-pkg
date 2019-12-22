<?php

namespace Abs\ImportCronJobPkg;

use Abs\ImportCronJobPkg\ImportCronJob;
use App\Http\Controllers\Controller;
use Excel;
use PHPExcel_IOFactory;

class ImportJobCronController extends Controller {

	public static function getRecordsFromExcel($job, $max_col) {
		//READING EXCEL FILE
		$objPHPExcel = PHPExcel_IOFactory::load('storage/app/' . $job->src_file);
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestDataRow();

		$header = $sheet->rangeToArray('A1:' . $max_col . '1', NULL, TRUE, FALSE);
		$header = $header[0];

		foreach ($header as $key => $column) {
			if ($column == NULL) {
				unset($header[$key]);
			}
		}
		$rows = $sheet->rangeToArray('A2:' . $max_col . $highestRow, NULL, TRUE, FALSE);
		$total_records = $highestRow - 1;
		$job->total_record_count = $total_records;
		$job->remaining_count = $total_records;
		$job->status_id = 7201; //Inprogress
		$job->save();
		return [
			'rows' => $rows,
			'header' => $header,
		];
	}

	public static function generateImportReport($params) {
		$job = $params['job'];
		$all_error_records = $params['all_error_records'];
		if (count($all_error_records) > 0) {
			Excel::load('storage/app/' . $job->output_file, function ($excel) use ($all_error_records, $job) {
				$excel->sheet('Error Details', function ($sheet) use ($all_error_records) {
					foreach ($all_error_records as $key => $error_record) {
						if ($key == 0) {
							$header = array_keys($error_record);
							$sheet->appendRow($header, null, 'A1', false, false);
						}
						$sheet->appendRow($error_record, null, 'A1', false, false);

						if (isset($error_record['Record No'])) {
							$sheet->row($sheet->getHighestRow(), function ($row) {
								//get last row at the moment and style it
								$row->setFontColor('#FF0000');
							});
						}
					}
				});
			})->store('xlsx', storage_path('' . $job->type->folder_path));
		}
		dump('Success.', $job->toArray());

	}

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
