<?php
namespace Abs\ImportCronJobPkg\Database\Seeds;

use Abs\ImportCronJobPkg\ImportType;
use Illuminate\Database\Seeder;

class ImportTypePkgSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$import_types = [
			// 1 => [
			// 	'data' => [
			// 		'name' => 'Supplier Batch Serial Number Import',
			// 		'folder_path' => 'public/file-imports/supplier-batch-serial-number/',
			// 		'file_name' => 'supplier-batch-serial-number',
			// 		'action' => 'App\SupplierBatch::importFromExcel',
			// 		'permission' => 'supplier_batches',
			// 		'template_file' => 'supplier_batch_serial_number.xlsx',
			// 	],
			// 	'columns' => [
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'batch_line_id',
			// 			'excel_column_name' => 'Batch Line ID',
			// 			'is_required' => 1,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'serial_number',
			// 			'excel_column_name' => 'Serial Number',
			// 			'is_required' => 1,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'item_code',
			// 			'excel_column_name' => 'Item Code',
			// 			'is_required' => 1,
			// 		],
			// 	],
			// ],
			// 2 => [
			// 	'data' => [
			// 		'name' => 'Coupon Code Import',
			// 		'folder_path' => 'public/file-imports/coupon-codes/',
			// 		'file_name' => 'coupon-codes',
			// 		'action' => 'Abs\CouponPkg\Coupon::importFromExcel',
			// 		'permission' => 'import-coupon',
			// 		'template_file' => 'coupon_code.xlsx',
			// 	],
			// 	'columns' => [
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'coupon_code',
			// 			'excel_column_name' => 'Code',
			// 			'is_required' => 1,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'date_of_printing',
			// 			'excel_column_name' => 'Date of Printing',
			// 			'is_required' => 1,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'points',
			// 			'excel_column_name' => 'Point',
			// 			'is_required' => 1,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'pack_size',
			// 			'excel_column_name' => 'Pack Size',
			// 			'is_required' => 1,
			// 		],
			// 	],
			// ],
			// 3 => [
			// 	'data' => [
			// 		'name' => 'Repair Order Import',
			// 		'folder_path' => 'public/file-imports/repair-orders/',
			// 		'file_name' => 'repair-orders',
			// 		'action' => 'Abs\GigoPkg\RepairOrder::importFromExcel',
			// 		'permission' => 'import-repair-order',
			// 		'template_file' => 'repair_order.xlsx',
			// 	],
			// 	'columns' => [
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'type',
			// 			'excel_column_name' => 'Type',
			// 			'is_required' => 1,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'code',
			// 			'excel_column_name' => 'Code',
			// 			'is_required' => 1,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'alt_code',
			// 			'excel_column_name' => 'Alt Code',
			// 			'is_required' => 0,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'name',
			// 			'excel_column_name' => 'Name',
			// 			'is_required' => 1,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'uom',
			// 			'excel_column_name' => 'UOM',
			// 			'is_required' => 0,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'skill_level',
			// 			'excel_column_name' => 'Skill Level',
			// 			'is_required' => 0,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'hours',
			// 			'excel_column_name' => 'Hours',
			// 			'is_required' => 1,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'amount',
			// 			'excel_column_name' => 'Amount',
			// 			'is_required' => 1,
			// 		],
			// 		[
			// 			'company_id' => 1,
			// 			'default_column_name' => 'tax_code',
			// 			'excel_column_name' => 'Tax Code',
			// 			'is_required' => 0,
			// 		],
			// 	],
			],
		];
		ImportType::createMultipleFromArray($import_types);

		return;
	}
}
