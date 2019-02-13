<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use \app\index\model\Sort as sort_m;
use \app\index\model\Video as video_m;
class Index extends Controller{
    public function index(){   
        $sor=new sort_m();
        $sortinfo=$sor->query("select * from sort");
        $this->assign('sortinfo',$sortinfo);     
       /*  $info2=$sor::execute("INSERT INTO sort (`name`) VALUES ('kali');");*/
        $video=new video_m;
        $viinfo = Db::name('video')->field('id,vname,vimg,time')->order('time')->paginate(18);
        /* $viinfo=$video->query("select id,vname,vimg,time from video  order by time  limit 18"); */
        $this->assign('viinfo',$viinfo); 
        $page = $viinfo->render();
        $this->assign('page', $page);
        return view();   
    }
    public function video($id){
        $sor=new sort_m();
        $sortinfo=$sor->query("select * from sort");
        $this->assign('sortinfo',$sortinfo);
        $video=new video_m;
        $sortid=$video->query("select sortid from video where id=$id;");
        $this->assign('sortid',$sortid);
        $viinfo=$video->query("select vname,sortid,vpath,video.time,name from video inner JOIN sort ON (video.sortid=sort.id) where video.id=$id;");
        $this->assign('viinfo',$viinfo);
        $sortid=$viinfo[0]['sortid'];
        $sorinfo=$sor->query("select vname,vimg,id from video where sortid=$sortid limit 3;");
        $this->assign('sorinfo',$sorinfo);
        
        
        return view();
    }
    public function sort($id){
        $sor=new sort_m();
        $sortinfo=$sor->query("select * from sort");
        $this->assign('sortinfo',$sortinfo);
        $sortname=$sor->query("select name from sort where id=$id;");
        $sortname=$sortname[0]['name'];
        $this->assign('sortname',$sortname);
        $viinfo = Db::name('video')->field('id,vname,vimg,time')->where('sortid',$id)->order('time')->paginate(18);
        $this->assign('viinfo',$viinfo);
        $page = $viinfo->render();
        $this->assign('page', $page);
       
        return view();
    }
    public function search(){
        $sor=new sort_m();
        $sortinfo=$sor->query("select * from sort");
        $this->assign('sortinfo',$sortinfo);
        $video=new video_m();
        $info=$_POST['info'];
        $this->assign('info',$info);
        $info='%'.$info.'%';
      /*   $viinfo=Db::name('video')->field('id,vname,vimg,time')->where('vname','like',$info)->order('time')->paginate(18); */
        $viinfo=Db::name('video')->alias('v')->join('sort s','v.sortid=s.id')->field('video.id,vname,vimg,time,name')->where('vname|name','like',$info)->order('time')->paginate(18);
     /*    $viinfo=$video->query("select id,vname,vimg,time from video where vname like '%$info%'"); */
        $this->assign('viinfo',$viinfo);
        $page = $viinfo->render();
        $this->assign('page', $page);
        return view();
        
    }
    
   
    
    public function refresh(){
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
                    $video->execute("INSERT INTO video (vname, sortid, vpath, vimg) VALUES ('$value', '$sorid', '$despath', '$imgname');
");
                    
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
                $this->redirect('/index');
                exit();
                
                
    }
    
    
    
    
}
