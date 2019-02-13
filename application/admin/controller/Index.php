<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use \app\admin\model\Sort as sort_m;
use \app\admin\model\Video as video_m;
use \app\admin\model\Admin as admin_m;
use think\Validate;
use think\Cache;
class Index extends Controller{
  
    public function login(){
        $getdata=input('error');
        if(empty($getdata)){
            $this->assign('error',""); 
        }else{
            $this->assign('error',$getdata);
        }
        return view();
    }
    
    public function checklogin(){
        
        $validata=new Validate([
            "username"=>"require",
            "password"=>"require"
        ],[
            "username.require"=>'用户名不能为空',
            "password.require"=>'密码不能为空',
        ]);
        $data=input('post.');
        if ($validata->check($data)){
            $admin=new admin_m();
            $username=$data['username'];
            $password=md5($data['password']);
            $dbinfo=$admin->query("select id,password from admin where username='$username';");
            
            if(!empty($dbinfo)){
                $userid=$dbinfo[0]['id'];
                $dbinfo=$dbinfo[0]['password'];
                if($dbinfo==$password){
                    session('id',$userid);
                    session('tpvideo',$password);
                    $this->redirect('/admin');
                    exit();
                }else{
                    $error="用户名不存在或密码错误";
                    $this->redirect('/login',array('error'=>$error));
                    exit();
                }
                
            }else{
                $error="用户名不存在或密码错误";
                $this->redirect('/login',array('error'=>$error));
                exit();
            }
           
        }else{ 
            $error=$validata->getError();
            $this->redirect('/login',array('error'=>$error));
            exit();
        }
    
    }
    
    public function index(){
        $check=new check();
        $check->testlogin();
        
      /*   $vfile="./static/video/test.mkv";
        dump(unlink($vfile));exit(); */
        
        $viinfo = Db::name('video')->alias('v')->join('sort s','v.sortid=s.id')->field('video.id,vname,vimg,video.time,name')->order('time desc')->paginate(14);
        $this->assign('viinfo',$viinfo);
        $page = $viinfo->render();
        $this->assign('page', $page);
        
        return view();
    }
    
    public function asearch(){
        $check=new check();
        $check->testlogin();
        
        $ainfo=input('ainfo');
        $this->assign('asearch_info',$ainfo);
        $ainfo='%'.$ainfo.'%';
        $viinfo=Db::name('video')->alias('v')->join('sort s','v.sortid=s.id')->field('video.id,vname,vimg,video.time,name')->where('vname|name','like',$ainfo)->order('time desc')->paginate(14);
        $this->assign('viinfo',$viinfo);
        $page = $viinfo->render();
        $this->assign('page', $page);
        return view();
    }
    
