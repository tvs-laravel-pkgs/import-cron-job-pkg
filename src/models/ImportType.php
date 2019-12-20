<?php

namespace Abs\ImportCronJobPkg;

use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;

class ImportType extends Model {
	protected $table = 'import_types';
	public $timestamps = false;
	protected $fillable = [
		'name',
		'folder_path',
		'file_name',
		'action',
		'permission',
		'template_file',
	];

	public function columns() {
		return $this->hasMany('Abs\ImportCronJobPkg\ImportTypeColumn', 'import_type_id');
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

	public static function createMultipleFromArray($items) {

		foreach ($items as $id => $item) {
			$errors = [];
			$record = self::firstOrNew([
				'id' => $id,
			]);
			$record->fill($item['data']);
			$record->save();

			foreach ($item['columns'] as $column) {
				$import_type_column = ImportTypeColumn::firstOrNew([
					'company_id' => $column['company_id'],
					'import_type_id' => $record->id,
					'default_column_name' => $column['default_column_name'],
				]);
				$import_type_column->fill($column);
				$import_type_column->save();
			}
		}
	}

}
