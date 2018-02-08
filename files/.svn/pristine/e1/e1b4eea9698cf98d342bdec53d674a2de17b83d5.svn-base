<?php
namespace app\index\controller;
use app\index\controller\BaseController;
class Index extends BaseController
{
	/**
     * *
     * @return [index] [档案管理首页]
     * @return [filelist] [档案管理文件列表]
     * @return [divisionlist] [档案管理各中心/事业部列表]
     */
    public function index()
    {
    	$nav = $this->files_nav();
    	$nav_count = count($nav);
    	$this->assign('nav',$nav);
    	$this->assign('nav_count',$nav_count);
        return $this->fetch();
    }


    public function filelist()
    {
    	$fid = $_GET['fid'];
    	$nav = $this->files_nav($fid);
    	$fnav = db('indexNav')->where('nid = '.$fid)->find();
    	$this->assign('fnav',$fnav);
    	$this->assign('nav',$nav);
    	return $this->fetch();
    }

    public function divisionlist()
    {
        $navId = $_GET['navfid'];
        //  查找菜单名
        $navName = db('indexNav')
                 ->where("nid = $navId")
                 ->find();
        //  查找当前上一级菜单
        $fnav = db('indexNav')
              ->where('nid = '.$navName['fid'])
              ->find();
        //  查找公司中心/事业部
        $data = $this->company();

        $this->assign("navid",$navId);
        $this->assign("navName",$navName);
        $this->assign("fnav",$fnav);
        $this->assign("company",$data);
        return $this->fetch();
    }













































}
