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

app.component('importTypeList', {
    templateUrl: import_type_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var table_scroll;
        table_scroll = $('.page-main-content').height() - 37;
        var dataTable = $('#faqs_list').DataTable({
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
                url: laravel_routes['getFaqList'],
                data: function(d) {}
            },
            columns: [
                { data: 'action', searchable: false, class: 'action' },
                { data: 'question', name: 'faqs.question', searchable: true },
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
        $('.page-header-content .display-inline-block .data-table-title').html('FAQs <span class="badge badge-secondary" id="table_info">0</span>');
        $('.page-header-content .search.display-inline-block .add_close_button').html('<button type="button" class="btn btn-img btn-add-close"><img src="' + image_scr2 + '" class="img-responsive"></button>');
        $('.page-header-content .refresh.display-inline-block').html('<button type="button" class="btn btn-refresh"><img src="' + image_scr3 + '" class="img-responsive"></button>');
        $('.add_new_button').html(
            '<a href="#!/faq-pkg/faq/add" type="button" class="btn btn-secondary" dusk="add-btn">' +
            'Add FAQ' +
            '</a>'
        );

        $('.btn-add-close').on("click", function() {
            $('#faqs_list').DataTable().search('').draw();
        });

        $('.btn-refresh').on("click", function() {
            $('#faqs_list').DataTable().ajax.reload();
        });

        $('.dataTables_length select').select2();

        $scope.clear_search = function() {
            $('#search_faq').val('');
            $('#faqs_list').DataTable().search('').draw();
        }

        var dataTables = $('#faqs_list').dataTable();
        $("#search_faq").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteFaq = function($id) {
            $('#faq_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#faq_id').val();
            $http.get(
                faq_delete_data_url + '/' + $id,
            ).then(function(response) {
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Faq Deleted Successfully',
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 3000);
                    $('#faqs_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/faq-pkg/faq/list');
                }
            });
        }

        //FOR FILTER
        $('#faq_code').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#faq_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#mobile_no').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#email').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.reset_filter = function() {
            $("#faq_name").val('');
            $("#faq_code").val('');
            $("#mobile_no").val('');
            $("#email").val('');
            dataTables.fnFilter();
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('importTypeForm', {
    templateUrl: import_type_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_form_data_url = typeof($routeParams.id) == 'undefined' ? laravel_routes['getFaqFormData'] : laravel_routes['getFaqFormData'] + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http({
            url: laravel_routes['getFaqFormData'],
            method: 'GET',
            params: {
                'id': typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
            }
        }).then(function(response) {
            self.faq = response.data.faq;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.faq.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'question': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'answer': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
            },
            invalidHandler: function(event, validator) {
                checkAllTabNoty()
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveFaq'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message)
                            $location.path('/faq-pkg/faq/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('#submit').button('reset');
                                showErrorNoty(res)
                            } else {
                                $('#submit').button('reset');
                                $location.path('/faq-pkg/faq/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        showServerErrorNoty()
                    });
            }
        });
    }
});
