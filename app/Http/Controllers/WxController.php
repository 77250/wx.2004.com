<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
class WxController extends Controller
{
    function wxEvent(Request $request){
        $echostr=$request->get('echostr');

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            if(!empty($echostr)){
                echo $echostr;
            }
            //接收数据
            $xml_data=file_get_contents('php://input');
            //记录日志
            file_put_contents('wx_event.log',$xml_data);
            //把xml文本转化为数组对象
            $data = simplexml_load_string($xml_data);
            if($data->MsgType=='event'){
                if($data->Event=='subscribe'){
                    $Content = "欢迎关注";
                    file_put_contents('wx_event.log',$Content);
                    $result = $this->infocodl($data,$Content);
                    return $result;
                }
            }
       
        }else{
           echo "";
        }
    }
    //获取access_token
    public function getAccessToken(){ 
        $key = 'wx:access_token';
        //检查是否有 token
        $token = Redis::get($key);
        if($token){
            echo "有缓存";echo '</br>';
        }else{
            echo "无缓存";
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPEC')."";
        // echo $url;die;
        $response = file_get_contents($url);
        echo $response;
        $data = json_decode($response,true);
        $token = $data['access_token'];
        
        //保存到Redis中 时间为3600
        
        Redis::set($key,$token);
        Redis::expire($key,3600);
        
        echo "access_token: ".$token;
    }
   //封装回复方法
   public function infocodl($postarray,$Content){
    $ToUserName=$postarray->FromUserName;//接收对方帐号
    $FromUserName=$postarray->ToUserName;//接收开发者微信
    file_put_contents('log.lpgs',$ToUserName);

    $time=time();//接收时间
    $text='text';//数据类型
    $ret="<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[%s]]></MsgType>
        <Content><![CDATA[%s]]></Content>
    </xml>";
    echo spintf($ret,$ToUserName,$FromUserName,$time,$text,$Content);
   }
}
