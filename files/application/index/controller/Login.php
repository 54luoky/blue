<?php
namespace app\index\controller;
use app\index\controller\BaseController;
class Login extends BaseController
{
    public function login()
    {
        if(!$_POST){
        	return view();
        }else{
        	$user = preg_replace('# #','',$_POST['username']);
        	$pwd = md5($_POST['password']);
        	$data = $this->SqlServerName()->name('hrmresource')->where("workcode = '$user' and password = '$pwd'")->find();
        	if($data)
        	{
        		$this->redirect('index/index/index');
        	}else{
        		$this->error("账户或密码错误，登录失败，请重新登录！");
        	}
        }
    }
}
