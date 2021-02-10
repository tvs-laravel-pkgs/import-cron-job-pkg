<?php

namespace Abs\ImportCronJobPkg;

use App\Company;
use App\Models\Config;
use App\Models\ImportJob;
use Auth;
use Carbon\Carbon;
use DB;
use Excel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use PHPExcel_IOFactory;
use Validator;
use \Abs\BasicPkg\Models\BaseModel;

class ImportCronJob extends BaseModel {
	use SoftDeletes;
	protected $table = 'import_jobs';
	protected $fillable = [
		'code',
		'name',
		'cust_group',
		'dimension',
		'mobile_no',
		'email',
		'company_id',
	];

	// Custom attributes specified in this array will be appended to model
	protected $appends = [
		'src_file_url',
		'output_file_url',
	];

	// Dynamic Attributes -------------------------
	public function getSrcFileNameAttribute() {
		return basename($this->src_file);
	}

	public function getOutputFileNameAttribute() {
		return basename($this->output_file);
	}

	public function getSrcFileUrlAttribute() {
		return url($this->src_file);
	}

	public function getOutputFileUrlAttribute() {
		return url($this->output_file);
	}

	// Relationships to auto load -------------------------
	public static function relationships($action = '', $format = ''): array
	{
		$relationships = [];

		if ($action === 'index') {
			$relationships = array_merge($relationships, [
				'status',
			]);
		} else if ($action === 'read') {
			$relationships = array_merge($relationships, [
				'status',
				'type',
			]);
		} else if ($action === 'save') {
			$relationships = array_merge($relationships, [
				// 'accountable',
				// 'status',
				// 'type',
				// 'transactions',
			]);
		} else if ($action === 'options') {
			$relationships = array_merge($relationships, [
				// 'accountable',
				//'accountable.address',
				//'accountable.address.city',
			]);
		}

		return $relationships;
	}

	// Relations ------------------------------------

	public function type() {
		return $this->belongsTo('Abs\ImportCronJobPkg\ImportType', 'type_id');
	}

	public function status() {
		return $this->belongsTo(Config::class, 'status_id');
	}

	// Static operations ------------------------------------

