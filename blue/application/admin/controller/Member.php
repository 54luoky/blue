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
			$list = db("member")
				  ->alias("m")
                  ->field('blue_member.*,g.grade_name,t.type_name,s.standard_name,c.cons_name')
				  ->join('member_grade g','g.id = m.member_gid ','left')
				  ->join('standard s','s.id = m.member_sid','left')
				  ->join("member_type t", "m.member_tid = t.id",'left')
				  ->join("consumer c", "m.member_cid = c.cid",'left')
				  ->order('m.member_cid,m.mid')
				  ->select();

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



















	}