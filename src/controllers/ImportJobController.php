<?php

namespace Abs\ImportCronJobPkg;
use Abs\ImportCronJobPkg\ImportCronJob;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class ImportJobController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getImportCronJobList(Request $request) {
		$import_jobs = ImportCronJob::
			join('import_types as type', 'type.id', '=', 'import_jobs.type_id')
			->leftjoin('configs as status', 'status.id', '=', 'import_jobs.status_id')
			->leftjoin('users as cb', 'cb.id', '=', 'import_jobs.created_by_id')
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
				DB::raw('DATE_FORMAT(import_jobs.start_time,"%h:%i:%s %p") as start_time'),
				DB::raw('DATE_FORMAT(import_jobs.end_time,"%h:%i:%s %p") as end_time'),
				'import_jobs.duration',
				'cb.name as created_by'
			)
		// ->where('import_jobs.company_id', Auth::user()->company_id)
			->where('import_jobs.company_id', 1)
			->orderBy('import_jobs.created_at', 'DESC')
		;

		if (!Entrust::can('view-all-import-cron-job')) {
			$import_jobs = $import_jobs->where('import_jobs.created_by_id', Auth::id());
		}

		return Datatables::of($import_jobs)
			->addColumn('action', function ($import_jobs) {
				$source = asset('/public/themes/' . $this->data['theme'] . '/img/content/icons/upload_normal.svg');
				$source_active = asset('/public/themes/' . $this->data['theme'] . '/img/content/icons/upload_hover.svg');
				$error = asset('/public/themes/' . $this->data['theme'] . '/img/content/icons/error_normal.svg');
				$error_active = asset('/public/themes/' . $this->data['theme'] . '/img/content/icons/error_hover.svg');
				$delete = asset('/public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$delete_active = asset('/public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');

				$action = '<a href="storage/app/' . $import_jobs->src_file . '" title="Source File"><img src="' . $source . '" alt="Source File" class="img-responsive" onmouseover=this.src="' . $source_active . '" onmouseout=this.src="' . $source . '" ></a>
					<a href="storage/app/' . $import_jobs->output_file . '" title="Error Report"><img src="' . $error . '" alt="Error File" class="img-responsive" onmouseover=this.src="' . $error_active . '" onmouseout=this.src="' . $error . '" ></a>
					'
				;
				if (Entrust::can('delete-import-cron-job')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_import_job"
					onclick="angular.element(this).scope().deleteImportJob(' . $import_jobs->id . ')" dusk = "delete-btn" title="Delete"><img src="' . $delete . '" alt="Delete" class="img-responsive" onmouseover=this.src="' . $delete_active . '" onmouseout=this.src="' . $delete . '" >
					</a>';
				}
				return $action;
			})
		// ->addColumn('src_file', function ($import_jobs) {
		// 	return '<a href="storage/app/' . $import_jobs->src_file . '">Download</a>';
		// })
		// ->addColumn('output_file', function ($import_jobs) {
		// 	return '<a href="storage/app/' . $import_jobs->output_file . '">Download</a>';
		// })
			->addColumn('error_details_tooltip', function ($import_jobs) {
				return $import_jobs->error_details;
			})
			->addColumn('error_details', function ($import_jobs) {
				$color = "color-red";
				$error_details = $import_jobs->error_details;
				if (!empty($import_jobs->error_details)) {
					return '<a href="#!" class="' . $color . '">' . (strlen($error_details) > 20) ? substr($error_details, 0, 20) . '...' : $error_details . '</a>';
				} else {
					return '';
				}
				// return '<span class="' . $color . '">' . wordwrap($error_details, 30, "<br>", true) . '</span>';
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
			->rawColumns(['action', 'error_details', 'status'])
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
