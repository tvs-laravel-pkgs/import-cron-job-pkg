app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    //ImportCronJob
    when('/import-cron-job-pkg/import-job/list', {
        template: '<import-cron-job-list></import-cron-job-list>',
        title: 'Import Cron Jobs',
    }).
    when('/import-cron-job-pkg/import-job/form/:id', {
        template: '<import-cron-job-form></import-cron-job-form>',
        title: 'Add Import Cron Job',
    })
}]);

app.component('importCronJobList', {
    templateUrl: import_cron_job_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        var dataTable = $('#table').DataTable({
            "dom": dom_structure,
            "language": {
                "search": "",
                "lengthMenu": "Rows Per Page _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            stateSave: true,
            pageLength: 10,
            processing: true,
            serverSide: true,
            paging: true,
            ordering: false,
            ajax: {
                url: laravel_routes['getImportJobList'],
                type: "GET",
                dataType: "json",
                data: function(d) {},
            },

            columns: [
                // { data: 'action', searchable: false, class: 'action1' },
                { data: 'created', name: 'import_jobs.created_at', searchable: true },
                { data: 'type', name: 'type.name', searchable: true },
                { data: 'error_details', searchable: false },
                { data: 'status', name: 'status.name', searchable: true },
                { data: 'entity', searchable: false },
                { data: 'total_record_count', searchable: false },
                { data: 'processed_count', searchable: false },
                { data: 'remaining_count', searchable: false },
                { data: 'new_count', searchable: false },
                { data: 'updated_count', searchable: false },
                { data: 'error_count', searchable: false },
                { data: 'src_file', searchable: false },
                { data: 'output_file', searchable: false },
                { data: 'created_by', name: 'cb.name', searchable: true },
            ],
            "initComplete": function(settings, json) {
                $('.dataTables_length select').select2();
                $('#modal-loading').modal('hide');
            },
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(max)
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });

        $('.page-header-content .display-inline-block .data-table-title').html('Import Jobs <span class="badge badge-secondary" id="table_info">0</span>');
        $('.page-header-content .search.display-inline-block .add_close_button').html('<button type="button" class="btn btn-img btn-add-close"><img src="' + image_scr2 + '" class="img-responsive"></button>');
        $('.page-header-content .refresh.display-inline-block').html('<button type="button" class="btn btn-refresh"><img src="' + image_scr3 + '" class="img-responsive"></button>');

        /*$('.add_new_button').html(
            '<button id="refresh-btn" type="button" class="btn btn-secondary" ng-click="refreshImportJob()">' +
            'Refresh' +
            '</button>'
        );*/

        $('.btn-add-close').on("click", function() {
            $('#table').DataTable().search('').draw();
        });

        $('.btn-refresh, #refresh-btn').on("click", function() {
            $('#table').DataTable().ajax.reload();
        });
    }

});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('importCronJobForm', {
    templateUrl: import_cron_job_from_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        // get_form_data_url = typeof($routeParams.id) == 'undefined' ? ImportCronJob_get_form_data_url : ImportCronJob_get_form_data_url + '/' + $routeParams.id;
        get_form_data_url = '';
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        // $http.get(
        //     get_form_data_url
        // ).then(function(response) {
        //     self.ImportCronJob = response.data.ImportCronJob;
        //     $rootScope.loading = false;
        // });

        /* Tab Funtion */

        var form_id = '#import-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'excel_file': {
                    required: true,
                },
            },
            messages: {
                'code': {
                    maxlength: 'Maximum of 255 charaters',
                },
            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs'
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 3000)
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveImportCronJob'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/import-cron-job-pkg/import-job/list');
                            $scope.$apply();
                        } else {
                            if (!res.success) {
                                $('#submit').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
    }
});