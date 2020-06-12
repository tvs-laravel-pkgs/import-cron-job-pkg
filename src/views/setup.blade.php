@if(config('import-cron-job-pkg.DEV'))
    <?php $import_cron_job_pkg_path = 'packages/abs/import-cron-job-pkg/src/'?>
@else
    <?php $import_cron_job_pkg_path = ''?>
@endif

<!-- IMPORT JOBS -->
<script type="text/javascript">
	app.config(['$routeProvider', function($routeProvider) {
	    $routeProvider.
	    //ImportCronJob
	    when('/import-cron-job-pkg/import-job/list', {
	        template: '<import-cron-job-list></import-cron-job-list>',
	        title: 'Import Status',
	    }).
	    when('/import-cron-job-pkg/import-job/form/:id', {
	        template: '<import-cron-job-form></import-cron-job-form>',
	        title: 'Import Form',
	    })
	}]);

	app.config(['$routeProvider', function($routeProvider) {

	    $routeProvider.
	    when('/import-cron-job-pkg/import-type/list', {
	        template: '<import-type-list></import-type-list>',
	        title: 'Import Types',
	    }).
	    when('/import-cron-job-pkg/import-type/add', {
	        template: '<import-type-form></import-type-form>',
	        title: 'Add Import Type',
	    }).
	    when('/import-cron-job-pkg/import-type/edit/:id', {
	        template: '<import-type-form></import-type-form>',
	        title: 'Edit Import Type',
	    });
	}]);

    var import_cron_job_list_template_url = "{{URL::asset($import_cron_job_pkg_path.'public/themes/'.$theme.'/import-cron-job-pkg/import-cron-job/list.html')}}";
    var import_cron_job_from_template_url = "{{URL::asset($import_cron_job_pkg_path.'public/themes/'.$theme.'/import-cron-job-pkg/import-cron-job/form.html')}}";
    var import_cron_job_from_data_url = "{{url('import-cron-job-pkg/import-jobs/get-from-data/')}}";
    var import_cron_job_delete = "{{url('import-cron-job-pkg/import-job/delete/')}}";
    var execute_cron_job_import = "{{url('import-cron-job-pkg/import-job-cron/execute')}}";
    var import_cron_job_template_base_path = "{{URL::asset('/public/templates/')}}";
</script>
<script type="text/javascript" src="{{URL::asset($import_cron_job_pkg_path.'public/themes/'.$theme.'/import-cron-job-pkg/import-cron-job/controller.js')}}"></script>

<!-- IMPORT TYPE -->
<script type="text/javascript">
    var import_type_list_template_url = "{{asset($import_cron_job_pkg_path.'public/themes/'.$theme.'/import-cron-job-pkg/import-type/list.html')}}";
    var import_type_form_template_url = "{{asset($import_cron_job_pkg_path.'public/themes/'.$theme.'/import-cron-job-pkg/import-type/form.html')}}";
</script>
<script type="text/javascript" src="{{URL::asset($import_cron_job_pkg_path.'public/themes/'.$theme.'/import-cron-job-pkg/import-type/controller.js')}}"></script>
