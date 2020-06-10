<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends VerifyEmailBase
{

  public function toMail($notifiable)
  {

    $verificationUrl = $this->verificationUrl($notifiable);

    return (new MailMessage)
      ->subject('[神ったー]メールアドレス認証のお知らせ') // 件名
      ->line('下のボタンをクリックしてメールアドレスの認証を行って下さい。')
      ->action('メールアドレス認証',$verificationUrl)
      ->line('もし心当たりがない場合は、本メッセージは破棄してください。');
  }

  protected function verificationUrl($user)
  {
      // TODO 本番環境ではURLの変更必要
      $prefix = config('app.develop_url') .config('app.email_verify_url') .config('app.parameter_queryUrl');
      $routeName = 'verification.verify';
      $temporarySignedURL = URL::temporarySignedRoute(
          $routeName, Carbon::now()->addMinutes(60),
          [
              'id' => $user->getKey()
          ]
      );

      return $prefix . urlencode($temporarySignedURL);
  }
}
