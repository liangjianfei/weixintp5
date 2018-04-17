<?php
namespace app\index\controller;

use think\Controller;
class WeChat extends Controller
{
    public function index(){
        import('Wechat.wechat', EXTEND_PATH,'.php');
        $options = array(
            'token'         =>config('TOKEN'), //填写你设定的key
            'encodingaeskey'=>config('ENCODINGAESKEY'), //填写加密用的EncodingAESKey
            'appid'         =>config('APPID'), //填写高级调用功能的app id
            'appsecret'     =>config('APPSECRET') //填写高级调用功能的密钥
        );
        $wechat = new \Wechat($options);
        $wechat->valid();
        $wechat->getRev();
        cache('wechat',$wechat);
        cache('xml',$wechat->getRevData());
        $this->doRev($wechat);
    }

    public function getInstance(){
        import('Wechat.wechat', EXTEND_PATH,'.php');
        $options = array(
            'token'         =>config('TOKEN'), //填写你设定的key
            'encodingaeskey'=>config('ENCODINGAESKEY'), //填写加密用的EncodingAESKey
            'appid'         =>config('APPID'), //填写高级调用功能的app id
            'appsecret'     =>config('APPSECRET') //填写高级调用功能的密钥
        );
        $wechat = new \Wechat($options);
        return $wechat;
    }

    //执行微信端返回的数据
    public function doRev($wechat){
        $xml = $wechat->getRevData();
        cache('xml',$xml);
        $openid = $xml['FromUserName'];
        cache('openid',$openid);
        switch($xml['MsgType']){
            case 'event':
                switch($xml['Event']){
                    case 'subscribe'://关注
                        /*["ToUserName"] => string(15) "gh_d16fd4b359b1"
                        ["FromUserName"] => string(28) "oWm6q0zvjAsWsxMsJx1ESLoYlQAI"
                        ["CreateTime"] => string(10) "1503557690"
                        ["MsgType"] => string(5) "event"
                        ["Event"] => string(9) "subscribe"
                        ["EventKey"] => string(9) "qrscene_1"
                        ["Ticket"] => string(96) "gQF-7zwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyYnVoeGxNcUtlR2oxSERGRHhwMXgAAgRnb55ZAwSAOgkA"*/
                        $userMod = db('Users');
                        $wxinfo = $wechat->getUserInfo($openid);//获取用户微信信息
                        cache('wxinfo',$wxinfo);
                        if($xml['Event'] && $xml['Ticket']){//二维码关注
                            $pid  = str_replace('qrscene_','',$xml['EventKey']);
                            //前台用户从10000开始自增长
                            if($pid < 10000){
                                //后台推广
                                $data['parent_id'] = $pid;
                                //TODO 推广统计
                            }else{
                                //前台推广
                                $pinfo = $userMod->find($pid);
                                if($pinfo){
                                    $data['parent_id'] = $pid;
                                    $message = [
                                        "touser" => $pinfo['wx_openid'],
                                        "msgtype" => "text",
                                        "text" => ["content"=>"【".$wxinfo["user_nicename"]."】通过您分享的二维码关注了公众号，Ta注册后您有可能获得奖励。"]
                                    ];
                                    $wechat->sendCustomMessage($message);
                                }
                            }

                        }
                        $data['user_login'] = $openid;
                        $data['avatar'] = $wxinfo["headimgurl"];
                        $data['user_nicename'] = $wxinfo["nickname"] ? $wxinfo["nickname"] : 'ds' . date('ymdHis', time()) . rand(1111, 9999);
                        $data['country'] = $wxinfo['country'];
                        $data['province'] = $wxinfo['province'];
                        $data['city'] = $wxinfo['city'];
                        $data['subscribe'] = $wxinfo['subscribe'];
                        $data['subscribe_time'] = $wxinfo['subscribe_time'];
                        $data['sex'] = $wxinfo['sex'];

                        $uinfo = db('Users')->where(['wx_openid'=>$openid])->find();
                        if($uinfo){
                            //更新信息
                            if($uinfo['parent_id']) unset($data['parent_id']);
                            $re = db('Users')->where(['wx_openid'=>$openid])->save($data);
                        }else{
                            //注册
                            $data['wx_openid'] = $data['user_pass'] = $openid;
                            $data['create_time'] = date('Y-m-d H:i:s');
                            $request = Request::instance();
                            $ip_address=$request->ip();
                            $data['regip'] = $ip_address;
                            $data['user_pass'] = md5($data["user_login"] . $data["user_pass"] . config('PWD_SALA'));

                            $re = db('Users')->insert($data);
                        }
                        if(config('adminopenid') && ($openid != config('adminopenid'))){
                            $message = [
                                "touser"=>config('adminopenid'),
                                "msgtype"=>"text",
                                "text"=>["content"=>"有新朋友关注：【".$wxinfo['user_nicename']."】"]
                            ];
                            $wechat->sendCustomMessage($message);
                        }
                        $message = [
                            "touser" => $openid,
                            "msgtype" => "text",
                            "text" => ["content"=>"欢迎关注蓝色方程"]
                        ];
                        $wechat->sendCustomMessage($message);

                        break;
                    case 'unsubscribe':
                        $info = db('Users')->where(['wx_openid'=>$openid])->find();
                        if($info){
                            db('Users')->where(['wx_openid'=>$openid])->save(['subscribe'=>-1]);
                            //TODO 后台统计
                        }

                        break;
                    case 'SCAN'://扫描
                        /*["ToUserName"] => string(15) "gh_d16fd4b359b1"
                        ["FromUserName"] => string(28) "oWm6q01vdkJU3TaK1uWHqeAgf0yE"
                        ["CreateTime"] => string(10) "1503557495"
                        ["MsgType"] => string(5) "event"
                        ["Event"] => string(4) "SCAN"
                        ["EventKey"] => string(1) "1"
                        ["Ticket"] => string(96) "gQF-7zwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyYnVoeGxNcUtlR2oxSERGRHhwMXgAAgRnb55ZAwSAOgkA"*/
                        break;
                }
                break;
            case 'text':
                $message = [
                    "touser" => $openid,
                    "msgtype" => "text",
                    "text" => ["content"=>"欢迎关注蓝色方程！"]
                ];
                $wechat->sendCustomMessage($message);
                break;
        }
    }

    public function log(){
        dump(S('wechat'));
        dump(S('xml'));
        dump(S('openid'));
        dump(S('wxinfo'));
    }
}