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

// Health check routes for deployment
$route['health'] = 'health/index';
$route['health/test'] = 'health/test';
$route['db/test'] = 'database_test/index';
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

require_once( BASEPATH .'database/DB.php');
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$url = rtrim($url, '/');
$domain =  parse_url($url, PHP_URL_HOST);
if (substr($domain, 0, 4) == 'www.') {
	$domain = str_replace('www.', '', $domain);
}
$db =& DB();
$saas_default = false;
if ($db->table_exists("custom_domain")) {
	$getURL = $db->select('count(id) as cid')->get_where('custom_domain', array('status' => 1, 'url' => $domain))->row()->cid;
	if($getURL > 0 ) {
		$route['authentication'] = 'authentication/index/$1';
		$route['forgot'] = 'authentication/forgot/$1';
		$route['teachers'] = 'home/teachers';
		$route['events'] = 'home/events';
		$route['news'] = 'home/news/';
		$route['about'] = 'home/about';
		$route['faq'] = 'home/faq';
		$route['admission'] = 'home/admission';
		$route['gallery'] = 'home/gallery';
		$route['contact'] = 'home/contact';
		$route['admit_card'] = 'home/admit_card';
		$route['exam_results'] = 'home/exam_results';
		$route['certificates'] = 'home/certificates';
		$route['page/(:any)'] = 'home/page/$1';
		$route['gallery_view/(:any)'] = 'home/gallery_view/$1';
		$route['event_view/(:num)'] = 'home/event_view/$1';
		$route['news_view/(:any)'] = 'home/news_view/$1';
		$route['default_controller'] = 'home/index';
	} else {
		$saas_default = true;
	}
} else {
	$saas_default = true;
}

$route['(:any)/authentication'] = 'authentication/index/$1';
$route['(:any)/forgot'] = 'authentication/forgot/$1';
$route['(:any)/teachers'] = 'home/teachers';
$route['(:any)/events'] = 'home/events';
$route['(:any)/news'] = 'home/news/';
$route['(:any)/about'] = 'home/about';
$route['(:any)/faq'] = 'home/faq';
$route['(:any)/admission'] = 'home/admission';
$route['(:any)/gallery'] = 'home/gallery';
$route['(:any)/contact'] = 'home/contact';
$route['(:any)/admit_card'] = 'home/admit_card';
$route['(:any)/exam_results'] = 'home/exam_results';
$route['(:any)/certificates'] = 'home/certificates';
$route['(:any)/page/(:any)'] = 'home/page/$2';
$route['(:any)/gallery_view/(:any)'] = 'home/gallery_view/$2';
$route['(:any)/event_view/(:num)'] = 'home/event_view/$2';
$route['(:any)/news_view/(:any)'] = 'home/news_view/$2';

$route['dashboard'] = 'dashboard/index';
$route['branch'] = 'branch/index';
$route['attachments'] = 'attachments/index';
$route['homework'] = 'homework/index';
$route['onlineexam'] = 'onlineexam/index';
$route['hostels'] = 'hostels/index';
$route['event'] = 'event/index';
$route['accounting'] = 'accounting/index';
$route['school_settings'] = 'school_settings/index';
$route['role'] = 'role/index';
$route['sessions'] = 'sessions/index';
$route['translations'] = 'translations/index';
$route['cron_api'] = 'cron_api/index';
$route['modules'] = 'modules/index';
$route['system_student_field'] = 'system_student_field/index';
$route['custom_field'] = 'custom_field/index';
$route['backup'] = 'backup/index';
$route['advance_salary'] = 'advance_salary/index';
$route['system_update'] = 'system_update/index';
$route['certificate'] = 'certificate/index';
$route['payroll'] = 'payroll/index';
$route['leave'] = 'leave/index';
$route['award'] = 'award/index';
$route['classes'] = 'classes/index';
$route['student_promotion'] = 'student_promotion/index';
$route['live_class'] = 'live_class/index';
$route['exam'] = 'exam/index';
$route['profile'] = 'profile/index';
$route['sections'] = 'sections/index';
$route['subscription_review/(:num)'] = 'saas_website/purchase_complete/$1';

$route['authentication'] = 'authentication/index';
$route['install'] = 'install/index';
$route['404_override'] = 'errors';

