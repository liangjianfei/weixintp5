<?php

namespace app\index\controller;

use think\Request;
use think\Session;

class User extends WeChat
{
    public function __construct()
    {
        parent::__construct();
        if ($this->is_weixin()&&!Session::get('uinfo')) {
            $this->dowxlogin();
        }
    }

    //微信登录
    public function dowxlogin()
    {
        $wechat = $this->getInstance();
        if (!isset($_GET['code'])) {
            $backurl = $this->get_url();
            $_SESSION['gourl'] = $backurl;
            $url = $wechat->getOauthRedirect($backurl, '', 'snsapi_base');//snsapi_base,snsapi_userinfo
            $this->redirect($url);
        } else {
            $jsonArr = $wechat->getOauthAccessToken();
            $openid = $jsonArr['openid'];
            $where['wx_openid'] = $openid;
            $userInfo = db('Users')->where($where)->find();
            $wxinfo = $wechat->getUserInfo($openid);
            //公共信息
            $data['user_login'] = $data['wx_openid'] = $data['user_pass'] = $openid;
            $request = Request::instance();
            $ip_address = $request->ip();
            $data['regip'] = $ip_address;
            $data['user_pass'] = md5($data ['user_login'] . $data ['user_pass'] . cache('PWD_SALA'));
            $data['create_time'] = date('Y-m-d H:i:s');

            if ($wxinfo['subscribe'] == 0 && !is_array($userInfo)) {
                //未关注注册
                $uid = db("Users")->insert($data);
                if ($uid) {
                    //设置session
                    Session::set('uinfo', $openid);
                    $url = url('Index/User/user');
                    $this->redirect($url);
                    exit;
                }
            }
            if ($wxinfo['subscribe'] == 1) {
                $data['avatar'] = $wxinfo['headimgurl'];
                $data['user_nicename'] = $wxinfo['nickname'] ? $wxinfo['nickname'] : 'ds' . date('ymdHis', time()) . rand(1111, 9999);
                $data['country'] = $wxinfo['country'];
                $data['province'] = $wxinfo['province'];
                $data['city'] = $wxinfo['city'];
                $data['subscribe'] = $wxinfo['subscribe'] ? $wxinfo['subscribe'] : 0;
                $data['subscribe_time'] = $wxinfo['subscribe_time'] ? $wxinfo['subscribe_time'] : 0;
                $data['sex'] = $wxinfo['sex'];
                if (!is_array($userInfo)) {
                    //关注注册
                    $uid = db("Users")->insert($data);
                    if ($uid) {
                        //设置session
                        Session::set('uinfo', $openid);
                        dump($openid);
                        exit();

                        $url = url('Index/User/user');
                        $this->redirect($url);
                        exit;
                    }
                }
                if (is_array($userInfo) && $userInfo['subscribe'] != $wxinfo['subscribe']) {
                    //未关注再关注 更新数据库信息
                    $uid = db('Users')->where($where)->update($data);
                    if ($uid) {
                        //设置session
                        Session::set('uinfo', $openid);
                    }
                }
            }
        }
    }

    //退出登录
    public function dologout()
    {
        $cook = cookie('checklogin');
        $ucookie = json_decode(stripslashes($cook), true);
        cache('uinfo' . $ucookie['check'], null);
        cookie('checklogin', null, 3600);
        cookie('gourl', null, 3600);

        $this->success('退出成功！', url('Index/User/user'));

    }


    public function curlg($url, $fromurl = NULL, $fromip = NULL, $uagent = NULL, $timeout = 1, $host = NULL)
    {//php 模拟get
        ob_start();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //ssl证书不检验
        if ($fromip) curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:' . $fromip, 'CLIENT-IP:' . $fromip));  //构造IP
        if ($fromurl) curl_setopt($ch, CURLOPT_REFERER, $fromurl);   //构造来路
        //curl_setopt($ch, CURLOPT_ENCODING ,gzip);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $uagent ? $this->useragent(1) : $this->useragent());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $file_msg = curl_exec($ch);
        curl_close($ch);
        //dump($ch);
        if ($file_msg === false) return file_get_contents($url);
        return $file_msg;
    }

    public function curlp($post_url, $xjson)
    {//php post
        $ch = curl_init($post_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //ssl证书不检验
        curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xjson);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($xjson))
        );
        $respose_data = curl_exec($ch);
        return $respose_data;
    }

    private function useragent($mobile = null)
    {
        $ua1 = 'Mozilla/5.0 (Windows NT 5.1; rv:25.0) Gecko/20100101 Firefox/25.0';
        $ua2 = 'Mozilla/5.0 (Windows NT 6.0) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1';
        $ua3 = 'Mozilla/5.0 (Windows NT 6.1; rv:25.0) Gecko/20100101 Firefox/25.0';
        $ua4 = 'Mozilla/5.0 (Windows NT 6.2; rv:25.0) Gecko/20100101 Firefox/25.0';
        $ua5 = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1';
        $ua6 = 'Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1';
        $ua7 = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2';
        $ua8 = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET4.0C; .NET CLR 3.0.04506.30; InfoPath.2; .NET4.0E; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)';
        $ua9 = 'Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET4.0C; .NET CLR 3.0.04506.30; InfoPath.2; .NET4.0E; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)';
        $ua10 = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET4.0C; .NET CLR 3.0.04506.30; InfoPath.2; .NET4.0E; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)';
        $ua11 = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET4.0C; .NET CLR 3.0.04506.30; InfoPath.2; .NET4.0E; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)';
        $uaarr = array($ua1, $ua2, $ua3, $ua4, $ua5, $ua6, $ua7, $ua8, $ua9, $ua10, $ua11);
        if ($mobile) {
            return 'Mozilla/5.0 (Linux; Android 4.4.4; HM NOTE 1LTEW Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 MicroMessenger/6.0.2.56_r958800.520 NetType/3gnet';
            //Mozilla/5.0 (Linux; Android 4.4.4; HM NOTE 1LTEW Build/KTU84P) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36 MicroMessenger/6.0.2.56_r958800.520 NetType/3gnet
            //Mozilla/5.0 (iPhone; CPU iPhone OS 7_0_4 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Mobile/11B554a MicroMessenger/5.2
        } else {
            return $uaarr[rand(0, count($uaarr) - 1)];
        }
    }

    protected function is_weixin()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }

    protected function get_url()
    {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];

        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
    }

    //测试方法
    public function user()
    {
        return $this->fetch();
    }
}