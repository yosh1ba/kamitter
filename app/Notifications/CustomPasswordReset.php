<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomPasswordReset extends Notification
{
    use Queueable;
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    /*
     * パスワードリセット用メール送信用メソッド
     * リクエスト用パラメータを引数に取り、メール送信を行います。
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('[神ったー]パスワードリセットのお知らせ') // 件名
            ->line('下のボタンをクリックしてパスワードを再設定してください。')
            ->action('リセットパスワード',
                config('app.develop_url') . config('app.reset_pass_url') .
                config('app.parameter_queryUrl') . url('api/password/reset', $this->token) . '&' . config('app.parameter_token') . $this->token
                )
            ->line('もし心当たりがない場合は、本メッセージは破棄してください。');
    }
}
