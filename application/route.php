<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/* return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

]; */
use think\Route;

Route::rule('/','index/index/index');
Route::rule('video','index/index/video');
Route::rule('class','index/index/sort');
Route::rule('search','index/index/search');
Route::rule('refresh','index/index/refresh');

Route::rule('admin','admin/index/index');
Route::rule('login','admin/index/login');
Route::rule('user','admin/index/user');
Route::rule('reuser','admin/index/reuser');
Route::rule('repass','admin/index/modifypas');
Route::rule('resort','admin/index/resort');
Route::rule('sort','admin/index/sort');
Route::rule('checksort','admin/index/checksort');
Route::rule('checklogin','admin/index/checklogin');
Route::rule('delv','admin/index/delv');
Route::rule('asearch','admin/index/asearch');
Route::rule('admrefresh','admin/index/adm_refresh');
Route::rule('delsort','admin/index/delsort');
Route::rule('addsort','admin/index/addsort');
Route::rule('renamesort','admin/index/renamesort');
Route::rule('upfile','admin/index/upfile');
Route::rule('deluser','admin/index/deluser');
Route::rule('upuser','admin/index/upuser');
Route::rule('adduser','admin/index/adduser');
Route::rule('upownpass','admin/index/upownpass');
Route::rule('logout','admin/index/logout');


