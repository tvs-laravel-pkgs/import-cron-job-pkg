<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ImportTypesU82yvdhk extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('import_types', function (Blueprint $table) {
			$table->string('code', 191)->after('id')->nullable();
			$table->unique('code');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('import_types', function (Blueprint $table) {
			$table->unique('import_types_code_unique');
			$table->dropColumn('code');
		});
	}
}