    public function adm_refresh(){
        $check=new check();
        $check->testlogin();
        
        $vdir='.'.VIDEO_DIR.'/';
        $resource=opendir($vdir);
        $dirinfo=array();
        $i=0;
        while ($row=readdir($resource)){
            if($row=="."||$row==".."){
                continue;
            }
            $dirinfo[$i]=$row;
            $i++;
        }
        closedir($resource);
        $video=new video_m();
        $sortid=new sort_m();
        $dbinfo=$video->query("select vpath from video;");
        $result = [];
        array_walk_recursive($dbinfo, function($value) use (&$result) {
            array_push($result, $value);
        });
        $dbname=$result;
        foreach ($dirinfo as $key=>$value){
            if(!in_array($value,$dbname)){
                $random=rand();
                $imgname = md5(date('YmdHis').$random).".jpg";
                $imgpath='.'.VIDEOIMG_DIR.'/'.$imgname;
                $vpath=$vdir.$value;
                
                /*   ffmpeg -i test.asf -y -f image2 -t 0.001 -s 334x188 a.jpg */
                $str="ffmpeg -i "."\"".$vpath."\""." -y -f image2 -t 0.001 -s 334x188 "."\"".$imgpath."\"";
                system($str);
                
                $sorid=$sortid->query("select max(id) from sort;");
                $sorid=$sorid[0]['max(id)'];
                $fileclass=substr(strrchr($value, '.'), 1);
                $desname=md5($value.$random);
                $despath=$desname.'.'.$fileclass;
             /*    dump($vpath);dump($despath);exit(); */
                rename($vpath, $vdir.$desname.'.'.$fileclass);
                $video->execute("INSERT INTO video (vname, sortid, vpath, vimg) VALUES ('$value', '$sorid', '$despath', '$imgname');");
                
            }
        }
     
        
        foreach ($dbname as $key=>$value){
            if(!in_array($value,$dirinfo)){
                $rm=$video->query("select id,vimg from video where vpath='$value' order by id desc limit 0,1;");
                $rminfo=$rm[0];
                $rmid=$rminfo['id'];
                $vimgpath=".".VIDEOIMG_DIR."/".$rminfo['vimg'];
                if(file_exists($vimgpath)){
                    unlink($vimgpath);
                }
               $video->execute("delete from video where id=$rmid;");
               
             
            }
        }
        
        
        $vimgpath=".".VIDEOIMG_DIR."/";
        $imgsource=opendir($vimgpath);
        $vimginfo=array();
        $i=0;
        while ($row=readdir($imgsource)){
            if($row=="."||$row==".."){
                continue;
            }
            $vimginfo[$i]=$row;
            $i++;
        }
        closedir($imgsource);
        $dbimg=$video->query("select vimg from video;");
        $redbimg = [];
        array_walk_recursive($dbimg, function($value) use (&$redbimg) {
            array_push($redbimg, $value);
        });
        
        foreach ($vimginfo as $key=>$value){
            if(!in_array($value,$redbimg)){
                $delimg=$vimgpath.$value;
                if(file_exists($delimg)){
                    unlink($delimg);
                }
            }
         }
       /*  dump($redbimg);dump($vimginfo); */
        
        
         foreach ($redbimg as $key=>$value){
             if(!in_array($value,$vimginfo)){
                 
                 $testv=$video->query("select id,vpath from video where vimg='$value' order by id desc limit 0,1;");
                 $testv=$testv[0];
                 $testvpath=$vdir.$testv['vpath'];
                 
                 if(file_exists($testvpath)){
                     $desimg=$vimgpath.$value;
                     $str="ffmpeg -i "."\"".$testvpath."\""." -y -f image2 -t 0.001 -s 334x188 "."\"".$desimg."\"";
                     system($str);
                 }else{
                     $testvid=$testv['id'];
                     $video->execute("delete from video where id=$testvid;");
                 }
             }
         }
        $this->redirect('/admin');
        exit();

        
    }
    
    public function user(){
        $check=new check();
        $check->testlogin();
        
        $getdata=input('error');
        if(empty($getdata)){
            $this->assign('error',"");
        }else{
            $this->assign('error',$getdata);
        }
        
        $userinfo = Db::name('admin')->field('id,username,admtime')->order('id asc')->paginate(14);
        /*   $sortinfo=$sort->query("select * from sort;"); */
        $page = $userinfo->render();
        $this->assign('userinfo',$userinfo);
        $this->assign('page', $page);
        
        return view();
    }
    
    public function reuser(){
        $check=new check();
        $check->testlogin();
        
        $getdata=input('error');
        if(empty($getdata)){
            $this->assign('error',"");
        }else{
            $this->assign('error',$getdata);
        }
        
        
        $chadmin=$check->testadmin();
        if($chadmin==FALSE){
            $error="仅admin用户可执行";
            $this->redirect('/user',array('error'=>$error));
            exit();
        }
        
        $userid=input('id');
        $admin=new admin_m();
        $redb=$admin->query("select username from admin where id=$userid;");
        $username=$redb[0]["username"];
        $this->assign('username',$username);
        $this->assign('userid',$userid);
        return view();
    }
    
    public function upuser(){
        
        $validata=new Validate([
            "password"=>"require",
            "repassword"=>"require|confirm:password",
            "userid"=>"require",
        ],[
            "password.require"=>'密码不能为空',
            "repassword.require"=>'确认密码不能为空',
            "repassword.confirm"=>'两次密码不一致',
            "userid.require"=>'用户信息错误',
        ]);
       
        
         $forminfo=input('post.');
       
        if ($validata->check($forminfo)){
            $testlength=$forminfo["password"];
            $length=strlen($testlength);
            if($length<6||$length>20){
                $error="密码长度需要在6-20之间";
                $this->redirect('/reuser',array('error'=>$error,'id'=>$forminfo["userid"]));
            }
            $userpass=$forminfo["password"];
            $hash=md5($userpass);
            $userid=$forminfo["userid"];
            $admin=new admin_m();
            $dbre=$admin->execute("update admin set password='$hash' where id='$userid'");
            if($dbre){
                $error="修改成功";
                $this->redirect('/reuser',array('error'=>$error,'id'=>$forminfo["userid"]));
            }else{
                $error="修改失败";
                $this->redirect('/reuser',array('error'=>$error,'id'=>$forminfo["userid"]));
            }
        }else{
            $error=$validata->getError();
            $this->redirect('/reuser',array('error'=>$error,'id'=>$forminfo["userid"]));
            exit();
        }
        
        
    }
    
