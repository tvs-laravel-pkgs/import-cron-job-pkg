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
			10200 => [
				'display_order' => 10,
				'parent_id' => null,
				'name' => 'import-cron-jobs',
				'display_name' => 'Import Status',
			],
			10201 => [
				'display_order' => 1,
				'parent_id' => 10200,
				'name' => 'view-all-import-cron-job',
				'display_name' => 'View All',
			],
			10202 => [
				'display_order' => 1,
				'parent_id' => 10200,
				'name' => 'view-own-import-cron-job',
				'display_name' => 'View Own Only',
			],
		];

		foreach ($permissions as $permission_id => $permsion) {
			$permission = Permission::firstOrNew([
				'id' => $permission_id,
			]);
			$permission->fill($permsion);
			$permission->save();
		}

		$config_types = [
			7008 => 'Import Job Statuses',
		];

		$configs = [

			//IMPORT JOB STATUSES
			7200 => [
				'name' => 'Pending',
				'entity_type_id' => 7008,
			],
			7201 => [
				'name' => 'Inprogress',
				'entity_type_id' => 7008,
			],
			7202 => [
				'name' => 'Completed',
				'entity_type_id' => 7008,
			],
			7203 => [
				'name' => 'Error',
				'entity_type_id' => 7008,
			],
			7204 => [
				'name' => 'Calculating Total Records',
				'entity_type_id' => 7008,
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