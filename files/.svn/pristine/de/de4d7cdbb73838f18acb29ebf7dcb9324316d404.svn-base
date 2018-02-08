<?php
namespace app\index\controller;
use app\index\controller\BaseController;
class Contract extends BaseController
{
    /**
     * 
     * 	@return [contractlist] [合同管理下指定事业部/中心合同列表]
     * 
    **/
    
    public function contractlist()
    {
    	return view();
    }


    public function uploadFlies()
    {
    	$file = request()->file('image');
    	$info = $file->move(ROOT_PATH.'public'.DS.'uploads');
    	if ($info) {
    		echo $info->getExtension();echo 111;
    		echo $info->getSaveName();echo 222;
    		echo $info->getFilename();
    	}else{
    		echo $info->getError();
    	}
    }
































}