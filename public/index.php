<?php
use app\admin\model\Video;

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
/* define('BIND_MODULE','index'); */
/* 网站目录名字 */
//define('APP_DIR','/tptest');

/* define('VIDEO_PATH','') */
define('VIDEO_DIR','/static/video');
define('VIDEOIMG_DIR','/static/video_img');

define('HOAME_JS_CS', '/static');
define('ADMIN_JS_CS', '/static');
define('VIDEOIMG',VIDEOIMG_DIR);
define('VPATH', VIDEO_DIR);
define('HOME_URL','/index.php');


// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
