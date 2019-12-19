<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ImportJobsU1737hdbfjf extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('import_jobs', function (Blueprint $table) {
			$table->unsignedInteger('entity_id')->nullable()->change();
			$table->text('error_details')->nullable()->change();
			$table->dropForeign('import_jobs_type_id_foreign');
			$table->foreign('type_id')->references('id')->on('import_types')->onDelete('CASCADE')->onUpdate('cascade');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('import_jobs', function (Blueprint $table) {
			$table->dropForeign('import_jobs_type_id_foreign');
			$table->foreign('type_id')->references('id')->on('import_types')->onDelete('CASCADE')->onUpdate('cascade');
		});

	}
}
