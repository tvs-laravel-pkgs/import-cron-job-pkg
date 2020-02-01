app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    //ImportCronJob
    when('/import-cron-job-pkg/import-job/list', {
        template: '<import-cron-job-list></import-cron-job-list>',
        title: 'Import Status',
    }).
    when('/import-cron-job-pkg/import-job/form/:id', {
        template: '<import-cron-job-form></import-cron-job-form>',
        title: 'Add Import Status',
    })
}]);

app.component('importCronJobList', {
    templateUrl: import_cron_job_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        var cols = [
            { data: 'action', class: 'action', searchable: false },
            { data: 'created', name: 'import_jobs.created_at', searchable: true },
            { data: 'type', name: 'type.name', searchable: true },
            { data: 'status', name: 'status.name', searchable: true },
            { data: 'error_details', searchable: false },
            { data: 'entity', searchable: false },
            { data: 'total_record_count', searchable: false },
            { data: 'processed_count', searchable: false },
            { data: 'remaining_count', searchable: false },
            { data: 'new_count', searchable: false },
            { data: 'updated_count', searchable: false },
            { data: 'error_count', searchable: false },
            { data: 'start_time', searchable: false },
            { data: 'end_time', searchable: false },
            { data: 'duration', searchable: false },
            // { data: 'src_file', searchable: false },
            // { data: 'output_file', searchable: false },
            { data: 'created_by', name: 'cb.name', searchable: true },
        ];

        var import_job_dt_config = JSON.parse(JSON.stringify(dt_config));

        $('#table').DataTable(
            $.extend(import_job_dt_config, {
                columns: cols,
                ordering: false,
                processing: true,
                serverSide: true,
                "scrollX": true,
                stateSave: true,
                stateSaveCallback: function(settings, data) {
                    localStorage.setItem('SIDataTables_' + settings.sInstance, JSON.stringify(data));
                },
                stateLoadCallback: function(settings) {
                    var state_save_val = JSON.parse(localStorage.getItem('SIDataTables_' + settings.sInstance));
                    if (state_save_val) {
                        $('.filterTable').val(state_save_val.search.search);
                    }
                    return JSON.parse(localStorage.getItem('SIDataTables_' + settings.sInstance));
                },
                ajax: {
                    url: laravel_routes['getImportCronJobList'],
                    data: function(d) {}
                },
                infoCallback: function(settings, start, end, max, total, pre) {
                    $('.count').html(total + ' / ' + max + ' listings')
                },
                initComplete: function() {},
            }));
        $('.dataTables_length select').select2();

        var dataTable = $('#table').dataTable();

        $(".filterTable").keyup(function() {
            dataTable.fnFilter(this.value);
        });

        $scope.refresh = function() {
            $('#table').DataTable().ajax.reload();
        };

        $scope.deleteImportJob = function($id) {
            bootbox.confirm({
                message: 'Do you want to delete this import job?',
                className: 'action-confirm-modal',
                buttons: {
                    confirm: {
                        label: 'Yes',
                        className: 'btn-success'
                    },
                    cancel: {
                        label: 'No',
                        className: 'btn-danger'
                    }
                },
                callback: function(result) {
                    if (result) {
                        $http.get(
                            import_cron_job_delete + '/' + $id,
                        ).then(function(response) {
                            if (response.data.success) {
                                custom_noty('success', 'Import job deleted successfully');
                                $('#table').DataTable().ajax.reload(function(json) {});
                                $location.path('/import-cron-job-pkg/import-job/list');
                            }
                        });
                    }
                }
            });
        }
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('importCronJobForm', {
    templateUrl: import_cron_job_from_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_form_data_url = import_cron_job_from_data_url + '/' + $routeParams.id;
        // if ($routeParams.id != 2) {
        //     $location.path('/page-not-found')
        //     // $scope.$apply()
        // }
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;

        self.type_id = $routeParams.id;
        self.import_cron_job_template_base_path = import_cron_job_template_base_path;
        $http.get(
            get_form_data_url
        ).then(function(response) {
            console.log(response);
            self.impoty_type = response.data.impoty_type;
            // if (self.impoty_type.permission != 'import-coupon') {
            //     $location.path('/page-not-found')
            //     $scope.$apply()
            // }
            // $rootScope.loading = false;
        });

        /* Tab Funtion */
        var form_id = '#import-form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'excel_file': {
                    required: true,
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#upload').button('loading');
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
                                $('#upload').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#upload').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
    }
});