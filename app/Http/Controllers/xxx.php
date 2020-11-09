<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
class TextController extends Controller
{
    //连接
    public function aaa(){
        $aaa=request()->get('echostr','');
        if($this->checkSignature() && !empty($aaa)){
            echo $aaa;
        }
    }
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = "aaa";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    function wxEvent()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = "aaa";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        //关注成功回复
        if( $tmpStr == $signature ){
            //接受数据
            $xml_data=file_get_contents('php://input');
            file_put_contents('wx_event.log ',$xml_data);
            $data=simplexml_load_string($xml_data);
            if ($data->MsgType=='event'){
                if ($data->Event=='subscribe'){
                    $Content ="欢迎关注";
                    $result = $this->infocodl($data,$Content);
                    return $result;
                }
            }
            //回复天气
            $arr=['天气','天气。','天气,'];
            if($data->Content==$arr[array_rand($arr)]){
                $Content = $this->getNew();
                $result = $this->infocodl($data,$Content);
                return $result;
            }

            echo "";
        }else{
            echo "";
        }
        //被动回复消息
        if($tmpStr == $signature){
            $xml_data=file_get_contents('php://input');
            file_put_contents('wx_event.log ',$xml_data);
            $data=simplexml_load_string($xml_data);
            if($data->MsgType=='text'){
                    +$array=['你好呀','祝你今天运气爆棚','斯特姆光线','祝你早日找到你的另一半','嘿嘿嘿','泰罗'];
                    $Content =$array[array_rand($array)];
                    $result = $this->infocodl($data,$Content);
                    return $result;
            }
        }
    }
    //接收toent
    public function token()
    {
        //dd($token);
        $key="access_token";
        if(empty(Redis::get($key))){
            $id="wxeb871333bd5f058a";
            $appsecret="b545aea26c4d502420cbb4918cb889af";
            $token="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$id&secret=$appsecret";
            $api = file_get_contents($token);
            $api = json_decode($api);
            // dd($api);die;
            $token = $api->access_token;
            Redis::setex($key,7200,$token);
        }
        $dd=Redis::get($key);
        dd($dd);
    }
    //封装回复方法
    public function infocodl($postarray,$Content)
    {
        $ToUserName=$postarray->FromUserName;//接收对方账号
        $FromUserName=$postarray->ToUserName;//接收开发者微信
        file_put_contents('log.logs',$ToUserName);

        $time=time();//接受时间
        $text='text';//数据类型
        $ret="<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[%s]]></MsgType>
                <Content><![CDATA[%s]]></Content>
            </xml>";
        echo sprintf($ret,$ToUserName,$FromUserName,$time,$text,$Content);
    }
    //调用天气
    public function getNew(){
        $key="51fa5a15d0084cefafd4591f57a29298";
        $url = "https://devapi.qweather.com/v7/weather/now?location=101010100&key=$key&gzip=n";
        $red = $this->curl($url);
        $red= json_decode($red,true);
        //dd($red);
        $rea = $red['now'];
        $rea=implode(',',$rea);
        //dd($rea);
        return $rea;
        //echo $red;
    }
    //调用接口方法
    public function curl($url,$header="",$content=[]){
        $ch = curl_init(); //初始化CURL句柄
        if(substr($url,0,5)=="https"){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,true);
        }
          curl_setopt($ch, CURLOPT_RETURNTRANSFER,true); //字符串类型打印
         curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        if($header){
            curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
        }
        if($content){
            curl_setopt ($ch, CURLOPT_POST,true);
            curl_setopt ($ch, CURLOPT_POSTFIELDS,http_build_query($content));
        }
        //执行
        $output = curl_exec($ch);
        if($error=curl_error($ch)){
            die($error);
        }
        //关闭
        curl_close($ch);
        return $output;
    }
}
