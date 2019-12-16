<?php

namespace Abs\ImportCronJobPkg;
use Abs\ImportCronJobPkg\ImportCronJob;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class ImportJobController extends Controller {

	public function __construct() {
	}

	public function getImportCronJobList(Request $request) {

		$import_jobs = ImportCronJob::
			join('configs as type', 'type.id', '=', 'import_jobs.type_id')
			->join('configs as status', 'status.id', '=', 'import_jobs.status_id')
			->join('users as cb', 'cb.id', '=', 'import_jobs.created_by_id')
			->select(
				DB::raw('DATE_FORMAT(import_jobs.created_at,"%d/%m/%Y %h:%i %p") as created'),
				'type.name as type',
				'status.name as status',
				'import_jobs.entity_id as entity',
				'import_jobs.total_record_count',
				'import_jobs.processed_count',
				'import_jobs.remaining_count',
				'import_jobs.new_count',
				'import_jobs.updated_count',
				'import_jobs.error_count',
				'import_jobs.src_file',
				'import_jobs.output_file',
				'import_jobs.error_details',
				'cb.name as created_by'
			)
			->where('import_jobs.company_id', Auth::user()->company_id)
			->orderBy('import_jobs.created_at', 'DESC')
		;

		if (!Entrust::can('view-all-import-jobs')) {
			$import_jobs->where('import_jobs.created_by_id', Auth::id());
		}

		return Datatables::of($import_jobs)
			->addColumn('src_file', function ($import_job) {
				return '<a href="storage/app/' . $import_job->src_file . '">Download</a>';
			})
			->addColumn('output_file', function ($import_job) {
				return '<a href="storage/app/' . $import_job->output_file . '">Download</a>';
			})
			->make(true);
	}

}
