app.component('importTypeList', {
    templateUrl: import_type_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        var dataTable = $('#import_type_list').DataTable({
            "dom": dom_structure,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
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
                url: laravel_routes['getImportTypeList'],
                type: "GET",
                dataType: "json",
                data: function(d) {}
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'name', name: 'import_types.name', searchable: true },
                { data: 'folder_path', name: 'import_types.folder_path', searchable: true },
                { data: 'file_name', name: 'import_types.file_name', searchable: true },
                { data: 'import_type_action', name: 'import_types.action', searchable: true },
                { data: 'permission', name: 'import_types.permission', searchable: true },
                { data: 'template_file', name: 'import_types.template_file', searchable: true },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total + '/' + max)
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            },
            initComplete: function() {
                $('.search label input').focus();
            },
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Import Types <span class="badge badge-secondary" id="table_info">0</span>');
        $('.page-header-content .search.display-inline-block .add_close_button').html('<button type="button" class="btn btn-img btn-add-close"><img src="' + image_scr2 + '" class="img-responsive"></button>');
        $('.page-header-content .refresh.display-inline-block').html('<button type="button" class="btn btn-refresh"><img src="' + image_scr3 + '" class="img-responsive"></button>');

        $('.btn-add-close').on("click", function() {
            $('#import_type_list').DataTable().search('').draw();
        });

        $('.btn-refresh').on("click", function() {
            $('#import_type_list').DataTable().ajax.reload();
        });

        //DELETE
        $scope.deleteImportType = function($id) {
            $('#import_type_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#import_type_id').val();
            $http.get(
                laravel_routes['deleteImportType'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', response.data.message);
                    $('#import_type_list').DataTable().ajax.reload();
                    $scope.$apply();
                } else {
                    custom_noty('error', errors);
                }
            });
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('importTypeForm', {
    templateUrl: import_type_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        // get_form_data_url = typeof($routeParams.id) == 'undefined' ? laravel_routes['getFaqFormData'] : laravel_routes['getFaqFormData'] + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getImportTypeFormData'], {
                params: {
                    'id': $routeParams.id,
                }
            }
        ).then(function(response) {
            //console.log(response.data);
            self.import_type = response.data.import_type;
            self.action = response.data.action;
            self.theme = response.data.theme;
            $rootScope.loading = false;
        });

        //ADD FIELDS
        $scope.addImportFields = function() {
            self.import_type.columns.push({
                id: '',
                company_id: '',
                default_column_name: '',
                excel_column_name: '',
                switch_value: 'No',
            });
        }
        //REMOVE FIELDS
        self.import_field_removal_ids = [];
        $scope.removeImportFields = function(index, column_id) {
            if (column_id) {
                self.import_field_removal_ids.push(column_id);
                $("#import_field_removal_ids").val(JSON.stringify(self.import_field_removal_ids));
            }
            self.import_type.columns.splice(index, 1);
        }

        /* Tab Funtion */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'folder_path': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'file_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
                'action': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'permission': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'template_file': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
            },
            invalidHandler: function(event, validator) {
                // checkAllTabNoty()
                custom_noty('error', 'Please check in each tab and fix errors!');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveImportType'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message)
                            $location.path('/import-cron-job-pkg/import-type/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('#submit').button('reset');
                                // showErrorNoty(res)
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                $('#submit').button('reset');
                                custom_noty('success', res.message)
                                $location.path('/import-cron-job-pkg/import-type/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        // showServerErrorNoty()
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
    }
});