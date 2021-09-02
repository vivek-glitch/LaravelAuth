<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Illuminate\Http\Request;

// $domains = ["localhost" , "192.168.103.203"];
// if(!(php_sapi_name() === 'cli' OR defined('STDIN'))){
//     if ( ! in_array($_SERVER['SERVER_NAME'], $domains)) {
//         echo "Illegal Access";die();
//     }
// }
Route::get('/', function () {
    return view('welcome');
});

Route::get('/test',[App\Http\Controllers\TestController::class, 'index']);

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('auth/google', 'App\Http\Controllers\Auth\GoogleController@redirectToGoogle');
Route::get('auth/google/callback', 'App\Http\Controllers\Auth\GoogleController@handleGoogleCallback');
// Route::get('/home', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->middleware('auth');

Route::match(['get','post'],'/Admin/{controllers}/{action?}/{params?}', function ($controllers, $action='index', $params='') {
    $params = explode('/', $params);
    $app = app();
    if(strpos($controllers,'-') !==false){
        $strControllerName = implode('', array_map('ucwords', explode('-',$controllers)));
    }else{
        $strControllerName = ucwords($controllers);
    }
    $controllers = $app->make("\App\Http\Controllers\Admin\\". $strControllerName.'Controller' );
    return $controllers->callAction($action, $params);
    
})->where('params', '[A-Za-z0-9/]+')->middleware('auth');

Route::match(['get','post'],'/User/{controller}/{action?}/{params?}', function ($controller, $action='index', $params='') {
    $params = explode('/', $params);
    $app = app();
    if(strpos($controller,'-') !==false){
        $strControllerName = implode('', array_map('ucwords', explode('-',$controller)));
    }else{
        $strControllerName = ucwords($controller);
    }
    $controller = $app->make("\App\Http\Controllers\User\\". $strControllerName.'Controller' );
    return $controller->callAction($action, $params);
    
})->where('params', '[A-Za-z0-9/]+')->middleware('auth');