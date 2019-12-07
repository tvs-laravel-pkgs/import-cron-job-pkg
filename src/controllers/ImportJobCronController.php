<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\ImportJob;
use App\Sticker;
use App\SupplierBatchItem;
use DB;
use Excel;
use Illuminate\Support\Facades\Storage;
use PHPExcel_IOFactory;

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

		//READING EXCEL FILE
		$objPHPExcel = PHPExcel_IOFactory::load('storage/app/' . $job->src_file);
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestDataRow();
		// dd($job);
		if ($job->type_id == 7180) {
			DB::beginTransaction();
			//SUPPLIER BATCH SERIAL NUMBER IMPORT
			// $job->status_id = 7204; //Calculating Total Records
			$job->save();

			$header = $sheet->rangeToArray('A1:BZ1', NULL, TRUE, FALSE);
			$header = $header[0];

			foreach ($header as $key => $column) {
				$empty_columns = [];
				if ($column == NULL) {
					$empty_columns[] = $key;
					unset($header[$key]);
				}
			}
			$rows = $sheet->rangeToArray('A2:' . 'BZ' . $highestRow, NULL, TRUE, FALSE);
			$total_records = $highestRow - 1;
			$job->total_record_count = $total_records;
			$job->remaining_count = $total_records;
			// $job->status_id = 7201; //Inprogress
			$job->save();
			// dump($total_records);
			$all_error_records = [];
			$error_msg = $status = $records = '';
			Storage::makeDirectory('public/qr-stickers', 0777);

			$sr_no = 0;
			$batch_item_init = true;
			// dd($rows);
			foreach ($rows as $k => $row) {
				// DB::beginTransaction();
				$record = [];
				foreach ($header as $key => $column) {
					if (!$column) {
						continue;
					} else {
						$record[$column] = trim($row[$key]);
					}
				}

				$original_record = $record;

				// $status = Vendor::validate_import_record($k, $record, $mandatory_columns, $job);
				$status = [];
				$status['errors'] = [];
				$batch_item = SupplierBatchItem::with([
					'poItem',
					'item',
					'supplierBatch',
				])
					->withCount('stickers')
					->find($record['Batch Line ID']);
				if (!$batch_item) {
					$status['errors'][] = 'Batch Line not found';
				}
				if (empty($record['Serial Number'])) {
					$status['errors'][] = 'Serial Number not found';
				}

				$sticker = Sticker::where('encrypted_qr_code', $record['Serial Number'])->first();
				if ($sticker) {
					$status['errors'][] = 'Duplicate Serial Number';
				}
				if ($batch_item) {
					if ($batch_item->item->code != $record['Item Code']) {
						$status['errors'][] = 'Item code not matched with Batch Line';
					}

					if ($batch_item->stickers_count >= $batch_item->qty) {
						$status['errors'][] = 'Excess Serial Number';
					}
				}

				if (count($status['errors']) > 0) {
					// dump($status['errors']);
					$original_record['Record No'] = $k + 1;
					$original_record['Error Details'] = implode(',', $status['errors']);
					$all_error_records[] = $original_record;
					$error_count++;
					continue;
				}

				//ASSIGN BATCH ID INITIALLY
				if ($batch_item_init) {
					$batch_item_id = $record['Batch Line ID'];
					$batch_item_init = false;
				}

				//ASSIGN SR_NO 0 FOR THE NEXT SET OF BATCH IDs
				if ($batch_item_id != $record['Batch Line ID']) {
					//CHECK IF SERIAL NUMBERS ARE PARTIALLY UPDATED - THEN TAKE SR NO
					$check_serial_number_partially_updated = Sticker::where('type', 400)->where('supplier_batch_id', $batch_item->supplier_batch_id)->where('supplier_batch_item_id', $batch_item->id)->where('item_id', $batch_item->item_id)->orderBy('sr_no', 'desc')->first();
					if ($check_serial_number_partially_updated) {
						$sr_no = $check_serial_number_partially_updated->sr_no;
					} else {
						$sr_no = 0;
					}
					$batch_item_id = $record['Batch Line ID'];
				} else {
					//CHECK IF SERIAL NUMBERS ARE PARTIALLY UPDATED - THEN TAKE SR NO
					$check_serial_number_partially_updated = Sticker::where('type', 400)->where('supplier_batch_id', $batch_item->supplier_batch_id)->where('supplier_batch_item_id', $batch_item->id)->where('item_id', $batch_item->item_id)->orderBy('sr_no', 'desc')->first();
					if ($check_serial_number_partially_updated) {
						$sr_no = $check_serial_number_partially_updated->sr_no;
					} else {
						$sr_no = 0;
					}
				}
				$sr_no++;

				$sticker = Sticker::create([
					'type' => 400, //ITEM
					'supplier_batch_id' => $batch_item->supplier_batch_id,
					'supplier_batch_item_id' => $batch_item->id,
					'item_id' => $batch_item->item_id,
					'qr_code' => null,
					'encrypted_qr_code' => $record['Serial Number'],
					'sr_no' => $sr_no,
					'status_id' => 18, //NEW
				]);

				$newCount++;

				//UPDATING PROGRESS FOR EVERY FIVE RECORDS
				// if (($k + 1) % 5 == 0) {
				$job->new_count = $newCount;
				$job->error_count = $error_count;
				$job->remaining_count = $total_records - ($k + 1);
				$job->processed_count = $k + 1;
				$job->save();
				// }
			}
			if (count($all_error_records) > 0) {
				$job->error_details = 'Error occured during import. Check the error report';
			}

			// $job->processed_count = $total_records;
			// $job->processed_count = 0;
			$job->new_count = $newCount;
			$job->updated_count = $updatedcount;
			$job->error_count = $error_count;
			$job->status_id = 7202; //COMPLETED
			$job->save();
			DB::commit();
			// dd($all_error_records);
			if (count($all_error_records) > 0) {
				Excel::load('storage/app/' . $job->output_file, function ($excel) use ($all_error_records, $job) {
					$excel->sheet('Error Details', function ($sheet) use ($all_error_records) {
						// dd($sheet);
						foreach ($all_error_records as $error_record) {
							$sheet->appendRow($error_record, null, 'A1', false, false);

							if (isset($error_record['Record No'])) {
								$sheet->row($sheet->getHighestRow(), function ($row) {
									//get last row at the moment and style it
									$row->setFontColor('#FF0000');
								});
							}
						}
					});
				})->store('xlsx', storage_path('app/public/file-imports/supplier-batch-serial-numbers'));
			}

		}

	}

}
