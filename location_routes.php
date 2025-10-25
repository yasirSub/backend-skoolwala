<?php
/*
|--------------------------------------------------------------------------
| Location API Routes
|--------------------------------------------------------------------------
|
| Add these routes to your application/config/routes.php file
| or create a separate routes file for location management
|
*/

// Location Management Routes
$route['location/add'] = 'location/add';
$route['location/list'] = 'location/list';
$route['location/update/(:num)'] = 'location/update/$1';
$route['location/delete/(:num)'] = 'location/delete/$1';
$route['location/get/(:num)'] = 'location/get/$1';
$route['location/check'] = 'location/check';
$route['location/within-radius'] = 'location/within_radius';

/*
|--------------------------------------------------------------------------
| Usage Examples
|--------------------------------------------------------------------------
|
| POST /location/add
| {
|   "name": "Main Office",
|   "latitude": 23.8103,
|   "longitude": 90.4125,
|   "address": "Dhaka, Bangladesh",
|   "radius": 100,
|   "branch_id": 1,
|   "is_active": 1
| }
|
| GET /location/list?branch_id=1&is_active=1
|
| PUT /location/update/1
| {
|   "name": "Updated Office Name",
|   "radius": 150
| }
|
| DELETE /location/delete/1
|
| POST /location/check
| {
|   "latitude": 23.8103,
|   "longitude": 90.4125,
|   "branch_id": 1
| }
|
| GET /location/within-radius?latitude=23.8103&longitude=90.4125&radius=1000&branch_id=1
|
*/
