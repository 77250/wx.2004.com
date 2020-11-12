<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WxModel extends Model
{
    //
    protected $table = "media";
    protected $primarykey = "id";
    public $timestamps = false;
    protected $guarded = [];
}
