<?php
namespace app\admin\controller;
use app\admin\controller\BaseController;



	class Member extends BaseController
	{

	/**
	***	会员列表
	**/ 
		public function memberList()
		{
			$count = db("member")->count();
			$list = $this->members();

			$this->assign("count",$count);
			$this->assign("list",$list);
			return $this->fetch();
		}

	/**
	***	添加会员
	**/ 

		public function memberAdd()
		{
			if(!$_POST)
			{
				$memstand = $this->memberstand();
				$memgrade = $this->membergrade();
				$memtype = $this->membertype();
            
            	$this->assign("memtype",$memtype);
				$this->assign("memstand",$memstand);
				$this->assign("memgrade",$memgrade);
				return $this->fetch();
			}else{
				$_POST['at_time'] = date("Y-m-d H:i:s", time());
				$_POST['m_status'] = 0;
				$back = $this->sendMessForUser($_POST['mobile_phone'],$_POST['member_name']);
				$re = db("member")->insert($_POST);
				if ($re) {
					$lastid = db("member")->getLastInsID();
					$sre = db("member")
						 ->where("mid = ".$lastid)
						 ->setField("serial_number", $lastid);
					$this->success("操作成功！",'member/successHtml',1);
				}else{
					$this->error("操作失败，请重新操作！");
				}
			}
		}


	/**
	***	编辑会员
	**/ 

		public function memberedit()
		{
			if(!$_POST)
			{
				$mid = $_GET['mid'];
				$mem = db("member")
					 ->where('mid', $mid)
					 ->find();
					 
				$memstand = $this->memberstand();
				$memgrade = $this->membergrade();
				$memtype = $this->membertype();

				$this->assign("memtype",$memtype);
				$this->assign("memstand",$memstand);
				$this->assign("memgrade",$memgrade);
				$this->assign("mem",$mem);
        		return $this->fetch();
			}else{
				$mid = $_POST['mid'];
				$re = db("member")->where('mid', $mid)->update($_POST);
				if ($re) {
					$this->success("操作成功！",'member/successHtml',1);
				}else{
					$this->error("操作失败，请重新操作！");
				}
			}
		}



	/**
	***	会员消费
	**/ 
		public function memberShow()
		{
			if (!$_POST) {
				$id = $_GET['id'];
				return view('memberShow',['id'=>$id]);

			}else{
				$user = db('member')->find($_POST['uid']);
			    $counts = $user['subsidies']+$user['dividend'];  //会员总金额
			    $consume = db('consume')
			    		 ->where('uid ='.$_POST['uid'])
			    		 ->field('tip')
			    		 ->select();
			    //计算会员消费总数
			    $recount = '';
			    foreach ($consume as $v) {
			    	$recount += $v['tip'];
			    }
			    $remain = $counts-$recount;	  //计算会员历史消费余额
			    $tip = $_POST['tip'];
			    $rem = $remain-$tip;

			    if ($rem < 0) {
			    	$this->error("余额不足，请充值");exit;
			    }
				$_POST['time'] = date('Y-m-d H:i:s',time());
				$re = db('consume')->insert($_POST);
			    
				if ($re) {
				    
				    $lod = $this->sendMess($user['mobile_phone'],$user['name'],$_POST['tip'],$rem);
					$this->success("操作成功！",'member/successHtml',1);
				}else{
					$this->error("操作失败，请重新操作！");
				}

			}
		}


	/**
	***	会员停用
	**/ 
		public function memberout()
		{
			$mid = $_POST['mid'];
			$data['status'] = 0;
			$re = db("member")
				->where("mid",$mid)
				->setField('m_status',1);
			if ($re) {
				$data['status'] = 1;
			}

			return $data;
		}

	/**
	***	会员启用
	**/ 
		public function memberstart()
		{
			$mid = $_POST['mid'];
			$data['status'] = 0;
			$re = db("member")
				->where("mid",$mid)
				->setField('m_status',0);
			if ($re) {
				$data['status'] = 1;
			}

			return $data;
		}


	/**
	***	会员检索
	**/ 
		public function membersearch()
		{
			$s = $_POST['mem_search'];
			$list = $this->search($s);
			$count = count($list);

			$this->assign("list",$list);
			$this->assign("count",$count);
			$this->assign("search",$s);
			return $this->fetch();
		}

    /**
    *** 批量导出会员
    **/ 
	    public function membertoexcel()
	    {
	    	$res = $this->members();
	    	$this->toexcel($res);

	    }


    /**
    *** 批量导出检索会员数据
    **/ 
    	public function membersearchtoexcel()
    	{
    		$s = input('param.s');
    		$res = $this->search($s);
    		$this->toexcel($res);
    	}


    /**
    *** 批量充值导入临时页面并读取相应数据
    **/ 
    public function recharge()
    {
    	if (! $_POST) {
    		return view();
    	}else{
    		$files = request()->file('images');
	        foreach ($files as $file) {
	            $info = $file->move(ROOT_PATH.'public'.DS.'uploads'.DS.$path);
	            if ($info) {
	                
	                $saveName = $info->getSaveName();
	                // $arr = ["<input type='hidden' name='filename[]' value='".$saveName."' />"];
	                // echo json_encode($arr);
	                var_dump(1111);
	            }else{
	                echo $info->getError();
	            }
	        }
    	}
    }



/*******************  会员公共函数  *******************/ 

    /**
    *** 会员总数据
    **/
	    public function members()
	    {
	    	$list = db("member")
				  ->alias("m")
                  ->field('m.*,g.grade_name,t.type_name,s.standard_name,c.cons_name')
				  ->join('member_grade g','g.id = m.member_gid ','left')
				  ->join('standard s','s.id = m.member_sid','left')
				  ->join("member_type t", "m.member_tid = t.id",'left')
				  ->join("consumer c", "m.member_cid = c.cid",'left')
				  ->order('m.member_cid,m.mid')
				  ->select();
			return $list;
	    }

    /**
    *** 会员检索数据
    **/
    	public function search($s)
    	{
    		// 	消费者表消费者字段模糊查询
			$con = db("consumer")
				 ->field("cid")
				 ->where('cons_name','like','%'.$s.'%')
				 ->select();

			$cidarr = [999999999];
			if($con)
			{
				foreach ($con as $k => $v) {
					$cidarr[$k] = $v['cid'];
				}
			}

			// 	区域表字段模糊查询
			$mt = db("member_type")
				 ->field("id")
				->where("type_name",'like','%'.$s.'%')
				->select();

			$tidarr = [999999999];
			if($mt)
			{
				foreach ($mt as $k => $v) {
					$tidarr[$k] = $v['id'];
				}
			}

			// 	类别表字段模糊查询
			$mg = db("member_grade")
				 ->field("id")
				->where("grade_name",'like','%'.$s.'%')
				->select();

			$gidarr = [999999999];
			if($mg)
			{
				foreach ($mg as $k => $v) {
					$gidarr[$k] = $v['id'];
				}
			}
				
			// 	标准表字段模糊查询
			$sd = db("standard")
				 ->field("id")
				->where("standard_name",'like','%'.$s.'%')
				->select();

			$sidarr = [999999999];
			if($sd)
			{
				foreach ($sd as $k => $v) {
					$sidarr[$k] = $v['id'];
				}
			}

			// 	会员表字段模糊查询
			$list= db("member")
				 ->alias("m")
                 ->field('m.*,g.grade_name,t.type_name,s.standard_name,c.cons_name')
				 ->where("m.member_name",'like','%'.$s.'%')
				 ->whereOr("m.mobile_phone",'like','%'.$s.'%')
				 ->whereOr("m.member_addr",'like','%'.$s.'%')
				 ->whereOr('m.member_cid','in',$cidarr)
				 ->whereOr('m.member_tid','in',$tidarr)
				 ->whereOr('m.member_gid','in',$gidarr)
				 ->whereOr('m.member_sid','in',$sidarr)
				 ->join('member_grade g','g.id = m.member_gid ','left')
				 ->join('standard s','s.id = m.member_sid','left')
				 ->join("member_type t", "m.member_tid = t.id",'left')
				 ->join("consumer c", "m.member_cid = c.cid",'left')
				 ->order('m.member_cid,m.mid')
				 ->order('member_cid')
				 ->select();

			return $list;
    	}

    /**
    *** 会员数据Excel
    **/
    	public function toexcel($res)
    	{
    		\think\Loader::import("PHPExcel.PHPExcel",EXTEND_PATH);
            \think\Loader::import("PHPExcel.PHPExcel.Worksheet.Drawing",EXTEND_PATH);
            \think\Loader::import("PHPExcel.PHPExcel.Writer.Excel2007",EXTEND_PATH);
            $objPHPExcel = new \PHPExcel();
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

            $objActSheet = $objPHPExcel->getActiveSheet();

            // 水平居中（位置很重要，建议在最初始位置）
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('A')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('B')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('C')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('D')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('E')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('F')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('G')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('H')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('I')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('J')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('K')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->setActiveSheetIndex(0)->getstyle('L')->getAlignment()->setHorizontal(\PHPExcel_style_Alignment::HORIZONTAL_CENTER);

            //  设置excels第一行
            
            $objActSheet->setCellValue('A1','用户名');
            $objActSheet->setCellValue('B1','性别');
            $objActSheet->setCellValue('C1','手机');
            $objActSheet->setCellValue('D1','地址');
            $objActSheet->setCellValue('E1','区域');
            $objActSheet->setCellValue('F1','会员类别');
            $objActSheet->setCellValue('G1','套餐标准');
            $objActSheet->setCellValue('H1','其他联系方式');
            $objActSheet->setCellValue('I1','消费者');
            $objActSheet->setCellValue('J1','入会时间');
            $objActSheet->setCellValue('K1','状态');

            // 设置个表格宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(9);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(6);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(12);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(60);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(34);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(44);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(34);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(14);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(9);
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(11);
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(6);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);

            // 垂直居中
            $objPHPExcel->getActiveSheet()->getstyle('A')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('B')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('C')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('D')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('E')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('F')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('G')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('H')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('I')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('J')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('K')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getstyle('L')->getAlignment()->setVertical(\PHPExcel_style_Alignment::VERTICAL_CENTER);

            //其他行数据
            $row = 2;
            foreach($res as $k=>$v)
            {
                 $objActSheet->setCellValue('A'.$row,$v['member_name']);
                 switch($v['member_sex'])
                 {
                     case 1:
                         $timeing = '男';
                         break;
                     case 2:
                         $timeing = '女';
                         break;
                     case 3:
                         $timeing = '保密';
                         break;
                 }
                 $objActSheet->setCellValue('B'.$row,$timeing);
                 $objActSheet->setCellValue('C'.$row,$v['mobile_phone']);
                 $objActSheet->setCellValue('D'.$row,$v['member_addr']);
                 $objActSheet->setCellValue('E'.$row,$v['type_name']);
                 $objActSheet->setCellValue('F'.$row,$v['grade_name']);
                 $objActSheet->setCellValue('G'.$row,$v['standard_name']);
                 $objActSheet->setCellValue('H'.$row,$v['family_phone']);
                 $objActSheet->setCellValue('I'.$row,$v['cons_name']);
                 $objActSheet->setCellValue('J'.$row,$v['ad_time']);

                 switch($v['m_status'])
                 {
                     case 0:
                         $pact = '启用';
                         break;
                     case 1:
                         $pact = '停用';
                         break;
                 }
                 $objActSheet->setCellValue('K'.$row,$pact);

                 $row++;
            }


            $fileName = '会员表';
            $date = date("Y-m-d H:i:s",time());
            $fileName .= "_{$date}.xls";

            $fileName = iconv("utf-8", "gb2312", $fileName);

            $objPHPExcel->setActiveSheetIndex(0);
            header('Content-Type: application/vnd.ms-excel');
            header("Content-Disposition: attachment;filename=\"$fileName\"");
            header('Cache-Control: max-age=0');

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output'); //文件通过浏览器下载
    	}

        public function dealExcel()
        {
            $file = request()->file();
            if( isset($file) ){
                header('Content-type:text/json');
                $info = $file["images"]->move(ROOT_PATH . 'public' . DS . 'upload'. DS .'payExcel');
                if($info){
                    $path = ROOT_PATH . 'public' . DS . 'upload'. DS .'payExcel'. DS .$info->getSaveName();

                    \think\Loader::import("PHPExcel.PHPExcel.IOFactory",EXTEND_PATH);
                    $reader = \PHPExcel_IOFactory::createReader('Excel2007'); //设置以Excel5格式(Excel97-2003工作簿)
                    $PHPExcel = $reader->load($path); // 载入excel文件
                    $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
                    $arr_phone = [];
                    $err = [];
                    if(strlen($sheet->getCell('A1')->getValue()) == 11)
                    {
                        $col = 1;
                        $total = 0;
                        $effice = 0;
                        while ( ($phone = $sheet->getCell('A'.$col)->getValue()) != '' )
                        {
                            $member = db('member')->where("'".trim($phone)."'=mobile_phone")->find();
                            $pay = $sheet->getCell('B'.$col)->getValue();
                            if(is_numeric($pay))
                            {
                                $total += $pay;
                                if($member){
                                    $effice += $pay;
                                    $arr_phone[] = [
                                        'id'=>$member['mid'],
                                        'name'=>$member['member_name'],
                                        'phone'=> $phone,
                                        'pay'=> $sheet->getCell('B'.$col)->getValue()
                                    ];
                                }
                                else{
                                    $err[] = [
                                        'id'=>$member['mid'],
                                        'name'=>$member['member_name'],
                                        'phone'=> $phone,
                                        'pay'=> $sheet->getCell('B'.$col)->getValue()
                                    ];
                                }
                            }
                            else{
                                $err[] = [
                                    'id'=>$member['mid'],
                                    'name'=>$member['member_name'],
                                    'phone'=> $phone,
                                    'pay'=> $sheet->getCell('B'.$col)->getValue()
                                ];
                            }

                            $col++;
                        }
                        print json_encode(['list'=>$arr_phone,'err'=>$err,'tal'=>$total,'eff'=>$effice]);
                        exit;
                    }
                    else{
                        print json_encode(['status'=>false,'mess'=>'文件格式不对']);
                        exit;
                    }

                }else{
                    // 上传失败获取错误信息
                    echo $file->getError();
                }
            }

        }

        public function addPay()
        {
            $mid = input('post.id/a');
            $pay = input('post.pay/a');

            foreach($mid as $k=>$v)
            {
                if($v == 'null' || !is_numeric($pay[$k]))continue;
                $res = db('member')->where('mid='.$v)->setInc('bonus',$pay[$k]);

                if($res)
                {
                    $ult = db('pay_record')->insert(['uid'=>$v,'inpay'=>$pay[$k],'pay_time'=>date('Y-d-m h:i:s',time()),'type'=>1]);
                    if(!$ult)
                    {
                        db('member')->where('mid='.$v)->setDec('bonus',$pay[$k]);
                    }
                }
            }
        }

	}