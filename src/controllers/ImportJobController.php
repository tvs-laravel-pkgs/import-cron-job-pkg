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
				'import_jobs.id',
				'import_jobs.entity_id as entity',
				'import_jobs.total_record_count',
				'import_jobs.processed_count',
				'import_jobs.remaining_count',
				'import_jobs.new_count',
				'import_jobs.updated_count',
				'import_jobs.status_id',
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
			$import_jobs = $import_jobs->where('import_jobs.created_by_id', Auth::id());
		}

		return Datatables::of($import_jobs)
			->addColumn('action', function ($import_jobs) {
				$delete = asset('/public/img/content/table/delete-default.svg');
				$delete_active = asset('/public/img/content/table/delete-active.svg');

				return '<a href="javascript:;" data-toggle="modal" data-target="#delete_import_job"
					onclick="angular.element(this).scope().deleteImportJob(' . $import_jobs->id . ')" dusk = "delete-btn" title="Delete"><img src="' . $delete . '" alt="Delete" class="img-responsive" onmouseover=this.src="' . $delete_active . '" onmouseout=this.src="' . $delete . '" >
					</a>';
			})
			->addColumn('src_file', function ($import_jobs) {
				return '<a href="storage/app/' . $import_jobs->src_file . '">Download</a>';
			})
			->addColumn('output_file', function ($import_jobs) {
				return '<a href="storage/app/' . $import_jobs->output_file . '">Download</a>';
			})
			->addColumn('error_details', function ($import_jobs) {
				$color = "color-red";
				return '<span class="' . $color . '">' . wordwrap($import_jobs->error_details, 20, "<br>", true) . '</span>';
			})
			->addColumn('status', function ($import_jobs) {
				//PENDING
				if ($import_jobs->status_id == 7200) {
					$color = "color-warning";
				} elseif ($import_jobs->status_id == 7202) {
					//COMPLETED
					$color = "color-green";
				} elseif ($import_jobs->status_id == 7203) {
					//ERROR
					$color = "color-red";
				} else {
					//OTHER
					$color = "color-blue";
				}
				return '<span class="' . $color . '">' . $import_jobs->status . '</span>';
			})
			->make(true);
	}

	public function getImportJobFormData($id) {
		$this->data['impoty_type'] = $impoty_type = ImportType::find($id);

		return response()->json($this->data);
	}

	public function saveImportCronJob(Request $r) {
		return ImportCronJob::createImportJob($r);
	}

	public function deleteImportJob($id) {
		$delete_status = ImportCronJob::where('id', $id)->forceDelete();
		if ($delete_status) {
			return response()->json(['success' => true]);
		}
	}
}
