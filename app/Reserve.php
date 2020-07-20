<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reserve extends Model
{
  // 以下に指定するカラムのみデータの挿入を許す
  protected $fillable = [
    'reserved_at',
    'tweet',
    'is_posted',
    'twitter_user_id'
  ];
}