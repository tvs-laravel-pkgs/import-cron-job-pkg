<?php

namespace Abs\ImportCronJobPkg;

use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportTypeColumn extends Model {
	use SoftDeletes;
	protected $table = 'import_type_columns';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'import_type_id',
		'default_column_name',
		'excel_column_name',
		'is_required',
	];
	protected $appends = ['switch_value'];

	public function getSwitchValueAttribute() {
		return $this->attributes['is_required'] == 1 ? 'Yes' : 'No';
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

}
