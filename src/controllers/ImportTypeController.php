<?php

namespace Abs\ImportCronJobPkg;
use Abs\ImportCronJobPkg\ImportType;
use Abs\ImportCronJobPkg\ImportTypeColumn;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ImportTypeController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getImportTypeList(Request $request) {
		$import_types = ImportType::select(
			'import_types.*',
			'import_types.action as import_type_action'
		)
			->orderby('import_types.id', 'desc');

		return Datatables::of($import_types)
			->addColumn('action', function ($import_type) {
				$edit = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$edit_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '<a href="#!/import-cron-job-pkg/import-type/edit/' . $import_type->id . '">
						<img src="' . $edit . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $edit_active . '" onmouseout=this.src="' . $edit . '" ></a>
						<a href="javascript:;" data-toggle="modal" data-target="#delete_import_type"
						onclick="angular.element(this).scope().deleteImportType(' . $import_type->id . ')" dusk = "delete-btn" title="Delete">
						<img src="' . $delete . '" alt="Delete" class="img-responsive" onmouseover=this.src="' . $delete_active . '" onmouseout=this.src="' . $delete . '" >
						</a>';
			})
			->make(true);
	}

	public function getImportTypeFormData(Request $request) {
		$id = $request->id;
		$this->data['import_type'] = $import_type = ImportType::where('id', $id)->with([
			'columns',
		])->first();
		$this->data['action'] = $action = 'Edit';
		$this->data['theme'];

		return response()->json($this->data);
	}

	public function saveImportType(Request $request) {
		//dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'ImportType is Required',
				'name.unique' => 'ImportType is already taken',
				'folder_path.required' => 'Folder Path is Required',
				'file_name.required' => 'File Name is Required',
				'file_name.unique' => 'File Name is already taken',
				'action.required' => 'Action is Required',
				'permission.required' => 'Permission is Required',
				'template_file.required' => 'Template File is Required',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required',
					'unique:import_types,name,' . $request->id . ',id',
				],
				'folder_path' => 'required',
				'file_name' => [
					'required',
					'unique:import_types,file_name,' . $request->id . ',id',
				],
				'action' => 'required',
				'permission' => 'required',
				'template_file' => 'required',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			//VALIDATE UNIQUE FOR IMPORT-TYPE-COLUMNS
			if (isset($request->columns) && !empty($request->columns)) {
				$error_messages_1 = [
					'default_column_name.required' => 'Default Column Name is required',
					'default_column_name.unique' => 'Default Column Name is already taken',
					'excel_column_name.required' => 'Excel Column Name is required',
					'excel_column_name.unique' => 'Excel Column Name is already taken',
				];

				foreach ($request->columns as $column_key => $column) {
					$validator_1 = Validator::make($column, [
						'default_column_name' => [
							'unique:import_type_columns,default_column_name,' . $column['id'] . ',id,company_id,' . Auth::user()->company_id . ',import_type_id,' . $column['import_type_id'],
							'required',
						],
						'excel_column_name' => [
							'unique:import_type_columns,excel_column_name,' . $column['id'] . ',id,company_id,' . Auth::user()->company_id . ',import_type_id,' . $column['import_type_id'],
							'required',
						],
					], $error_messages_1);

					if ($validator_1->fails()) {
						return response()->json(['success' => false, 'errors' => $validator_1->errors()->all()]);
					}

					//FIND DUPLICATE IMPORT-TYPE-COLUMNS
					foreach ($request->columns as $search_key => $search_array) {
						if ($search_array['default_column_name'] == $column['default_column_name']) {
							if ($search_key != $column_key) {
								return response()->json(['success' => false, 'errors' => ['Default Column Name is already taken']]);
							}
						}
						if ($search_array['excel_column_name'] == $column['excel_column_name']) {
							if ($search_key != $column_key) {
								return response()->json(['success' => false, 'errors' => ['Excel Column Name is already taken']]);
							}
						}
					}
				}
			}

			DB::beginTransaction();
			$import_type = ImportType::find($request->id);
			$import_type->fill($request->all());
			$import_type->save();

			//DELETE IMPORT-TYPE-COLUMNS
			if (!empty($request->import_field_removal_ids)) {
				$import_field_removal_ids = json_decode($request->import_field_removal_ids, true);
				ImportTypeColumn::withTrashed()->whereIn('id', $import_field_removal_ids)->forcedelete();
			}

			if (isset($request->columns) && !empty($request->columns)) {
				foreach ($request->columns as $key => $column) {
					$import_type_columns = ImportTypeColumn::withTrashed()->firstOrNew(['id' => $column['id']]);
					$import_type_columns->company_id = Auth::user()->company_id;
					$import_type_columns->fill($column);
					if ($column['is_required'] == "Yes") {
						$import_type_columns->is_required = 1;
					} elseif ($column['is_required'] == "No") {
						$import_type_columns->is_required = 0;
					}
					$import_type_columns->import_type_id = $import_type->id;
					if (empty($column['id'])) {
						$import_type_columns->created_by_id = Auth::user()->id;
						$import_type_columns->created_at = date('Y-m-d H:i:s');
					} else {
						$import_type_columns->updated_by_id = Auth::user()->id;
						$import_type_columns->updated_at = date('Y-m-d H:i:s');
					}
					$import_type_columns->save();
				}
			}

			DB::commit();
			return response()->json(['success' => true, 'message' => ['ImportType Details Updated Successfully']]);
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteImportType(Request $request) {
		DB::beginTransaction();
		try {
			$delete_import_type = ImportType::where('id', $request->id)->forceDelete();
			DB::commit();
			return response()->json(['success' => true, 'message' => 'Import Type deleted successfully']);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