    public function adduser(){
        $check=new check();
        $check->testlogin();
        
        $chadmin=$check->testadmin();
        if($chadmin==FALSE){
            $error="仅admin用户可执行";
            $this->redirect('/user',array('error'=>$error));
            exit();
        }
        
        $adduser=input('post.');
        $userinfo=$adduser["username"];
        $admin=new admin_m();
        $testinfo=$admin->query("select username from admin where username='$userinfo';");
        if(!empty($testinfo)){
            $error="用户名已注册";
            $this->redirect('/user',array('error'=>$error));
            exit();
        }
        $userpass=md5('123456');
        $dbre=$admin->execute("INSERT INTO admin (username,password) VALUES ('$userinfo','$userpass');");
        if($dbre){
            $error="注册成功(默认密码:123456)";
            $this->redirect('/user',array('error'=>$error));
            exit();
        }else{
            $error="注册失败";
            $this->redirect('/user',array('error'=>$error));
            exit();
        }
    }
    
    public function deluser(){
        $check=new check();
        $check->testlogin();
        
        $chadmin=$check->testadmin();
        if($chadmin==FALSE){
            $result="error";
            return $result;
            exit();
        }
      
        $userid=input('id');
        $user=new admin_m();
        $testinfo=$user->query("select username from admin where id=$userid;");
        $testuser=$testinfo[0]["username"];
        if(empty($testuser)){
            $result="no";
            return $result;
            exit();
        }
       
        if($testuser=="admin"){
            $result="noadmin";
            return $result;
            exit();
        } 
        $redb=$user->execute("delete from admin where id=$userid;");
        if($redb){
            $result="ok";
            return $result;
            exit();
        }else{
            $result="delno";
            return $result;
            exit();
        } 
       
    }
    
    public function modifypas(){
        $check=new check();
        $check->testlogin();
        
        $getdata=input('error');
        if(empty($getdata)){
            $this->assign('error',"");
        }else{
            $this->assign('error',$getdata);
        }
        
        
        $userid=session('id');
        $admin=new admin_m();
        $username=$admin->query("select username from admin where id='$userid';");
        $username=$username[0]["username"];
        $this->assign('username', $username);
        
        return view();
    }
    
    public function upownpass(){
        $check=new check();
        $check->testlogin();
        
        $forminfo=input('post.');
        $userid=session('id');
        
        $validata=new Validate([
            "password"=>"require",
            "repassword"=>"require|confirm:password",
            
        ],[
            "password.require"=>'密码不能为空',
            "repassword.require"=>'确认密码不能为空',
            "repassword.confirm"=>'两次密码不一致',
        ]);
        
        
        if ($validata->check($forminfo)){
            $testlength=$forminfo["password"];
            $length=strlen($testlength);
            if($length<6||$length>20){
                $error="密码长度需要在6-20之间";
                $this->redirect('/repass',array('error'=>$error));
            }
            $userpass=$forminfo["password"];
            $hash=md5($userpass);
            $admin=new admin_m();
            $dbre=$admin->execute("update admin set password='$hash' where id='$userid'");
            if($dbre){
                $error="修改成功";
                $this->redirect('/repass',array('error'=>$error));
            }else{
                $error="修改失败";
                $this->redirect('/repass',array('error'=>$error));
            }
        }else{
            $error=$validata->getError();
            $this->redirect('/repass',array('error'=>$error));
            exit();
        } 
        
    }
   
    public function resort($id){
        $check=new check();
        $check->testlogin();
        
        $error=input('error');
        if(empty($error)){
            $error="";
        }
        $this->assign('error',$error);
        
        $viinfo=Db::name('video')->alias('v')->join('sort s','v.sortid=s.id')->field('video.id,sortid,vname,vimg,name')->where('v.id',$id)->select();   
        $this->assign('viinfo',$viinfo);
       $nosortid=$viinfo[0]['sortid'];
        $sort=new sort_m();
        $sortinfo=$sort->query("select * from sort where id!=$nosortid;");
        $this->assign('sortinfo',$sortinfo);
        return view();
    }
    
