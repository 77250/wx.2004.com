<?php

namespace App\Http\Controllers;

use App\Openname;
use App\Record;
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
    private function checkSignature()  {
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
    //菜单栏
    public function menu(){
        $menu='{
            "button":[
            {
                    "type":"view",
                    "name":"百度翻译",
                    "url":"https://www.baidu.com"
                },
                {
                    "name":"多媒体",
                    "sub_button":[
                    {
                        "type":"view",
                        "name":"商城",
                        "url":"http://www.zikh.我爱你"
                    },
                    {
                        "type":"view",
                        "name":"滴滴",
                        "url":"http://www.soso.com/"
                    }]
                }]
        }';
        $token=$this->token();
        $uri="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$token;
        $res=$this->curl($uri,'',$menu);
    }
    function wxEvent(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = "aaa";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        //访问日志
        //tail -f /data/wwwlogs/access_nginx.log
        //关注成功回复
        if( $tmpStr == $signature ){
            //接受数据
            $xml_data=file_get_contents('php://input');
            $obj=simplexml_load_string($xml_data);
            //$this->chat($obj);
            if ($obj->MsgType=='event'){
                if ($obj->Event=='subscribe'){
                    //openid写入库里
                    $ToUserName=$obj->FromUserName;//接收对方账号
                    //if判断
                    $u=Openname::where('openid',$ToUserName)->first();
                    if($u){
                        $Content ="欢迎再次关注";
                        $result = $this->infocodl($obj,$Content);
                        return $result;
                    }else{
                        $token=$this->token();
                        $uri='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.$ToUserName.'&lang=zh_CN';
                        //dd($uri);
                        $user_file=file_get_contents($uri);
                        $user_code=json_decode($user_file,true);
                        // dd($user_code);
                        $data=[
                            'nickname'=>$user_code['nickname'],
                            'sex'=>$user_code['sex'],
                            'country'=>$user_code['country'],
                            'headimgurl'=>$user_code['headimgurl'],
                            'add_time'=>$user_code['subscribe_time'],
                            'openid'=>$user_code['openid']
                        ];
                        //dd($data);
                        $openid=new Openname();
                        //dd($openid);
                        $user_insert=$openid->insertGetId($data);
                        $Content ="关注成功";
                        $result = $this->infocodl($obj,$Content);
                        return $result;
                    }

                }
            }
            //回复天气
            // if($obj->Content="天气"){
            //     $Content = $this->getNew();
            //     $result = $this->infocodl($obj,$Content);
            //     return $result;
            // }
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
                    $array=['你好呀','祝你今天运气爆棚','斯特姆光线','祝你早日找到你的另一半','嘿嘿嘿','泰罗'];
                    $Content =$array[array_rand($array)];
                    $result = $this->infocodl($data,$Content);
                    return $result;
            }
        }
        if($tmpStr == $signature){
            $xml_str=file_get_contents("php://input");
            $data = simplexml_load_string($xml_str,"SimpleXMLElement",LIBXML_NOCDATA);
            //用户扫码的openid
            $openid = $data->FromUserName;
            file_put_contents('wx_event1.log ',$openid);
        }

    }
    //接收toent
    public function token(){
        //dd($token);
        $key="access_token";
        function get_token($key){
            $id="wxeb871333bd5f058a";
            $appsecret="b545aea26c4d502420cbb4918cb889af";
            $token="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$id&secret=$appsecret";
            $api = file_get_contents($token);
            $api = json_decode($api);
            // dd($api);die;
            $token = $api->access_token;
            Redis::setex($key,7200,$token);
        }
        if(empty(Redis::get($key))){
            get_token($key);
        }else{
            // 如果 token 存在 则验证 token 是否有效
            $toekn = Redis::get($key);
            $uri = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$toekn."&media_id=1";
            $_true = file_get_contents($uri);
            $_true = json_decode($_true,true);
            if($_true['errcode'] == 40001){
                get_token($key);
            }
        }
        $dd=Redis::get($key);
        // dd($dd);
        return $dd;
    }
    //封装回复方法
    public function infocodl($postarray,$Content){
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
        $rea = $red['now'];
        $rea=implode(',',$rea);
        return $rea;
        //echo $red;
    }
    //调用接口方法
    public function curl($url,$header="",$content=[]){
        $ch = curl_init(); //初始化CURL句柄
        if(substr($url,0,5)=="https"){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true); //字符串类型打印
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        if(!empty($header)){
            curl_setopt ($ch, CURLOPT_HTTPHEADER,$header);
        }
        if($content){
            curl_setopt ($ch, CURLOPT_POST,true);
            curl_setopt ($ch, CURLOPT_POSTFIELDS,$content);
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
    //封装添加聊天记录
    public function chat(){
        $xml_data=file_get_contents('php://input');
        $obj=simplexml_load_string($xml_data);
        dd($obj);
        // if(empty($res)){
        //     $data=[
        //         'time'=>time(),
        //         'msg_type'=>$obj->MsgType,
        //         'open_id'=>$obj->FromUserName,
        //         'msg_id'=>$obj->MsgId
        //     ];
        //     //图片
        //     if($obj->MsgType=='image'){
        //         $data['url']=$obj->PicUrl;
        //         $data['media_id']=$obj->MediaId;
        //     }
        //     //视频
        //     if($obj->MsgType=='video'){
        //         $data['media_id']=$obj->MediaId;
        //     }
        //     //文本
        //     if($obj->MsgType=='text'){
        //         $data['media_id']=$obj->MediaId;
        //     }
        //     //语音
        //     if($obj->MsgType=='voice'){
        //         $data['media_id']=$obj->MediaId;
        //     }
        //     $res=Record::insert($data);
        // }
    }
}
