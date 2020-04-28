<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function() use($router) {
    $router->group(['prefix' => 'role'], function() use($router) {
        $router->post('/', 'RoleController@create');
        $router->get('/', 'RoleController@read');
        $router->put('/{Id}', 'RoleController@update');
        $router->delete('/{Id}', 'RoleController@delete');
        $router->get('/{Id}', 'RoleController@show');
    });

    $router->group(['prefix' => 'user'], function() use($router) {
        $router->post('/', 'UserController@create');
        $router->get('/', 'UserController@read');
        $router->put('/{Id}', 'UserController@update');
        $router->delete('/{Id}', 'UserController@delete');
        $router->get('/{Id}', 'UserController@show');
    });

    $router->group(['prefix' => 'device'], function() use($router) {
        $router->post('/', 'DeviceController@create');
        $router->get('/', 'DeviceController@read');
        $router->put('/{Id}', 'DeviceController@update');
        $router->delete('/{Id}', 'DeviceController@delete');
        $router->get('/{Id}', 'DeviceController@show');
        $router->get('/readdevicewithzoneid/{zoneId}', 'DeviceController@readDevicewithZoneId');
    });

    $router->group(['prefix' => 'zone'], function() use($router) {
        $router->post('/', 'ZoneController@create');
        $router->get('/', 'ZoneController@read');
        $router->put('/{Id}', 'ZoneController@update');
        $router->delete('/{Id}', 'ZoneController@delete');
        $router->get('/{Id}/{Type}', 'ZoneController@show');
        $router->post('/createzonedetail', 'ZoneController@createZoneDetail');
        $router->post('/deletezonedetail', 'ZoneController@deleteZoneDetail');
    });

    $router->group(['prefix' => 'booking'], function() use($router) {
        $router->get('/getbookingbyuserid/{userid}', 'BookingController@readBookingActiveByUserId');
        $router->post('/createBooking', 'BookingController@createBooking');
        $router->post('/checkInBooking', 'BookingController@checkInBooking');
        $router->post('/checkOutBooking', 'BookingController@checkOutBooking');
        $router->get('/updatelogtransactiondevice/{randomCode}/{status}', 'BookingController@updateLogTransactionDevice');
    });



    $router->post('/login', 'UserController@login');
    $router->post('/register', 'UserController@create');
});