    public function checksort(){
        $check=new check();
        $check->testlogin();
        
        $data=input('post.');
        $reid=$data['id'];
        $rename=$data['rename'];
        $resort=$data['sort'];
        $video=new video_m();
        $reinfo=$video->execute("update video set vname='$rename',sortid='$resort' where id=$reid;");
        if($reinfo==0){
            $error="未修改信息";
            $this->redirect('/resort',array('id'=>$reid,'error'=>$error));
        }elseif ($reinfo==1){
            $error="修改成功";
            $this->redirect('/resort',array('id'=>$reid,'error'=>$error));
        }else{
            $error="错误";
            $this->redirect('/resort',array('id'=>$reid,'error'=>$error));
        }
    }
   
    public function sort(){
        $check=new check();
        $check->testlogin();
        
        
        $geterror=input('error');
        if(empty($geterror)){
            $this->assign('error',"");
        }else{
            $this->assign('error',$geterror);
        }
        
      
        $sortinfo = Db::name('sort')->field('id,name,stime')->order('stime asc')->paginate(14);
      /*   $sortinfo=$sort->query("select * from sort;"); */
        $page = $sortinfo->render();
        $this->assign('sortinfo',$sortinfo);
        $this->assign('page', $page);
        return view();
    }
     
    public function delv(){
        $check=new check();
        $check->testlogin();
        
        $deldata=input('post.');
        if(empty($deldata)){
            $result="no";
            return $result;
            exit();
        }
        $deldata=$deldata['id'];
        $video=new video_m();
        $dbinfo=$video->query("select vpath,vimg from video where id=$deldata;");
        $dbinfo=$dbinfo[0];
        if(empty($dbinfo['vpath'])||empty($dbinfo['vimg'])){
            $result="no";
            return $result;
            exit();
        }
        $vfile='.'.VIDEO_DIR.'/'.$dbinfo['vpath'];
        $vimgfile='.'.VIDEOIMG_DIR.'/'.$dbinfo['vimg'];
        if(!file_exists($vfile)){
            if(file_exists($vimgfile)){
                unlink($vimgfile);
            }
            $return=$video->execute("delete from video where id=$deldata;");
            if($return){
                $result="nofile";
                return $result;
                exit();
            }else{
                $result="error";
                return $result;
                exit();
            }
            
        }else{
            if(!unlink($vfile)){
                $result="error";
                return $result;
                exit();
            }else{
                if(file_exists($vimgfile)){
                    unlink($vimgfile);
                }
                $return=$video->execute("delete from video where id=$deldata;");
                if($return){
                    $result="ok";
                    return $result;
                    exit();
                }else{
                    $result="error";
                    return $result;
                    exit();
                }
             }
        }
        
        
        
        /* $dbvpath="/".$dbinfo['vpath'];
        $vfile=VPATH. $dbvpath;
          unlink($vfile); 
        $result=$vfile; 
        return $result; */
        exit();
   
    }
     
    public function delsort(){
        $check=new check();
        $check->testlogin();
        
        $delsort=input('post.');
        $delid=$delsort['id'];
        $video=new video_m();
        $testvideo=$video->query("select id from video where sortid=$delid;");
        if(!empty($testvideo)){
            $result="no";
            return $result;
            exit();
        }
        $sort=new sort_m();
        $deldb=$sort->execute("delete from sort where id=$delid");
        if($deldb){
             $result="ok";
             return $result;
        }else{
            $result="error";
            return $result;
        }
       
    }
    
    public function addsort(){
        $check=new check();
        $check->testlogin();
        
        $addsort=input('post.');
        $addsort=$addsort['addsort'];
        $sort=new sort_m();
        $testinfo=$sort->query("select name from sort where name='$addsort';");
        if(!empty($testinfo)){
            $error="此分类已经存在";
            $this->redirect('/sort',array('error'=>$error));
            exit();
        }else{
            $addinfo=$sort->execute("insert into sort(name) value('$addsort');");
            if($addinfo){
                $error="添加成功";
                $this->redirect('/sort',array('error'=>$error));
                exit();
            }else{
                $error="添加失败";
                $this->redirect('/sort',array('error'=>$error));
                exit();
            }
        }
    }
     
    public function renamesort(){
        $check=new check();
        $check->testlogin();
        
        $getinfo=input('post.');
        $getid=$getinfo["Id"];
        $getvalue=$getinfo["Value"];
       
        $sort=new sort_m();
        $testname=$sort->query("select name from sort where name='$getvalue';");
        if(!empty($testname)){
            $result="error";
            return $result;
            exit();
        }else{
            $reinfo=$sort->execute("update sort set name = '$getvalue' WHERE (id = '$getid');");
            if($reinfo){
                $result="ok";
                return $result;
                exit();
            }else{
                $result="no";
                return $result;
                exit();
            }
        }
        
    }

