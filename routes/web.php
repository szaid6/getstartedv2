<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('logout', [AdminController::class, 'logout'])->name('logout');

Route::get('/login', [AdminController::class, 'login']);
Route::post('auth', [AdminController::class, 'checkUser'])->name('auth');

Route::get('register', [AdminController::class, 'showRegister']);
Route::post('register', [AdminController::class, 'Register']);

Route::post('/signup', [AdminController::class, 'storeEnquiry']);

Route::get('forgetPassword', [AdminController::class, 'showforget']);
Route::post('forgetPassword', [AdminController::class, 'forgetpassword']);
Route::post('changePassword', [AdminController::class, 'changepassword']);

Route::group(['middleware' => 'checkUserr'], function () {

    Route::get('resetP', [AdminController::class, 'resetPassIndex']);
    Route::post('resetPass', [AdminController::class, 'resetPass']);
    Route::post('checkPhone', [AdminController::class, 'checkPhone']);
    Route::post('checkPass', [AdminController::class, 'checkPass']);


    Route::get('dashboard', [AdminController::class, 'indexAdmin']);

    //Office User
    Route::group([
        'prefix' => 'user'
    ], function () {
        Route::get('/', [AdminController::class, 'indexUser']);
        Route::post('/addUser', [AdminController::class, 'saveUser']);
        Route::post('/checkEmail', [AdminController::class, 'checkOfficeUserEmail']);
        Route::post('/checkPhone', [AdminController::class, 'checkOfficeUserPhone']);
        Route::get('/exportUserExcel', [AdminController::class, 'exportOfficeUserData']);
        Route::get('/exportToCSV', [AdminController::class, 'exportToCSV']);
        //not working
        //Route::get('/exportToPDF', [AdminController::class, 'exportToPDF']);
        Route::post('/addUserExcel', [AdminController::class, 'saveUserExcel']);
        Route::post('/deleteUser', [AdminController::class, 'deleteUser']);
        Route::post('/editUser', [AdminController::class, 'editUser']);
    });

    // enduser enduser/checkPhone
    Route::group([
        'prefix' => 'enduser',
        'middleware' => ['notAllowManager', 'notAllowSupervisor', 'notAllowSuperManager']
    ], function () {
        Route::get('/', [AdminController::class, 'indexEndUser']);
        Route::post('/addEndUser', [AdminController::class, 'saveEndUser']);
        Route::post('/checkEmail', [AdminController::class, 'checkEndUserEmail']);
        Route::post('/checkPhone', [AdminController::class, 'checkEndUserPhone']);
        Route::get('/exportEndUserExcel', [AdminController::class, 'exportEndUserData']);
        Route::post('/addEndUserExcel', [AdminController::class, 'saveEndUserExcel']);
        Route::post('/deleteEndUser', [AdminController::class, 'deleteEndUser']);
        Route::post('/editEndUser', [AdminController::class, 'editEndUser']);
        
    });

    //Role
    Route::group([
        'prefix' => 'role'
    ], function () {
        Route::get('/', [AdminController::class, 'indexRole']);
        Route::post('/addRole', [AdminController::class, 'storeRole']);
        Route::post('/deleteRole', [AdminController::class, 'deleteRole']);
        Route::post('/editRole', [AdminController::class, 'editRole']);
    });

    Route::group([
        'prefix' => 'blog'
    ], function () {
        Route::get('/', [AdminController::class, 'indexBlog']);
        Route::get('/addBlog', [AdminController::class, 'createBlog']);
        Route::post('/saveBlog', [AdminController::class, 'storeBlog']);
        Route::post('/deleteBlog', [AdminController::class, 'deleteBlog']);
        Route::get('/editBlog/{id}', [AdminController::class, 'showBlogEdit']);
        Route::post('/editBlog', [AdminController::class, 'editBlog']);
    });

    //Testimonial
    Route::group([
        'prefix' => 'testimonial'
    ], function () {
        Route::get('/', [AdminController::class, 'indexTestimonial']);
        Route::post('/addTestimonial', [AdminController::class, 'storeTestimonial']);
        Route::post('/editTestimonial', [AdminController::class, 'editTestimonial']);
        Route::post('/deleteTestimonial', [AdminController::class, 'deleteTestimonial']);
    });
});
