<?php
namespace app\admin\controller;
use app\admin\controller\BaseController;
class Login extends BaseController
{
    public function login()
    {
        return view();
    }

    public function checkLogin()
    {
    	$user = $_POST['username'];
    	$pwd = $_POST['password'];
    	$ver = $_POST['verify'];
    	$status = 0;
    	if(captcha_check($ver)){
    		$status = 1;
            $re = db("adminuser")
                ->where("username", $user)
                ->where("password", $pwd)
                ->find();
            if (!$re) {
                $status = 2;
            }
    	}
    	return $status;
    }

}
