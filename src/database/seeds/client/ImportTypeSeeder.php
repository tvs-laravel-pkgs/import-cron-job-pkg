<?php

use Illuminate\Database\Seeder;

class ImportTypeSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$import_types = [
			1 => [
				'data' => [
					'name' => 'CNDN Import',
					'folder_path' => 'public/file-imports/cndn/',
					'file_name' => 'cndn',
					'action' => 'Abs\ServiceInvoicePkg\ServiceInvoice::importFromExcel',
					'permission' => 'import-cn-dn',
					'template_file' => 'cn-dn-import-template.xlsx',
				],
				'columns' => [
					[
						'company_id' => 1,
						'default_column_name' => 'reference_number',
						'excel_column_name' => 'Reference Number',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'type',
						'excel_column_name' => 'Type',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'doc_date',
						'excel_column_name' => 'Doc Date',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'branch',
						'excel_column_name' => 'Branch',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'sbu',
						'excel_column_name' => 'SBU',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'category',
						'excel_column_name' => 'Category',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'sub_category',
						'excel_column_name' => 'Sub Category',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'customer_code',
						'excel_column_name' => 'Customer Code',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'item_code',
						'excel_column_name' => 'Item Code',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'reference',
						'excel_column_name' => 'Reference',
						'is_required' => 1,
					],
					[
						'company_id' => 1,
						'default_column_name' => 'amount',
						'excel_column_name' => 'Amount',
						'is_required' => 1,
					],
				],
			],

		];
		Abs\ImportCronJobPkg\ImportType::createMultipleFromArray($import_types);

		return;
	}
}
