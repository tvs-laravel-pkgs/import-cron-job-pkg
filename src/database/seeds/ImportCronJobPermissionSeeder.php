<?php
namespace Abs\ImportCronJobPkg\Database\Seeds;

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
				'display_name' => 'Import Cron Jobs',
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
	}
}