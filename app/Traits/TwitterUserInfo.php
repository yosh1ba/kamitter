<?php

  namespace App\Traits;

  trait TwitterUserInfo
  {
    // 各モデルで利用するための、twitter_user_idに関するwhere句
    public function whereTwitterUserId($query, $twitter_user_id)
    {
      return $query->where('twitter_user_id', $twitter_user_id);
    }

    // 各モデルで利用するための、screen_nameに関するwhere句
    public function whereTwitterScreenName($query, $screen_name)
    {
      return $query->where('screen_name', $screen_name);
    }
  }