// API Routes - These need to be placed before the catch-all route
$route['api/v1/auth/login'] = 'api/authLogin';
$route['api/teacherLogin'] = 'api/teacherLogin';
$route['api/teacherAttendance'] = 'api/teacherAttendance';
$route['api/teacherSelfAttendance'] = 'api/teacherSelfAttendance';
$route['api/teacherPresentDaysCount'] = 'api/teacherPresentDaysCount';
$route['api/teacherAbsentDaysCount'] = 'api/teacherAbsentDaysCount';
$route['api/teacherProfile'] = 'api/teacherProfile';
$route['api/updateTeacherProfile'] = 'api/updateTeacherProfile';
$route['api/enrollFaceImage'] = 'api/enrollFaceImage';
$route['api/enrollFace'] = 'api/enrollFace';
$route['api/face/enroll'] = 'faceapi/enroll';
$route['api/face/identify'] = 'faceapi/identify';
$route['api/face/check-duplicate'] = 'faceapi/checkDuplicate';
$route['api/face/analyze'] = 'faceanalyzer/analyze';
$route['api/face/testAnalyzer'] = 'faceanalyzer/testAnalyzer';
$route['api/face/enrollment/check/(:num)'] = 'api/checkFaceEnrollment/$1';
$route['api/face/enrollment/check'] = 'api/checkFaceEnrollment';
$route['api/attendanceForTeacher'] = 'attendanceforteacher/quickAttendance';
$route['api/attendanceForTeacherTest'] = 'attendanceforteachertest/test';
$route['api/getSchoolLocation'] = 'api/getSchoolLocation';
$route['api/getTeacherSelfAttendanceStats'] = 'api/getTeacherSelfAttendanceStats';
$route['api/locationAnalytics'] = 'locationanalytics/getComprehensiveAnalytics';
$route['api/exportLocationAnalytics'] = 'locationanalytics/exportAnalytics';
$route['location-analytics'] = 'locationanalytics/index';
$route['api/deleteAttendance'] = 'api/deleteAttendance';
$route['api/generateDummyDataAlt'] = 'api/generateDummyDataAlt';
$route['api/testGenerateDummyDataSimple'] = 'api/testGenerateDummyDataSimple';
$route['api/testGenerateDummyData'] = 'api/testGenerateDummyData';
$route['api/generateDummyData'] = 'api/generateDummyData';
$route['api/getStaffList'] = 'api/getStaffList';
$route['api/quickAttendance'] = 'api/quickAttendance';
$route['api/face/delete/(:num)'] = 'api/deleteFaceEnrollment/$1';
$route['api/face/delete'] = 'api/deleteFaceEnrollment';
$route['api/checkFaceDuplicate'] = 'api/checkFaceDuplicate';
$route['api/listEnrolledFaces'] = 'api/listEnrolledFaces';
$route['api/verifyFaceImage'] = 'api/verifyFaceImage';
$route['api/verifyFace'] = 'api/verifyFace';
$route['api/f2f/test'] = 'api/f2fTest';
$route['api/f2f/testController'] = 'f2fapi/test';
$route['api/f2f/testDelete'] = 'f2fapi/testDelete';
$route['api/f2f/register'] = 'f2fapi/register';
$route['api/f2f/analyze'] = 'f2fapi/analyze';
$route['api/f2f/list'] = 'f2fapi/list';
$route['api/f2f/checkFace'] = 'f2fapi/checkFace';
$route['api/f2f/delete'] = 'f2fapi/delete';
$route['api/f2f/deleteAll'] = 'f2fapi/deleteAll';
$route['api/f2f/create'] = 'f2fapi/create';
$route['api/f2f/attach'] = 'f2fapi/attach';
$route['api/f2f/attachImage'] = 'f2fapi/attachImage';
$route['api/getSchoolInfo'] = 'api/getSchoolInfo';
$route['api/attendance/today/(:num)'] = 'api/getTodayAttendance/$1';
$route['api/attendance/mark'] = 'api/markAttendanceWithFace';
$route['api/face/enrollment/check/(:num)'] = 'api/checkFaceEnrollment/$1';
$route['api/face/enrollment/check'] = 'api/checkFaceEnrollment';
$route['api/face/delete/(:num)'] = 'api/deleteFaceEnrollment/$1';
$route['api/face/delete'] = 'api/deleteFaceEnrollment';
$route['api/face/identify'] = 'faceapi/identify';
$route['api/debugFaceRecognition'] = 'api/debugFaceRecognition';
$route['api/test'] = 'api/test';
$route['api/testDatabase'] = 'api/testDatabase';
$route['api/fixDatabaseSchema'] = 'api/fixDatabaseSchema';
$route['api/testCodeVersion'] = 'api/testCodeVersion';
$route['api/create3DFaceTable'] = 'api/create3DFaceTable';
$route['api/face/enroll3D'] = 'api/enrollFace3D';
$route['api/face/identify3D'] = 'api/identifyFace3D';
$route['api/face/list3D'] = 'api/list3DFaces';

// School Location Management API Routes
$route['api/school-location/set'] = 'schoollocation/set';
$route['api/school-location/list'] = 'schoollocation/list';
$route['api/school-location/check'] = 'schoollocation/check';
$route['api/school-location/delete/(:num)'] = 'schoollocation/delete/$1';

// Employee Face Status Routes
$route['employee/checkFaceStatus/(:num)'] = 'employee/checkFaceStatus/$1';

if ($saas_default) {
	$route['default_controller'] = 'saas_website/index';
}
$route['(:any)'] = 'home/index/$1';
$route['translate_uri_dashes'] = FALSE;