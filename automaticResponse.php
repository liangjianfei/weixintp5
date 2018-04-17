<?php
header('content-type:text/html;charset=utf-8');

define("TOKEN", "cygxzb"); //define your token
$wx = new wechatCallbackapiTest();

if ($_GET['echostr']) {
    $wx->valid(); //如果发来了echostr则进行验证
} else {
    $wx->responseMsg(); //如果没有echostr，则返回消息
}


class wechatCallbackapiTest
{

    public function valid()
    { //valid signature , option
        $echoStr = $_GET["echostr"];
        if ($this->checkSignature()) { //调用验证字段
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {

        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; //接收微信发来的XML数据

        //extract post data
        if (!empty($postStr)) {

            //解析post来的XML为一个对象$postObj
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

            $fromUsername = $postObj->FromUserName; //请求消息的用户
            $toUsername = $postObj->ToUserName; //"我"的公众号id
            $keyword = trim($postObj->Content); //消息内容
            $time = time(); //时间戳
            $msgtype = 'text'; //消息类型：文本
            $textTpl = "<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[%s]]></MsgType>
  <Content><![CDATA[%s]]></Content>
  </xml>";
            if($postObj->MsgType == 'event'){ //如果XML信息里消息类型为event
                if($postObj->Event == 'subscribe'){ //如果是订阅事件
                    $contentStr = "欢迎订阅中天铭汇！\n更多精彩内容：http://zhongtian.meilebi.com/Home";
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgtype, $contentStr);
                    echo $resultStr;
                    exit();
                }
            }
        } else {
            echo "";
            exit;
        }
    }

    //验证字段
    private function checkSignature()
    {

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
}