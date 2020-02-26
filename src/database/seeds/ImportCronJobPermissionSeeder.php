<?php
namespace Abs\ImportCronJobPkg\Database\Seeds;

use App\Config;
use App\ConfigType;
use App\Permission;
use Illuminate\Database\Seeder;

class ImportCronJobPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//IMPORT STATUSES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'import-cron-jobs',
				'display_name' => 'Import Status',
			],
			[
				'display_order' => 1,
				'parent' => 'import-cron-jobs',
				'name' => 'view-all-import-cron-job',
				'display_name' => 'View All',
			],
			[
				'display_order' => 2,
				'parent' => 'import-cron-jobs',
				'name' => 'view-own-import-cron-job',
				'display_name' => 'View Own Only',
			],
			[
				'display_order' => 3,
				'parent' => 'import-cron-jobs',
				'name' => 'delete-import-cron-job',
				'display_name' => 'Delete',
			],
			[
				'display_order' => 4,
				'parent' => 'import-cron-jobs',
				'name' => 'execute-import-cron-job',
				'display_name' => 'Execute',
			],

			//IMPORT TYPES
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'import-types',
				'display_name' => 'Import Types',
			],
			[
				'display_order' => 1,
				'parent' => 'import-types',
				'name' => 'add-import-type',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'import-types',
				'name' => 'edit-import-type',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'import-types',
				'name' => 'delete-import-type',
				'display_name' => 'Delete',
			],

		];

		Permission::createFromArrays($permissions);

		$config_types = [
			7008 => 'Import Job Statuses',
		];

		$configs = [

			//IMPORT JOB STATUSES
			7200 => [
				'name' => 'Pending',
				// 'entity_type_id' => 7008,
				'config_type_id' => 7008,
			],
			7201 => [
				'name' => 'Inprogress',
				// 'entity_type_id' => 7008,
				'config_type_id' => 7008,
			],
			7202 => [
				'name' => 'Completed',
				// 'entity_type_id' => 7008,
				'config_type_id' => 7008,
			],
			7203 => [
				'name' => 'Error',
				// 'entity_type_id' => 7008,
				'config_type_id' => 7008,
			],
			7204 => [
				'name' => 'Calculating Total Records',
				// 'entity_type_id' => 7008,
				'config_type_id' => 7008,
			],
		];

		foreach ($config_types as $config_type_id => $config_type_name) {
			$config_type = ConfigType::firstOrNew([
				'id' => $config_type_id,
			]);
			$config_type->name = $config_type_name;
			$config_type->save();
		}

		foreach ($configs as $id => $config_data) {
			$config = Config::firstOrNew([
				'id' => $id,
			]);
			$config->fill($config_data);
			$config->save();
		}

	}
}