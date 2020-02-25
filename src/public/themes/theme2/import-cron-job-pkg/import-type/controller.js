app.component('importTypeList', {
    templateUrl: import_type_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.add_import_type = self.hasPermission('add-import-type');
        var table_scroll;
        table_scroll = $('.page-main-content').height() - 37;
        var dataTable = $('#import_types_list').DataTable({
            "dom": cndn_dom_structure,
            "language": {
                // "search": "",
                // "searchPlaceholder": "Search",
                "lengthMenu": "Rows _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            scrollX: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            stateSave: true,
            stateSaveCallback: function(settings, data) {
                localStorage.setItem('IMTDataTables_' + settings.sInstance, JSON.stringify(data));
            },
            stateLoadCallback: function(settings) {
                var state_save_val = JSON.parse(localStorage.getItem('IMTDataTables_' + settings.sInstance));
                if (state_save_val) {
                    $('#search_import_type').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('IMTDataTables_' + settings.sInstance));
            },
            processing: true,
            serverSide: true,
            paging: true,
            searching: true,
            ordering: false,
            ajax: {
                url: laravel_routes['getImportTypeList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.import_type_code = $('#import_type_code').val();
                    d.import_type_name = $('#import_type_name').val();
                    d.mobile_no = $('#mobile_no').val();
                    d.email = $('#email').val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'import_type_action', name: 'import_types.action' },
                { data: 'folder_path', name: 'import_types.folder_path' },
                { data: 'file_name', name: 'import_types.file_name  ' },
                { data: 'permission', name: 'import_types.permission' },
                { data: 'template_file', name: 'import_types.template_file' },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $('.refresh_table').on("click", function() {
            $('#import_types_list').DataTable().ajax.reload();
        });

        $scope.clear_search = function() {
            $('#search_import_type').val('');
            $('#import_types_list').DataTable().search('').draw();
        }

        var dataTables = $('#import_types_list').dataTable();
        $("#search_import_type").keyup(function() {
            dataTables.fnFilter(this.value);
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
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'ImportType Deleted Successfully',
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 3000);
                    $('#import_types_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/import-cron-job-pkg/import-type/list');
                }
            });
        }

        //FOR FILTER
        // $('#import_type_code').on('keyup', function() {
        //     dataTables.fnFilter();
        // });
        // $('#import_type_name').on('keyup', function() {
        //     dataTables.fnFilter();
        // });
        // $('#mobile_no').on('keyup', function() {
        //     dataTables.fnFilter();
        // });
        // $('#email').on('keyup', function() {
        //     dataTables.fnFilter();
        // });
        // $scope.reset_filter = function() {
        //     $("#import_type_name").val('');
        //     $("#import_type_code").val('');
        //     $("#mobile_no").val('');
        //     $("#email").val('');
        //     dataTables.fnFilter();
        // }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('importTypeForm', {
    templateUrl: import_type_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        // get_form_data_url = typeof($routeParams.id) == 'undefined' ? import_type_get_form_data_url : import_type_get_form_data_url + '/' + $routeParams.id;
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
            console.log(response.data);
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
            $('.cndn-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.cndn-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

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