<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ImportTypesU82yvdhj extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('import_types', function (Blueprint $table) {
			$table->string('action', 255)->after('file_name');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('import_types', function (Blueprint $table) {
			$table->dropColumn('action');
		});
	}
}
