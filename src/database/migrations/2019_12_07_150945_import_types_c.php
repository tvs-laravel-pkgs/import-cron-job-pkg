<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ImportTypesC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('import_types')) {
			Schema::create('import_types', function (Blueprint $table) {
				$table->increments('id');
				$table->string('name', 191);
				$table->string('folder_path', 255);
				$table->string('file_name', 191);

				$table->unique(["name"]);
				$table->unique(["file_name"]);
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('import_types');
	}
}
