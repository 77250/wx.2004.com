<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GoodsModel extends Model
{
    //
    protected $table = "p_goods";
    protected $primarykey = "goods_id";
    public $timestamps = false;
    protected $guarded = [];
}
