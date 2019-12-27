<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterImportJob extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('import_jobs', function (Blueprint $table) {
			$table->time('start_time')->nullable()->after('error_details');
			$table->time('end_time')->nullable()->after('start_time');
			$table->time('duration')->nullable()->after('end_time');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('import_jobs', function (Blueprint $table) {
			$table->dropColumn('start_time');
			$table->dropColumn('end_time');
			$table->dropColumn('duration');
		});
	}
}
