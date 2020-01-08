<?php

use Illuminate\Database\Seeder;

class ImportTypePkgSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$import_types = [
			1 => [
				'data' => [
					'name' => 'Supplier Batch Serial Number Import',
					'folder_path' => 'public/file-imports/supplier-batch-serial-number/',
					'file_name' => 'supplier-batch-serial-number',
					'action' => 'App\SupplierBatch::importFromExcel',
					'permission' => 'supplier_batches',
					'template_file' => 'supplier_batch_serial_number.xlsx',
				],
				'columns' => [
					[
						'company_id' => 1,
						'default_column_name' => 'batch_line_id',
						'excel_column_name' => 'Batch Line ID',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'serial_number',
						'excel_column_name' => 'Serial Number',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'item_code',
						'excel_column_name' => 'Item Code',
						'is_required' => 1,
					],
				],
			],
			2 => [
				'data' => [
					'name' => 'Coupon Code Import',
					'folder_path' => 'public/file-imports/coupon-codes/',
					'file_name' => 'coupon-codes',
					'action' => 'Abs\CouponPkg\Coupon::importFromExcel',
					'permission' => 'import-coupon',
					'template_file' => 'coupon_code.xlsx',
				],
				'columns' => [
					[
						'company_id' => 1,
						'default_column_name' => 'coupon_code',
						'excel_column_name' => 'Code',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'date_of_printing',
						'excel_column_name' => 'Date of Printing',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'points',
						'excel_column_name' => 'Point',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'pack_size',
						'excel_column_name' => 'Pack Size',
						'is_required' => 1,
					],
				],
			],
		];
		Abs\ImportCronJobPkg\ImportType::createMultipleFromArray($import_types);

		return;
	}
}
