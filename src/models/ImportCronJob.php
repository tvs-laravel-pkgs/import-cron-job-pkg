<?php

namespace Abs\ImportCronJobPkg;

use App\Company;
use App\Config;
use Auth;
use Excel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PHPExcel_IOFactory;
use Validator;

class ImportCronJob extends Model {
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

	public function type() {
		return $this->belongsTo('Abs\ImportCronJobPkg\ImportType', 'type_id');
	}

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
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => $validator->errors()->all(),
			]);
		}

		$import_type = ImportType::find($r->type_id);
		if (!$import_type) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => [
					'Invalid Import Type',
				],
			]);
		}

		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '-1');
		$attachment = 'excel_file';
		$attachment_extension = $r->file($attachment)->getClientOriginalExtension();
		if ($attachment_extension != "xlsx" && $attachment_extension != "xls") {
			$response = [
				'success' => false,
				'errors' => [
					'Invalid file format, Please Import Excel Format File',
				],
			];
			return response()->json($response);
		}
		$file = $r->file($attachment)->getRealPath();

		$objPHPExcel = PHPExcel_IOFactory::load($file);
		$sheet = $objPHPExcel->getSheet(0);
		$header = $sheet->rangeToArray('A1:F1', NULL, TRUE, FALSE);
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
				$missing_fields[] = $missing_fields;
			}
		}
		if (count($missing_fields) > 0) {
			$response = [
				'success' => false,
				'message' => "Invalid Data, Mandatory fields are missing.",
				'errors' => $missing_fields,
			];
			return response()->json($response);
		}

		//STORING UPLOADED EXCEL FILE
		$destination = str_replace('app/', '', $import_type->folder_path);
		$timetamp = date('Y_m_d_H_i_s');
		$src_file_name = $timetamp . '-src-file.' . $attachment_extension;
		Storage::makeDirectory($destination, 0777);
		$r->file($attachment)->storeAs($destination, $src_file_name);

		//CREATING & STORING OUTPUT EXCEL FILE
		$output_file = $timetamp . '-output-file';
		Excel::create($output_file, function ($excel) use ($header) {
			$excel->sheet('Error Details', function ($sheet) use ($header) {
				// $headings = array_keys($header);
				// $headings[] = 'Error No';
				// $headings[] = 'Error Details';
				// $sheet->fromArray(array($headings));
			});
		})->store('xlsx', storage_path('app/' . $destination));

		//CALCULATING TOTAL RECORDS
		$total_records = Excel::load('storage/app/' . $destination . $src_file_name, function ($reader) {
			$reader->limitColumns(1);
		})->get();
		$total_records = count($total_records);

		$import_job = new ImportCronJob;
		$import_job->company_id = Auth::user()->company_id;
		$import_job->type_id = $import_type->id;
		$import_job->status_id = 7200; //PENDING
		$import_job->entity_id = $r->entity_id ? $r->entity_id : '';
		$import_job->total_record_count = $total_records;
		$import_job->src_file = $destination . $src_file_name;
		$import_job->output_file = $destination . $output_file . '.xlsx';
		$import_job->created_by_id = Auth::user()->id;

		$import_job->save();
		return response()->json(['success' => true, 'message' => 'File added to import queue successfully']);
	}
}
