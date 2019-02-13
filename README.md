基于thinkphp开发的视频管理网站
===============

   后台使用thinkphp,mysql开发,可部署在树莓派的openwrt,以及个人pc,用于视频管理,分类管理,可新建管理用户
   
   获取项目后,Apache新建虚拟主机,目录指向tpvideo/public即可,将sql.sql文件导入mysql数据库,修改项目连接的数据库tpvideo/application/database.php,用户名和密码填写自己mysql数据库用户名密码即可
   
   可登陆管理界面(已存在用户admin,密码123456)上传视频,也可直接将视频拷贝至tpvideo/public/static/video目录下,点击刷新视频,即可将视频加入至数据库进行管理(上传的视频和拷贝的视频都将重命名)
   
   视频存放目录于tpvideo/public/static/video,不建议修改视频存放路径,可在static目录下创建一个软连接名字为video,修改视频目录需要修改tpvideo/public/index.php常量
   
