<h3>
    <a href="{{ config('app.url') }}">{{ config('app.name') }}</a>
</h3>
<p>
    {{ __('リンクをクリックしてパスワードをリセットしてください。') }}<br>
    {{ __('このメールに覚えのない場合には、お手数ですがメールを破棄してくださいますようお願いいたします。') }}
</p>
<p>
    {{ $actionText }}: <a href="{{ $actionUrl }}">{{ $actionUrl }}</a>
</p>