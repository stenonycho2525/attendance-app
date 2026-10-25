@extends('layouts.app')

@section('css')
@vite('resources/css/auth/verify-email.css')
@endsection

@section('content')
<div class="verify__content">
    @if (session('message'))
        <div class="verify__flash">{{ session('message') }}</div>
    @endif

    <p class="verify__text">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    <div class="verify__button">
        <a class="verify__link" href="http://localhost:8025" target="_blank" rel="noopener noreferrer">認証はこちらから</a>
    </div>

    <form class="verify__resend" action="{{ route('verification.send') }}" method="post">
        @csrf
        <button class="verify__resend-button" type="submit">認証メールを再送する</button>
    </form>
</div>
@endsection
