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
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/*
| -------------------------------------------------------------------------
| REST API Routes
| -------------------------------------------------------------------------
*/
$route['apiui/([a-zA-Z0-9_-]+)'] = 'apiui/index/$1';
$route['api/member/login'] = 'api/member/login';
$route['api/member/forgotpassword'] = 'api/member/forgotpassword';
$route['api/member/detail/(:num)'] = 'api/member/detail/id/$1';
$route['api/member/([a-zA-Z0-9_-]+)/(:num)'] = 'api/member/index/type/$1/$2';
$route['api/member/([a-zA-Z0-9_-]+)'] = 'api/member/index/type/$1';

$route['api/appadmin/add'] = 'api/appadmin/edit';
$route['api/appadmin/edit/(:num)'] = 'api/appadmin/edit/id/$1';
$route['api/appadmin/delete/(:num)'] = 'api/appadmin/delete/id/$1';

$route['api/teacher/add'] = 'api/teacher/edit';
$route['api/teacher/edit/(:num)'] = 'api/teacher/edit/id/$1';
$route['api/teacher/delete/(:num)'] = 'api/teacher/delete/id/$1';

$route['api/student/add'] = 'api/student/edit';
$route['api/student/edit/(:num)'] = 'api/student/edit/id/$1';
$route['api/student/delete/(:num)'] = 'api/student/delete/id/$1';

$route['api/subject/add'] = 'api/subject/edit';
$route['api/subject/edit/(:num)'] = 'api/subject/edit/id/$1';
$route['api/subject/delete/(:num)'] = 'api/subject/delete/id/$1';
$route['api/subject/detail/(:num)'] = 'api/subject/detail/id/$1';
$route['api/subject'] = 'api/subject/index';

$route['api/section/add'] = 'api/section/edit';
$route['api/section/edit/(:num)'] = 'api/section/edit/id/$1';
$route['api/section/delete/(:num)'] = 'api/section/delete/id/$1';
$route['api/section/detail/(:num)'] = 'api/section/detail/id/$1';
$route['api/section'] = 'api/section/index';

$route['api/term/add'] = 'api/term/edit';
$route['api/term/edit/(:num)'] = 'api/term/edit/id/$1';
$route['api/term/delete/(:num)'] = 'api/term/delete/id/$1';
$route['api/term/detail/(:num)'] = 'api/term/detail/id/$1';
$route['api/term'] = 'api/term/index';

$route['api/survey/add'] = 'api/survey/edit';
$route['api/survey/edit/(:num)'] = 'api/survey/edit/id/$1';
$route['api/survey/delete/(:num)'] = 'api/survey/delete/id/$1';
$route['api/survey/detail/(:num)'] = 'api/survey/detail/id/$1';
$route['api/survey'] = 'api/survey/index';

$route['api/becomeastudent/add'] = 'api/becomeastudent/edit';
$route['api/becomeastudent/edit/(:num)'] = 'api/becomeastudent/edit/id/$1';
$route['api/becomeastudent/delete/(:num)'] = 'api/becomeastudent/delete/id/$1';
$route['api/becomeastudent/detail/(:num)'] = 'api/becomeastudent/detail/id/$1';
$route['api/becomeastudent'] = 'api/becomeastudent/index';

$route['api/class/add'] = 'api/ClassSection/edit';
$route['api/class/edit/(:num)'] = 'api/ClassSection/edit/id/$1';
$route['api/class/delete/(:num)'] = 'api/ClassSection/delete/id/$1';
$route['api/class/detail/(:num)'] = 'api/ClassSection/detail/id/$1';
$route['api/class/list'] = 'api/ClassSection/list';
$route['api/class'] = 'api/ClassSection/index';

$route['api/rating/([a-zA-Z0-9_-]+)'] = 'api/rating/index/q/$1';
$route['api/rating/detail/(:num)'] = 'api/rating/detail/id/$1';

$route['api/classsection/list'] = 'api/ClassSection/list';
$route['api/classsection/studentlist'] = 'api/ClassSection/studentlist';
$route['api/classsection'] = 'api/ClassSection/index';
// $route['api/example/users/(:num)'] = 'api/example/users/id/$1'; // Example 4
// $route['api/example/users/(:num)(\.)([a-zA-Z0-9_-]+)(.*)'] = 'api/example/users/id/$1/format/$3$4'; // Example 8
