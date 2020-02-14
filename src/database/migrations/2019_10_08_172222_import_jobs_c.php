<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ImportJobsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		if (!Schema::hasTable('import_jobs')) {
			Schema::create('import_jobs', function (Blueprint $table) {
				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->unsignedInteger('type_id');
				$table->unsignedInteger('entity_id')->nullable();
				$table->unsignedInteger('total_record_count')->nullable();
				$table->unsignedInteger('processed_count')->default(0);
				$table->unsignedInteger('remaining_count')->default(0);
				$table->unsignedInteger('new_count')->default(0);
				$table->unsignedInteger('updated_count')->default(0);
				$table->unsignedInteger('error_count')->default(0);
				$table->unsignedInteger('status_id')->default(7200);
				$table->string('src_file');
				$table->string('output_file');
				$table->text('error_details')->nullable();
				$table->unsignedInteger('created_by_id');
				$table->unsignedInteger('updated_by_id')->nullable();
				$table->unsignedInteger('deleted_by_id')->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign('type_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('status_id')->references('id')->on('configs')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('updated_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
				$table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('import_jobs');
	}
}
