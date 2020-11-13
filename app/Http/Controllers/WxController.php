<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Model\WxModel;
class WxController extends Controller
{
    function wxEvent(Request $request){

        $signature = request()->get("signature");
        $timestamp = request()->get("timestamp");
        $nonce = request()->get("nonce");
        
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

             //把xml文本转化为数组对象
            $data = simplexml_load_string($xml_data);

            //记录日志
            file_put_contents('wx_event.log',$xml_data,FILE_APPEND);
           
            
            if($data->MsgType=='event'){
                if($data->Event=='subscribe'){
                    //openid写入库里
                    $ToUserName=$data->FromUserName;//接收对方账号
                    //if判断
                    $u=WxModel::where(['openid'=>$ToUserName])->first();
                    if($u){
                        $Content = "欢迎回来";
                        $result = $this->infocodl($data,$Content);
                        echo $result;
                    }
                    $token=$this->getAccessToken();
                        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid=ovgu16IbL9fRTw8QCbRQBClwQK3o&lang=zh_CN';
                        //   dd($url);
                        $file=file_get_contents($url);
                        $decode=json_decode($file,true);
                        //   dd($decode);
                        $datas = [
                            'nickname'=>$decode['nickname'],
                            'sex'=>$decode['sex'],
                            'country'=>$decode['country'],
                            'headimgurl'=>$decode['headimgurl'],
                            'add_time'=>$decode['subscribe_time'],
                            'openid'=>$decode['openid']
                        ];
                        // dd($data);
                        $openid = new WxModel();
                        // dd($openid);
                        $Content = "欢迎关注xx";
        
                        $result = $this->infocodl($data,$Content);
                        echo $result;
                    }
                }
            
            //回复天气
            $arr = ['天气','天气。','天气,'];
            if($data->Content==$arr[array_rand($arr)]){
                $Content = $this->getweather();
                $result = $this->infocodl($data,$Content);
                return $result;
            }
            echo "";
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
        // echo $response;
        $data = json_decode($response,true);
        $token = $data['access_token'];
        
        //保存到Redis中 时间为3600
        
        Redis::set($key,$token);
        Redis::expire($key,3600);
        
        return $token;
    }
   //封装回复方法
   public function infocodl($data,$Content){
    //    dd($data);
    $ToUserName=$data['FromUserName'];//接收对方帐号
    
    $FromUserName=$data['ToUserName'];//接收开发者微信
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
    echo sprintf($ret,$ToUserName,$FromUserName,$time,$text,$Content);
   }
   //封装天气方法
   public function getweather(){
       $url = 'http://api.k780.com/?app=weather.realtime&weaid=1&ag=today,futureDay,lifeIndex,futureHour&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4&format=json';
       $weathle = file_get_contents($url);
       $weathle = json_decode($weathle,true);
       if($weathle['success']){
           $content = '';
           $v = $weathle['result']['realTime'];
                $content .="日期:".$v['week']."当日温度:".$v['wtTemp']."天气:".$v['wtNm']."风向:".$v['wtWindNm'];
       }
       return $content;
   }
   public function curl($url,$menu){
    //1.初始化kil

        $ch = curl_init();
        //2.设置
        curl_setopt($ch,CURLOPT_URL,$url);//设置提交地址
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);//设置返回值返回字符串
        curl_setopt($ch,CURLOPT_POST,1);//post提交方式
        curl_setopt($ch,CURLOPT_POSTFIELDS,$menu);//上传的文件
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);//过滤https协议
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);//过滤https协议
        //3.执行
        $output = curl_exec($ch);
        //关闭
        curl_close($ch);
        return $output;
   }
   //自定义菜单
  public function createMenu(){
      $menu= ' {
        "button":[
        {	
             "type":"click",
             "name":"商城",
             "url":"http://www.soso.com/"
         },
         {
              "name":"菜单",
              "sub_button":[
              {	
                  "type":"view",
                  "name":"搜索",
                  "url":"http://www.soso.com/"
               },
               {
                  "type":"click",
                  "name":"赞一下我们",
                  "key":"V1001_GOOD"
               }]
          }]
    }';
    $access_token = $this->getAccessToken();
    $url ="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
    $res = $this->curl($url,$menu);
    dd($res);
  }
  //下载媒体素材
  public function diMedia(){                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
    $media_id = 'U3YUYonuHyx4Uv2mNCB_EZpySyYd12aLpeFAF2djAQGzWcKgRF_yUXTbznSKguyh';
    $url = '';
  }
}
