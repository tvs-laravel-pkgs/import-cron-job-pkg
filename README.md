# import-cron-job-pkg

## Installation. 
* Open termial and navigate to root folder of your project
* Install PKG by adding 'abs/import-cron-job-pkg': 'dev-master' entry in require section of composer.json. 
* Run 'composer update' from termianl. 
* Run 'php artisan migrate' from terminal. 
* If not exists then create PkgPermissionSeeder file in *database/seeds* folder of your project
* Add below line in run() function of PkgPermissionSeeder file
> $this->call(Abs\ImportCronJobPkg\Database\Seeds\ImportCronJobPermissionSeeder::class); 
* Run 'php artisan db:seed --class=PkgPermissionSeeder' in teminal
* Run 'php artisan vendor:publish' in teminal and select respective number shown for package
* Create copy of "vendor/abs/import-cron-job-pkg/src/database/seeds/ImportTypePkgSeeder.php" and save as ImportTypeSeeder.php in database/seeds folder of your project
* Rename class name of copied file from "ImportTypePkgSeeder" to "ImportTypeSeeder"
* Add data for "import types" and their column details
* Run 'composer dump-autoload' from terminal
* Run 'php artisan db:seed --class=ImportTypeSeeder'
* Add following line in your angular page laravel blade file (eg: master.blade.php)
> @include('import-cron-job::setup')

## Menu url for import job list page
  #!/import-cron-job-pkg/import-job/list

## Menu url for import job list page
  #!/import-cron-job-pkg/import-job/form/:id
  

