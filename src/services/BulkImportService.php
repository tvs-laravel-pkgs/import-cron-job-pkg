<?php

namespace Abs\ImportCronJobPkg\Services;

use App\ImportCronJob;
use App\ImportType;
use Excel;

abstract class BulkImportService {
	public static function import($job) {
		try {
			ini_set('memory_limit', -1);

			$company = $specific_company = $tc = null;

			$excel_file_path = storage_path('app/' . $job->src_file);
			$sheets = [];
			Excel::selectSheets('Import Config')->load($excel_file_path, function ($reader) use (&$sheets) {
				$reader->limitColumns(10);
				$reader->limitRows(100);
				$records = $reader->get();
				foreach ($records as $record) {
					if (!$record->sheet_name || $record->action != 'Execute') {
						continue;
					}
					$sheets[] = [
						'sheet_name' => $record->sheet_name,
						'import_type' => $record->import_type,
						'class_name' => $record->class_name,
						'function_name' => $record->function_name,
						'column_limit' => $record->column_limit,
						'skip' => $record->skip,
						'row_limit' => $record->row_limit,
					];
				}
			});

			$all_error_records = [];
			foreach ($sheets as $key => $sheet_detail) {
				$sheet_name = $sheet_detail['sheet_name'];
				dump($sheet_name . ' STARTED');
				$import_type = ImportType::where('name', $sheet_detail['import_type'])->first();
				if (!$import_type) {
					dump('Import Type not found : ' . $sheet_detail['import_type']);
					continue;
				}
				Excel::selectSheets($sheet_name)->load($excel_file_path, function ($reader) use ($sheet_name, $sheet_detail, $company, $specific_company, $tc, $import_type, $all_error_records) {
					$reader->limitColumns($sheet_detail['column_limit']);
					$reader->skipRows($sheet_detail['skip']);
					$reader->takeRows($sheet_detail['row_limit']);
					$records = $reader->get();
					$errors = call_user_func($import_type->action, $records, $company, $specific_company, $tc);
					$all_error_records = array_merge($all_error_records, $errors);
					dump($errors);
				});
				dump($sheet_name . ' COMPLETED');
			}

			//COMPLETED or completed with errors
			$job->status_id = $job->error_count == 0 ? 7202 : 7205;
			$job->save();

			ImportCronJob::generateImportReport([
				'job' => $job,
				'all_error_records' => $all_error_records,
			]);

		} catch (\Throwable $e) {
			// $job->status_id = 7203; //Error
			$job->error_details = 'Error:' . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(); //Error
			$job->save();
			dump($job->error_details);
		}
	}
}
