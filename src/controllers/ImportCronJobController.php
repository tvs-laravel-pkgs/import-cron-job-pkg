<?php

namespace Abs\ImportCronJobPkg;
use Abs\ImportCronJobPkg\ImportCronJob;
use App\Address;
use App\Country;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ImportCronJobController extends Controller {

	public function __construct() {
	}

	public function getImportCronJobList(Request $request) {
		$ImportCronJob_list = ImportCronJob::withTrashed()
			->select(
				'ImportCronJobs.id',
				'ImportCronJobs.code',
				'ImportCronJobs.name',
				DB::raw('IF(ImportCronJobs.mobile_no IS NULL,"--",ImportCronJobs.mobile_no) as mobile_no'),
				DB::raw('IF(ImportCronJobs.email IS NULL,"--",ImportCronJobs.email) as email'),
				DB::raw('IF(ImportCronJobs.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('ImportCronJobs.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->ImportCronJob_code)) {
					$query->where('ImportCronJobs.code', 'LIKE', '%' . $request->ImportCronJob_code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->ImportCronJob_name)) {
					$query->where('ImportCronJobs.name', 'LIKE', '%' . $request->ImportCronJob_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_no)) {
					$query->where('ImportCronJobs.mobile_no', 'LIKE', '%' . $request->mobile_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('ImportCronJobs.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->orderby('ImportCronJobs.id', 'desc');

		return Datatables::of($ImportCronJob_list)
			->addColumn('code', function ($ImportCronJob_list) {
				$status = $ImportCronJob_list->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $ImportCronJob_list->code;
			})
			->addColumn('action', function ($ImportCronJob_list) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/ImportCronJob-pkg/ImportCronJob/edit/' . $ImportCronJob_list->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_ImportCronJob"
					onclick="angular.element(this).scope().deleteImportCronJob(' . $ImportCronJob_list->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
	}

	public function getImportCronJobFormData($id = NULL) {
		if (!$id) {
			$ImportCronJob = new ImportCronJob;
			$address = new Address;
			$action = 'Add';
		} else {
			$ImportCronJob = ImportCronJob::withTrashed()->find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['ImportCronJob'] = $ImportCronJob;
		$this->data['address'] = $address;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveImportCronJob(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'ImportCronJob Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'name.required' => 'ImportCronJob Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'gst_number.required' => 'GST Number is Required',
				'gst_number.max' => 'Maximum 191 Numbers',
				'mobile_no.max' => 'Maximum 25 Numbers',
				// 'email.required' => 'Email is Required',
				'address_line1.required' => 'Address Line 1 is Required',
				'address_line1.max' => 'Maximum 255 Characters',
				'address_line1.min' => 'Minimum 3 Characters',
				'address_line2.max' => 'Maximum 255 Characters',
				'pincode.required' => 'Pincode is Required',
				'pincode.max' => 'Maximum 6 Characters',
				'pincode.min' => 'Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => 'required|max:255|min:3',
				'name' => 'required|max:255|min:3',
				'gst_number' => 'required|max:191',
				'mobile_no' => 'nullable|max:25',
				// 'email' => 'nullable',
				'address_line1' => 'required|max:255|min:3',
				'address_line2' => 'max:255',
				'pincode' => 'required|max:6|min:6',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$ImportCronJob = new ImportCronJob;
				$ImportCronJob->created_by_id = Auth::user()->id;
				$ImportCronJob->created_at = Carbon::now();
				$ImportCronJob->updated_at = NULL;
				$address = new Address;
			} else {
				$ImportCronJob = ImportCronJob::withTrashed()->find($request->id);
				$ImportCronJob->updated_by_id = Auth::user()->id;
				$ImportCronJob->updated_at = Carbon::now();
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
			}
			$ImportCronJob->fill($request->all());
			$ImportCronJob->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$ImportCronJob->deleted_at = Carbon::now();
				$ImportCronJob->deleted_by_id = Auth::user()->id;
			} else {
				$ImportCronJob->deleted_by_id = NULL;
				$ImportCronJob->deleted_at = NULL;
			}
			$ImportCronJob->gst_number = $request->gst_number;
			$ImportCronJob->save();

			if (!$address) {
				$address = new Address;

			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24;
			$address->entity_id = $ImportCronJob->id;
			$address->address_type_id = 40;
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['ImportCronJob Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['ImportCronJob Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteImportCronJob($id) {
		$delete_status = ImportCronJob::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
