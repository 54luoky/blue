<?php
namespace app\admin\controller;
use app\admin\controller\BaseController;
use think\Db;
class Consume extends BaseController
{
    public function lists()
    {
        $back_url = $_SERVER['HTTP_REFERER'];
        $uid = input('get.id');
        if( $uid )
        {
            $lists = Db::table('blue_consume')
                ->alias('c')
                ->join('blue_consumer b','b.cid=c.uid','left')
                ->where('uid='.$uid)
                ->order('c.id DESC')
                ->select();
        }
        else
        {
            $lists = Db::table('blue_consume')
                ->alias('c')
                ->join('blue_consumer b','b.cid=c.uid','left')
                ->order('c.id DESC')
                ->select();
        }
        return view('article-list',['lists'=>$lists,'back_url'=>$back_url]);
    }

    public function editor()
    {
        $id = \think\Request::instance()->param('id');
        $consume = Db::table('blue_consume')
            ->where('id='.$id)
            ->find();
        return view('editor',['con'=>$consume]);
    }

    /**
     *  消费者列表
     */
    public function consumerlist()
    {
        $list = db("consumer")
              ->alias("c")
              ->join("member_type t", "c.cons_tid = t.id")
              ->order("c.cid DESC")
              ->select();

        foreach ($list as $k => $v) {
            $sid = $v['cons_sid'];
            if ($sid) {
                $stand = db("standard")
                       ->where("id", $sid)
                       ->find();
                $list[$k]['standard_name'] = $stand['standard_name'];
            }else{
                $list[$k]['standard_name'] = '';
            }
        }

        $count = count($list);
        $this->assign("list",$list);
        $this->assign("count",$count);
        return $this->fetch();
    }

    /**
     *  消费者添加
     *  初始密码是111111  => 6 个 1
     */
    public function consumeradd()
    {
        if (! $_POST) {

            $memstand = $this->memberstand();
            $memtype = $this->membertype();
            
            $this->assign("memstand",$memstand);
            $this->assign("memtype",$memtype);
            return $this->fetch();
        }else{
            //  盐
            $str = '222222';
            $_POST['at_time'] = date("Y-m-d H:i:s", time());
            $_POST['up_time'] = date("Y-m-d H:i:s", time());
            $_POST['gt_time'] = date("Y-m-d H:i:s", time());
            $_POST['c_status'] = 0;
            $_POST['cons_pwd'] = md5($str."111111".$str);
            
            $re = db("consumer")->insert($_POST);
            if ($re) {
                $this->success("操作成功！",'admin/member/successHtml',1);
            }else{
                $this->error("操作失败，请重新操作！");
            }
        }
    }

    /**
     *  消费者编辑
     */
    public function consumeredit()
    {
        if (! $_POST) {
            $cid = $_GET['cid'];
            $cons = db("consumer")
                  ->where('cid',$cid)
                  ->find();
            $memstand = $this->memberstand();
            $memtype = $this->membertype();
            
            $this->assign("memstand",$memstand);
            $this->assign("memtype",$memtype);
            $this->assign("cons",$cons);
            return $this->fetch();
        }else{
            $_POST['up_time'] = date("Y-m-d H:i:s", time());
            $cid = $_POST['cid'];
            $re = db("consumer")
                ->where("cid",$cid)
                ->update($_POST);
            if ($re) {
                $this->success("操作成功！",'admin/member/successHtml');
            }else{
                $this->error("操作失败，请重新操作！");
            }
        }
    }


    /**
    *** 消费者停用
    **/ 
        public function consumerout()
        {
            $mid = $_POST['mid'];
            $data['status'] = 0;
            $re = db("consumer")
                ->where("cid",$mid)
                ->setField('c_status',1);
            if ($re) {
                $data['status'] = 1;
            }

            return $data;
        }

    /**
    *** 消费者启用
    **/ 
        public function consumerstart()
        {
            $mid = $_POST['mid'];
            $data['status'] = 0;
            $re = db("consumer")
                ->where("cid",$mid)
                ->setField('c_status',0);
            if ($re) {
                $data['status'] = 1;
            }

            return $data;
        }

























}
