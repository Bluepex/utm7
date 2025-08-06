<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'reports';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

$route['denied-page'] = "Util/deniedPage";
$route['logout'] = "Util/logout";

$route['dashboard'] = 'dashboard';
$route['dashboard/(daily|weekly|monthly)'] = 'dashboard/Index/$1';

$route['dashboard/realtime'] = 'dashboard/realtime';
$route['dashboard/realtime_data'] = 'dashboard/getRealtimeDataAjax';
$route['dashboard/refresh-data'] = 'dashboard/refreshData';

$route['utm/create'] = 'Utm/create';
$route['utm/insert'] = 'Utm/insert';
$route['utm/edit/([0-9]+)'] = 'Utm/edit/$1';
$route['utm/update/([0-9]+)'] = 'Utm/update/$1';
$route['utm/delete/([0-9]+)'] = 'Utm/delete/$1';
$route['utm/set-default/([0-9]+)'] = 'Utm/setDefault/$1';
$route['utm/test-connection-sync/([0-9]+)'] = 'Utm/testConnectionSync/$1';
$route['utm/change-display/([0-9]+)'] = 'Utm/changeDisplay/$1';

$route['reports'] = "Reports";
$route['reports/generate'] = "Reports/generate";
$route['reports/download/(:any)'] = "Reports/downloadReport/$1";
$route['reports/remove/(:any)'] = "Reports/removeReport/$1";
$route['reports/removeall'] = "Reports/removeAllReports";
$route['reports/get-files-table'] = "Reports/getReportFilesTable";
$route['reports/remove-queue/([0-9]+)'] = "Reports/removeReportQueue/$1";

$route['tools/categorization'] = "Tools/Categorization";
$route['tools/categorization/send'] = "Tools/Categorization/send";

$route['lang/(pt-br|english)'] = 'LanguageSwitcher/switchLang/$1';
$route['api/utm-session'] = 'Api/GeneralMethods/putDataSession';
