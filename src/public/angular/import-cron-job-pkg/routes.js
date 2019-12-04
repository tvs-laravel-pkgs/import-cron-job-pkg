app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    //ImportCronJob
    when('/ImportCronJob-pkg/ImportCronJob/list', {
        template: '<ImportCronJob-list></ImportCronJob-list>',
        title: 'ImportCronJobs',
    }).
    when('/ImportCronJob-pkg/ImportCronJob/add', {
        template: '<ImportCronJob-form></ImportCronJob-form>',
        title: 'Add ImportCronJob',
    }).
    when('/ImportCronJob-pkg/ImportCronJob/edit/:id', {
        template: '<ImportCronJob-form></ImportCronJob-form>',
        title: 'Edit ImportCronJob',
    });
}]);