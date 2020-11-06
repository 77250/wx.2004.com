<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
class WxController extends Controller
{
    //
    public function wx(Request $request){

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
            echo $echostr;
        }else{
            return false;
        }
    }
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
            //接收数据
            $xml_data=file_get_contents('php://input');
            //记录日志
            file_put_contents('wx_event.log',$xml_data);
            //把xml文本转化为数组对象
            $data=simplexml_load_string($xml_data,'SimpleXMLElement',LIBXML_NOCDATA);
            $xml="<xml>
                <ToUserName><![CDATA[toUser]]></ToUserName>
                <FromUserName><![CDATA[fromUser]]></FromUserName>
                <CreateTime>1348831860</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[this is a test]]></Content>
                <MsgId>1234567890123456</MsgId>
            </xml>";
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
}
