<?php
namespace app\admin\controller;
use app\admin\model\Admin;
use think\Controller;
class Index extends Controller
{
	public function __initialize()
	{
		echo 'init';
	}
	
     public function index()
    {
        if(request()->isPost()){
            $admin=new Admin();
            $data=input('post.');
            $num=$admin->login($data);
            if($num==3){
                $this->success('信息正确，正在为您跳转...','index/lst');
            }else{
                $this->error('用户名或者密码错误');
            }

        }
        return $this->fetch('index');
    }
    
    public function lst()
    {
        return $this->fetch('lst');
    }



}