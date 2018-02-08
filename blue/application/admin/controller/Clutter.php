<?php
/**
 * Created by PhpStorm.
 * User: dream
 * Date: 2018/2/2
 * Time: 21:37
 */
namespace app\admin\controller;
use think\Controller;
use think\Loader;
class Clutter extends Controller{

    /*
     * 会员等级管理
     */
    //等级列表
    public function grade_list()
    {
        $lists = db('member_grade')->select();
        return $this->fetch('grade_lists',['lists'=>$lists]);
    }

    //等级添加
    public function grade_add()
    {
        $requeat = \think\Request::instance();
        if($requeat->isPost())
        {
            $data = $requeat->post();
            $data['grade_status'] = 1;
            $res = db('member_grade')->insert($data);
        }
        else
        {
            return $this->fetch('grade_add');
        }
    }

    //等级编辑
    public function grade_edit()
    {
        $requeat = \think\Request::instance();
        if($requeat->isPost())
        {
            $data = $requeat->post();
            $res = db('member_grade')->update($data);
        }
        else
        {
            $editor = db('member_grade')->find($requeat->param('id'));
            return $this->fetch('grade_edit',['one'=>$editor]);
        }
    }

    //等级状态修改
    public function grade_change()
    {
        $id = \think\Request::instance()->get('id');
        $status = \think\Request::instance()->get('status');
        $res = db('member_grade')->update(['id'=>$id,'grade_status'=>$status]);
    }

    /*
     * 会员区域管理
     */
    //区域列表
    public function dis_list()
    {
        $lists = db('member_type')->select();
        $list = $this->build_tree($lists);
        $string = $this->arrayTostr($list);
        return $this->fetch('dis_lists',['string'=>$string,'lists'=>$lists]);
    }

    public function arrayTostr($array,$pri='')
    {
        $string = '';
        foreach($array as $k=>$v)
        {
            $string .= '<tr class="text-c">
							<td>'.($k+1).'</td>
							<td style="text-align:left" class="text-primary" >'.$pri.$v['type_name'].'</td>';
            if($v['type_status'] == 0)
            {
                $string .= '<td>停用</td>
                            <td class="td-manage">
								<a title="编辑" href="javascript:;" onclick="member_edit(\'编辑\',\' '.Url("admin/clutter/dis_edit", "id=" . $v["id"]).'\',\'4\',\'\',\'510\')" class="ml-5" style="text-decoration:none"><u style="cursor:pointer" class="text-primary">编辑</u></a>	
								<a title="启用" href="javascript:;" onclick="member_upl('.$v['id'].',1)" class="ml-5" style="text-decoration:none"><u style="cursor:pointer" class="text-primary">启用</u></a>
							</td>';
            }
            else
            {
                $string .= '<td>启用</td>
                            <td class="td-manage">
								<a title="编辑" href="javascript:;" onclick="member_edit(\'编辑\',\' '.Url("admin/clutter/dis_edit", "id=" . $v["id"]).' \',\'4\',\'\',\'510\')" class="ml-5" style="text-decoration:none"><u style="cursor:pointer" class="text-primary">编辑</u></a>
								<a title="禁用" href="javascript:;" onclick="member_upl('.$v['id'].',0)" class="ml-5" style="text-decoration:none"><u style="cursor:pointer" class="text-primary">禁用</u></a>
							</td>';
            }

            if(!empty($v['son']))
            {
                $string .= $this->arrayTostr($v['son'],$pri.'&nbsp;—|&nbsp;');
            }

        }
        return $string;
    }

    public function build_tree($lists,$pid = null)
    {
        $array = [];
        $all = [];
        foreach($lists as $k=>$v)
        {
            if($pid == null)
            {
                if($v['type_pid'] == 0)
                {
                    $v['son'] =  $this->build_tree($lists,$v['id']);
                    $array[$k] = $v;
                }
            }
            else
            {
                if($v['type_pid'] == $pid)
                {
                    $v['son'] =  $this->build_tree($lists,$v['id']);
                    $array[] = $v;
                }
            }
        }
        return $array;
    }

    //区域添加
    public function dis_add()
    {
        $requeat = \think\Request::instance();
        if($requeat->isPost())
        {
            $data = $requeat->post();
            $data['type_status'] = 1;
            $res = db('member_type')->insert($data);
        }
        else
        {
            $pid = $requeat->get('id');
            $lists = db('member_type')->select();
            $list = $this->build_tree($lists);
            $option = $this->build_pid($list);
            $option = '<option value="0">顶级区域</option>'.$option;
            return $this->fetch('dis_add',['pid'=>$pid,'option'=>$option]);
        }
    }

    public function build_pid($list,$pri='')
    {
        $string = '';
        foreach($list as $k=>$v)
        {
            $string .= '<option value="'.$v['id'].'">'.$pri.$v['type_name'].'</option>';
            if(!empty($v['son']))
            {
                $string .= $this->build_pid($v['son'],$pri.'&nbsp;—|&nbsp;');
            }

        }
        return $string;
    }

    //区域编辑
    public function dis_edit()
    {
        $requeat = \think\Request::instance();
        if($requeat->isPost())
        {
            $data = $requeat->post();
            $res = db('member_type')->update($data);
        }
        else
        {
            $one = db('member_type')->find($requeat->param('id'));
            $lists = db('member_type')->select();
            $list = $this->build_tree($lists);
            $option = $this->option_check($one['type_pid'],$list);
            if($one['type_pid'] == 0)
            {
                $option = '<option value="0" selected>顶级区域</option>'.$option;
            }
            else{
                $option = '<option value="0">顶级区域</option>'.$option;

            }
            return $this->fetch('dis_edit',['one'=>$one,'option'=>$option]);
        }
    }

    public function option_check($pid,$list,$pri='')
    {
        $string = '';
        foreach($list as $k=>$v)
        {
            if($v['id'] == $pid)
            {
                $string .= '<option value="'.$v['id'].'" selected>'.$pri.$v['type_name'].'</option>';
            }
            else
            {
                $string .= '<option value="'.$v['id'].'">'.$pri.$v['type_name'].'</option>';
            }
            if(!empty($v['son']))
            {
                $string .= $this->option_check($pid,$v['son'],$pri.'&nbsp;—|&nbsp;');
            }

        }
        return $string;
    }

    //区域状态修改
    public function dis_change()
    {
        $id = \think\Request::instance()->get('id');
        $status = \think\Request::instance()->get('status');
        $res = db('member_type')->update(['id'=>$id,'type_status'=>$status]);
    }




    /*
     * 会员套餐管理
     */
    //套餐列表
    public function std_list()
    {
        $lists = db('standard')->select();
        return $this->fetch('std_list',['lists'=>$lists]);
    }

    //套餐添加
    public function std_add()
    {
        $requeat = \think\Request::instance();
        if($requeat->isPost())
        {
            $data = $requeat->post();
            $data['standard_status'] = 1;
            $res = db('standard')->insert($data);
        }
        else
        {
            return $this->fetch('std_add');
        }
    }

    //套餐编辑
    public function std_edit()
    {
        $requeat = \think\Request::instance();
        if($requeat->isPost())
        {
            $data = $requeat->post();
            $res = db('standard')->update($data);
        }
        else
        {
            $one = db('standard')->find($requeat->param('id'));
            return $this->fetch('std_edit',['one'=>$one]);
        }
    }




    //套餐状态修改
    public function std_change()
    {
        $id = \think\Request::instance()->get('id');
        $status = \think\Request::instance()->get('status');
        $res = db('standard')->update(['id'=>$id,'standard_status'=>$status]);
    }
}