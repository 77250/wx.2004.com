<?php

namespace App\Http\Controllers\xcx;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\LoginModel;
use App\Model\GoodsModel;
use Illuminate\Support\Facades\Redis;

class ApiController extends Controller
{
    //
    public function goods(){

        // dump($_GET);
   
        $data=[
            "name"=>"水电费",
            "age"=>123
        ];
        echo json_encode($data);

    }
    public function wxlogin(){
        // echo "123";
        $code=request()->get("code");
        // return $code;
        // dd($code);
        $appid = "wxa5b35780c36238a1";
        $appSecret = "db4bbc47081ccf49cb75b4c212d01178";
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$appSecret."&js_code=".$code."&grant_type=authorization_code";
        $res = json_decode(file_get_contents($url),true);
        if(isset($res['errorde'])){
            $data = [
                'error'=>'50001',
                'msg'=>'登录失败'
            ];
            return $data;
        }else{
            $openid = $res['openid'];
            LoginModel::insert(['openid'=>$openid]);
            $token = sha1($res['openid'].$res['session_key'].mt_rand(0,9999));   
            $redis_key = "wxkey:".$token;
                Redis::set($redis_key,time());
                Redis::expire($redis_key,7200);
            $data = [
                'error'=>'0',
                'msg'=>'登录成功',
                'data'=>[
                    'token'=>$token
                ]
            ];
            return $data;
        }
    }
    public function wxgoods(){
        $goods = GoodsModel::inRandomOrder()->take('5')->get()->toArray();
        // dd($goods);
        return json_encode($goods,256);
    }
    public function datails(){
        $goods_id = request()->get('goods_id');
        // dd($goods_id);
        $detail = GoodsModel::where('goods_id',$goods_id)->first()->toArray();
        // dd($datail);
        return $detail;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    
    }
}
