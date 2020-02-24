<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterImportTypes extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('import_types', function (Blueprint $table) {
			// $table->string('permission', 255)->after('action');
			// $table->string('template_file', 255)->after('permission');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('import_types', function (Blueprint $table) {
			// $table->dropColumn('permission');
			// $table->dropColumn('template_file');
		});
	}
}
