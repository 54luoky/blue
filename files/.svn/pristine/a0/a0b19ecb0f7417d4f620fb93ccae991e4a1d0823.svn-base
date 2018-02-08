<?php
namespace app\index\controller;
use think\Controller;

class BaseController extends Controller
{


/*********** 公共函数 ***********/ 


	/**
	***	连接OA数据库配置
	**/
	public function SqlServerName()
	{
		$ob =  \think\Db::connect([
        		// 数据库类型
		        'type'            => 'sqlsrv',
		        // 服务器地址
		        'hostname'        => '10.1.20.200',
		        // 数据库名
		        'database'        => 'ecology',
		        // 用户名
		        'username'        => 'audit',
		        // 密码
		        'password'        => 'rn@123#',
        	]);
		return $ob;
	}

	/**
	 * *菜单
	 */
	public function files_nav($fid = 0)
	{
		$nav = db("indexNav")->where("fid = $fid")->select();
		return $nav;
	}
	/**
	 * *查找中心/事业部
	 */
	public function company()
	{
		$data = $this->SqlServerName()
              ->name('hrmsubcompany')
              ->where("(canceled <> 1 or canceled is null) and subcompanyname not in ('客户')")
              ->select();
        return $data;
	}








}