    public function upfile(){
        $videopath=".".VIDEO_DIR."/";
        /* if(isset($_POST['file_name'])){
            $filename=$_POST['file_name'];
            $fileclass=substr(strrchr($filename, '.'), 1);
            $desname=md5($filename).".".$fileclass;
            $name=$videopath.$desnmae;
            dump($name);exit();
        }else{
            $name=null;
        } */
        
        
       /*  $desnmae= md5(date('YmdHis').$random).".".$fileclass; */
        
        
        /* dump($fileclass);
        dump($videopath);exit(); */
        
        header("Content-type: text/html; charset=utf-8");
        
        $file = isset($_FILES['file_data']) ? $_FILES['file_data']:null; //分段的文件
        
        $name = isset($_POST['file_name']) ? $videopath.$_POST['file_name']:null; //要保存的文件名

        /* if(isset($_POST['file_name'])){
            $filename=$_POST['file_name'];
            $fileclass=substr(strrchr($filename, '.'), 1);
            $desname=md5($filename).".".$fileclass;
            $name=$videopath.$desname;
            
        }else{
            $name=null;
        } */
        
        $total = isset($_POST['file_total']) ? $_POST['file_total']:0; //总片数
        
        $index = isset($_POST['file_index']) ? $_POST['file_index']:0; //当前片数
        
        $md5   = isset($_POST['file_md5']) ? $_POST['file_md5'] : 0; //文件的md5值
        
        $size  = isset($_POST['file_size']) ?  $_POST['file_size'] : null; //文件大小
        
        echo '当前片数：'.$index.PHP_EOL;
        

        
        if(!$file || !$name){
            echo 'failed';
            die();
        }
        
        if ($file['error'] == 0) {
            //检测文件是否存在
            if (!file_exists($name)) {
                if (!move_uploaded_file($file['tmp_name'], $name)) {
                    echo 'success'; 
                   
                }
            } else {
                $content = file_get_contents($file['tmp_name']);
                if (!file_put_contents($name, $content, FILE_APPEND)) {
                    echo 'failed';
                }
               
                echo 'success';
            }
        } else {
            echo 'failed';
        }
        
        if($index>=$total){
            $random=rand();
            $videoname=$_POST['file_name'];
            $fileclass=substr(strrchr($videoname, '.'), 1);
            $random=rand();
            $desname = md5(date('YmdHis').$random).".".$fileclass;
            $despath=$videopath.$desname;
            rename($name,$despath);
            
            $random=rand();
            $imgname = md5(date('YmdHis').$random).".jpg";
            $imgpath='.'.VIDEOIMG_DIR.'/'.$imgname;
            
            /*   ffmpeg -i test.asf -y -f image2 -t 0.001 -s 334x188 a.jpg */
            $str="ffmpeg -i "."\"".$despath."\""." -y -f image2 -t 0.001 -s 334x188 "."\"".$imgpath."\"";
            system($str);
            $sort=new sort_m();
            $sorid=$sort->query("select max(id) from sort;");
            $sorid=$sorid[0]['max(id)'];
            $invideo=new video_m();
           $reinfo=$invideo->execute("INSERT INTO video (vname, sortid, vpath, vimg) VALUES ('$videoname', '$sorid', '$desname', '$imgname');");
           
        }
        
       
        
    }
    
    public function logout(){
        session('id',null);
        session('tpvideo',null);
        $this->redirect('/');
        exit();
    }
    
}

class check extends Controller{
    public function testlogin(){
        $admin=new admin_m();
        $userid=session('id');
        $tpvideo=session('tpvideo');
        $dbinfo=$admin->query("select password from admin where id=$userid;");
        $password=$dbinfo[0]['password'];
        if($tpvideo!=$password){
            $error="用户信息已经过期";
            $this->redirect('/login',array('error'=>$error));
            exit();
        }
        
    }
    
    public function testadmin(){
        $admin=new admin_m();
        $userid=session('id');
        $dbinfo=$admin->query("select username from admin where id=$userid;");
        $username=$dbinfo[0]["username"];
        if($username=="admin"){
            return TRUE;
            exit();
        }else{
            return FALSE;
            exit();
        }
    }
    
}
