<?php
namespace Abs\ImportCronJobPkg\Database\Seeds;

use App\Company;
use App\Config;
use App\ImportCronJobPkg\ImportCronJob;
use DB;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class PkgManualImportJobSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		DB::beginTransaction();
		$faker = Faker::create();

		global $company_id;

		if (!$company_id) {
			$company_id = $this->command->ask("Enter company id", '3');
			$company = Company::findOrFail($company_id);
		}

		$admin = $company->admin();

		$types_headers = ['ID', 'Import Job Type'];
		$types = App\Config::where('config_type_id', 7007)->select(['id', 'name'])->get()->toArray();
		$type_ids = App\Config::where('config_type_id', 7007)->pluck('id')->toArray();
		$this->command->table($types_headers, $types);
		$type_id = $this->command->anticipate("Enter Import Job Type ID", $type_ids);

		$import_types = [
			7181 => [
				'destination' => 'public/file-imports/coupon-codes/',
				'file_name' => 'coupon_codes',
			],
		];

		$destination_folder = $import_types[$type_id]['destination'];
		$src_file = $this->command->ask("Enter file name", 'cc1');
		// $no_of_items = $this->command->ask("Enter No of Warrnty Policy Details", '1');

		$headers = Excel::load($destination_folder . $src_file . '.xlsx', function ($reader) {
			$reader->takeRows(1);
		})->toArray();
		$headers[0] = array_filter($headers[0]);

		dd($headers[0]);

		$timetamp = date('Y_m_d_H_i_s');
		Storage::makeDirectory($destination, 0777);
		$output_file = $timetamp . '_' . $import_types[$type_id]['file_name'] . '_output_file';
		Excel::create($output_file, function ($excel) use ($headers) {
			$excel->sheet('Error Details', function ($sheet) use ($headers) {
				$headings = array_keys($headers[0]);
				$headings[] = 'Error No';
				$headings[] = 'Error Details';
				$sheet->fromArray(array($headings));
			});
		})->store('xlsx', storage_path('app/' . $destination));

		$total_records = Excel::load('storage/app/' . $destination . $src_file, function ($reader) {
			$reader->limitColumns(1);
		})->get();
		$total_records = count($total_records);

		$import_job = new ImportCronJob;
		$import_job->company_id = $company_id;
		$import_job->type_id = $type_id; //Supplier Batch Serial Number Import
		$import_job->status_id = 7200; //PENDING
		$import_job->total_record_count = $total_records;
		$import_job->src_file = $destination . $src_file;
		$import_job->output_file = $destination . $output_file . '.xlsx';
		$import_job->created_by_id = Auth::user()->id;
		$import_job->save();

		DB::commit();
	}
}
