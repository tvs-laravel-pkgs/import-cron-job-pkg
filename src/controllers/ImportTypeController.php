<?php

namespace Abs\ImportTypePkg;
use Abs\ImportTypePkg\ImportType;
use App\Address;
use App\Country;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ImportTypeController extends Controller {

	public function __construct() {
	}

	public function getImportTypeList(Request $request) {
		$import_types = ImportType::withTrashed()
			->select(
				'import_types.id',
				'import_types.code',
				'import_types.name',
				DB::raw('IF(import_types.mobile_no IS NULL,"--",import_types.mobile_no) as mobile_no'),
				DB::raw('IF(import_types.email IS NULL,"--",import_types.email) as email'),
				DB::raw('IF(import_types.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('import_types.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->import_type_code)) {
					$query->where('import_types.code', 'LIKE', '%' . $request->import_type_code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->import_type_name)) {
					$query->where('import_types.name', 'LIKE', '%' . $request->import_type_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_no)) {
					$query->where('import_types.mobile_no', 'LIKE', '%' . $request->mobile_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('import_types.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->orderby('import_types.id', 'desc');

		return Datatables::of($import_types)
			->addColumn('code', function ($import_type) {
				$status = $import_type->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $import_type->code;
			})
			->addColumn('action', function ($import_type) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/import_type-pkg/import_type/edit/' . $import_type->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_import_type"
					onclick="angular.element(this).scope().deleteImportType(' . $import_type->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
	}

	public function getImportTypeFormData($id = NULL) {
		if (!$id) {
			$import_type = new ImportType;
			$address = new Address;
			$action = 'Add';
		} else {
			$import_type = ImportType::withTrashed()->find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			if (!$address) {
				$address = new Address;
			}
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['import_type'] = $import_type;
		$this->data['address'] = $address;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveImportType(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'ImportType Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'code.unique' => 'ImportType Code is already taken',
				'name.required' => 'ImportType Name is Required',
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
				// 'pincode.required' => 'Pincode is Required',
				// 'pincode.max' => 'Maximum 6 Characters',
				// 'pincode.min' => 'Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:import_types,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => 'required|max:255|min:3',
				'gst_number' => 'required|max:191',
				'mobile_no' => 'nullable|max:25',
				// 'email' => 'nullable',
				'address' => 'required',
				'address_line1' => 'required|max:255|min:3',
				'address_line2' => 'max:255',
				// 'pincode' => 'required|max:6|min:6',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$import_type = new ImportType;
				$import_type->created_by_id = Auth::user()->id;
				$import_type->created_at = Carbon::now();
				$import_type->updated_at = NULL;
				$address = new Address;
			} else {
				$import_type = ImportType::withTrashed()->find($request->id);
				$import_type->updated_by_id = Auth::user()->id;
				$import_type->updated_at = Carbon::now();
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
			}
			$import_type->fill($request->all());
			$import_type->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$import_type->deleted_at = Carbon::now();
				$import_type->deleted_by_id = Auth::user()->id;
			} else {
				$import_type->deleted_by_id = NULL;
				$import_type->deleted_at = NULL;
			}
			$import_type->gst_number = $request->gst_number;
			$import_type->axapta_location_id = $request->axapta_location_id;
			$import_type->save();

			if (!$address) {
				$address = new Address;
			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24;
			$address->entity_id = $import_type->id;
			$address->address_type_id = 40;
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['ImportType Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['ImportType Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteImportType($id) {
		$delete_status = ImportType::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