	public static function createFromObject($record_data) {

		$errors = [];
		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
		if (!$type) {
			$errors[] = 'Invalid Tax Type : ' . $record_data->type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data->tax_name,
		]);
		$record->type_id = $type->id;
		$record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}

	public static function createFromCollection($records) {
		foreach ($records as $key => $record_data) {
			try {
				if (!$record_data->company) {
					continue;
				}
				$record = self::createFromObject($record_data);
			} catch (Exception $e) {
				dd($e);
			}
		}
	}

	public static function createImportJob(Request $r) {
		try {
			$validator = Validator::make($r->all(), [
				'type_id' => [
					'required:true',
				],
				'entity_id' => [
					'nullable',
					'numeric',
				],
				'excel_file' => [
					'required:true',
				],
			]);

			if ($validator->fails()) {
				return [
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				];
			}
			//dump('in');

			$import_type = ImportType::find($r->type_id);
			if (!$import_type) {
				return [
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Invalid Import Type',
					],
				];
			}
			//dump($import_type);
			ini_set('max_execution_time', 0);
			ini_set('memory_limit', '-1');
			$attachment = 'excel_file';
			$attachment_extension = $r->file($attachment)->getClientOriginalExtension();
			// dd($attachment_extension);
			if ($attachment_extension != "xlsx" && $attachment_extension != "xls") {
				$response = [
					'success' => false,
					'errors' => [
						'Invalid file format, Please Import Excel Format File',
					],
				];
				return $response;
			}
			$file = $r->file($attachment)->getRealPath();

			$number_columns = $import_type->columns()->count('id');
			if ($number_columns != 0) {
				$objPHPExcel = PHPExcel_IOFactory::load($file);
				$sheet = $objPHPExcel->getSheet($import_type->sheet_index);

				$column_range = self::getNameFromNumber($number_columns);
				$header = $sheet->rangeToArray('A1:' . $column_range . '1', NULL, TRUE, FALSE);
				$header = $header[0];

				foreach ($header as $key => $column) {
					$empty_columns = [];
					if ($column == NULL) {
						$empty_columns[] = $key;
						unset($header[$key]);
					}
				}

				$columns = $import_type->columns()->where('is_required', 1)->pluck('excel_column_name');
				$mandatory_fields = $columns;
				// dd($mandatory_fields, $header);
				$missing_fields = [];
				foreach ($mandatory_fields as $mandatory_field) {
					if (!in_array($mandatory_field, $header)) {
						$missing_fields[] = $mandatory_field;
					}
				}
				if (count($missing_fields) > 0) {
					$response = [
						'success' => false,
						'message' => "Invalid Data, Mandatory fields are missing.",
						'errors' => $missing_fields,
					];
					return $response;
				}
			}

			DB::beginTransaction();
			$import_job = new ImportCronJob;
			// $import_job->company_id = Auth::user()->company_id;
			$import_job->company_id = 1;
			$import_job->type_id = $import_type->id;
			$import_job->status_id = 7200; //PENDING
			$import_job->entity_id = $r->entity_id ? $r->entity_id : '';
			$import_job->total_record_count = 0;
			$import_job->src_file = '';
			$import_job->output_file = '';
			$import_job->created_by_id = Auth::user()->id;
			$import_job->save();

			//STORING UPLOADED EXCEL FILE
			$destination = $import_type->folder_path;
			$src_file_name = $import_job->id . '-src.' . $attachment_extension;
			Storage::makeDirectory($destination, 0777);
			$r->file($attachment)->storeAs($destination, $src_file_name);

			try {
				//CALCULATING TOTAL RECORDS
				$total_records = Excel::load('storage/app/' . $destination . $src_file_name, function ($reader) {
					$reader->limitColumns(1);
				})->get();
			} catch (\Exception $e) {

				$total_records = [];
			}
			$total_records = count($total_records);
			$import_job->src_file = $destination . $src_file_name;
			$import_job->output_file = $destination . $import_job->id . '-report.xlsx';
			$import_job->total_record_count = $total_records;
			$import_job->save();

			//CREATING & STORING OUTPUT EXCEL FILE
			// $output_file = $timetamp . '-output-file';
			// Excel::create($output_file, function ($excel) use ($header) {
			// 	$excel->sheet('Error Details', function ($sheet) use ($header) {
			// 		// $headings = array_keys($header);
			// 		// $headings[] = 'Error No';
			// 		// $headings[] = 'Error Details';
			// 		// $sheet->fromArray(array($headings));
			// 	});
			// })->store('xlsx', storage_path('app/' . $destination));
			DB::commit();

			return [
				'success' => true,
				'message' => 'File added to import queue successfully',
			];
		} catch (\Exception $e) {
			DB::rollBack();

			return [
				'success' => false,
				'errors' => [
					$e->getMessage(),
				],
			];
		}
	}

	public static function createImportJobFromArray($input): array
	{
		try {
			$validator = Validator::make($input, [
				'import_type_code' => [
					'required:true',
				],
				'entity_id' => [
					'nullable',
					'numeric',
				],
				'file_name' => [
					'required:true',
				],
			]);

			if ($validator->fails()) {
				return [
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				];
			}
			//dump('in');

			$import_type = ImportType::where('code', $input['import_type_code'])->first();
			if (!$import_type) {
				return [
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Invalid Import Type',
					],
				];
			}
			//dump($import_type);
			ini_set('max_execution_time', 0);
			ini_set('memory_limit', '-1');
			//$attachment = 'excel_file';

			$attachment_extension = pathinfo($input['file_name'], PATHINFO_EXTENSION);
			if ($attachment_extension != "xlsx" && $attachment_extension != "xls") {
				$response = [
					'success' => false,
					'errors' => [
						'Invalid file format, Please Import Excel Format File',
					],
				];
				return $response;
			}
			$fileName = Arr::get($input, 'file_name');
			$filePath = public_path('files/' . $fileName);

			$number_columns = $import_type->columns()->count('id');
			if ($number_columns != 0) {
				$objPHPExcel = PHPExcel_IOFactory::load($filePath);
				$sheet = $objPHPExcel->getSheet($import_type->sheet_index);

				$column_range = self::getNameFromNumber($number_columns);
				$header = $sheet->rangeToArray('A1:' . $column_range . '1', NULL, TRUE, FALSE);
				$header = $header[0];

				foreach ($header as $key => $column) {
					$empty_columns = [];
					if ($column == NULL) {
						$empty_columns[] = $key;
						unset($header[$key]);
					}
				}

				$columns = $import_type->columns()->where('is_required', 1)->pluck('excel_column_name');
				$mandatory_fields = $columns;
				// dd($mandatory_fields, $header);
				$missing_fields = [];
				foreach ($mandatory_fields as $mandatory_field) {
					if (!in_array($mandatory_field, $header)) {
						$missing_fields[] = $mandatory_field;
					}
				}
				if (count($missing_fields) > 0) {
					$response = [
						'success' => false,
						'message' => "Invalid Data, Mandatory fields are missing.",
						'errors' => $missing_fields,
					];
					return $response;
				}
			}

			DB::beginTransaction();
			$import_job = new ImportCronJob;
			$import_job->company_id = \Illuminate\Support\Facades\Auth::user()->company_id;
			//$import_job->company_id = 1;
			$import_job->type_id = $import_type->id;
			$import_job->status_id = 7200; //PENDING
			$import_job->entity_id = Arr::get($input, 'entity_id', '');
			$import_job->total_record_count = Arr::get($input, 'total_record_count', '0');
			$import_job->src_file = '';
			$import_job->output_file = '';
			$import_job->created_by_id = Auth::user()->id;
			//$import_job->save();

			//STORING UPLOADED EXCEL FILE
			$destination = $import_type->folder_path;
			$srcFileName = 'public/files/' . $fileName;
			$import_job->src_file = $srcFileName;

			if ($import_job->total_record_count != 0) {
				try {
					//CALCULATING TOTAL RECORDS
					$total_records = Excel::load($filePath, function ($reader) {
						$reader->limitColumns(1);
					})->get();
				} catch (\Exception $e) {

					$total_records = [];
				}
				$import_job->total_record_count = count($total_records);
			}
			$import_job->remaining_count = $import_job->total_record_count;
			$import_job->save();
			$import_job->output_file = 'public/files/' . $import_job->id . '-report.xlsx';
			$import_job->save();

			DB::commit();

			return [
				'success' => true,
				'message' => 'File added to import queue successfully',
				'importJob' => $import_job,
			];
		} catch (\Exception $e) {
			DB::rollBack();

			return [
				'success' => false,
				'errors' => [
					$e->getMessage(),
				],
			];
		}
	}

	public static function getNameFromNumber($num) {
		$numeric = ($num - 1) % 26;
		$letter = chr(65 + ($numeric + 1));
		$num2 = intval(($num - 1) / 26);
		if ($num2 > 0) {
			return self::getNameFromNumber($num2) . $letter;
		} else {
			return $letter;
		}
	}

	public function incrementNew() {
		$this->new_count++;
		$this->remaining_count--;
		$this->processed_count++;
	}

	public function incrementError() {
		$this->error_count++;
		$this->remaining_count--;
		$this->processed_count++;
	}

	public static function getRecordsFromExcel($job, $max_col, $sheet_number = 0, $useStoragePath = true) {
		//READING EXCEL FILE
		if ($useStoragePath) {
			$objPHPExcel = PHPExcel_IOFactory::load(storage_path('app/' . $job->src_file));
		} else {
			$objPHPExcel = PHPExcel_IOFactory::load(public_path('../' . $job->src_file));
		}
		$sheet = $objPHPExcel->getSheet($sheet_number);
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

	public static function generateImportReport($params, $useStorage = true) {
		$job = $params['job'];
		$all_error_records = $params['all_error_records'];
		if (count($all_error_records) > 0) {
			$reportExcel = Excel::create($job->id . '-report', function ($excel) use ($all_error_records, $job) {
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
			});
			if ($useStorage) {
				$reportExcel->store('xlsx', storage_path('app/' . $job->type->folder_path));
			} else {
				$reportExcel->store('xlsx', public_path('files/'));
			}
		}

	}

	public static function start($job) {
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
		return $job;
	}

	// Query scopes --------------------------------------------------------------
	public function scopeFilterEntityId($query, $entityId) {
		$query->where('entity_id', $entityId);
	}

	public function scopeFilterType($query, $type) {
		$typeId = $type instanceof ImportJob ? $type->id : $type;
		$query->where('entity_id', $typeId);
	}

	public function scopeFilterByTypeCode($query, $typeCode) {
		$typeCode = $typeCode instanceof ImportJob ? $typeCode->code : $typeCode;
		$query->whereHas('type', function ($query) use ($typeCode) {
			$query->where('code', '=', $typeCode);
		});
	}

	public function scopeFilterOrderBy($query, $orderBy) {
		if (!empty($orderBy)) {
			$query->orderBy('id', 'DESC');
		}
	}
}
