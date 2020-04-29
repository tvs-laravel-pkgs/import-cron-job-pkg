<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ImportJobsU22 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('import_types', function (Blueprint $table) {
			$table->unsignedTinyInteger('sheet_index')->default(0)->after('action');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('import_types', function (Blueprint $table) {
			$table->dropColumn('sheet_index');
		});
	}
}
