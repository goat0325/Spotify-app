<!-- resources/views/home.blade.php -->
@extends('layouts.app')

<h2>ホーム画面</h2>
@section('content')
    
    
    <!-- ユーザー情報表示 -->
    <div class="container">

    <!-- 流れの確認のため、「ゲスト」でホーム画面に入れる設定。認証されていないユーザーでもホーム画面にアクセスできるように -->
        @if (auth()->check())

	    @php
                $user = auth()->user(); // 認証されたユーザー情報を取得
            @endphp

            <!-- 認証ユーザー専用のボタンや機能 -->
           <!-- 元々のやつ <img src="{{ $user->profile_image ?? asset('images/default.png')  }}" alt="{{ $user->Spotify_user_id }}" width="50" height="50"> -->
            

<!-- プロフィール画像を表示しない -->
	    <div class="user-info">
		@if ($user->profile_image)
                    <img src="{{ $user->profile_image }}" alt="{{ $user->Spotify_user_id }}" width="50" height="50">
		@endif
	 
	        <div>
		    <h1>ようこそ、{{ auth()->user()->account_name }}さん！</h1>
		    <p>Welcome, {{ $user->Spotify_user_id }}!</p>
	    	</div> 
	    </div>   


            <!-- ログアウトボタン -->
            <form action="{{ secure_url('logout') }}" method="POST"">
                @csrf
                <button type="submit" class="btn">ログアウト</button>
            </form>
            
        @else
            <!-- ゲスト用のコンテンツ -->
            <h1>ゲストとしてのホーム画面</h1>
            <p>ログインすると、すべての機能を利用できます</p>
            <img src="{{ asset('images/default.png') }}" alt="Guest" width="50" height="50">
            <span>Guest</span>
            <p>Welcome, Guest!</p>

            <!-- 機能制限メッセージ -->
            <p>このアプリの一部機能（セットリストの作成やプレイリストの作成、ブックマークなど）は、ログイン後にご利用いただけます。</p>
        @endif
    </div>

    <h1>セットリストをプレイリストへ</h1>
    
    <!-- ボタン -->
    <div>
        <a href="{{ route('spotify.auth') }}" class="btn">Spotifyアカウントを使ってログイン</a>
        <a href="https://www.spotify.com/jp/signup" class="btn">Spotifyアカウントを持っていない場合はこちら</a>
        <a href="https://www.spotify.com/jp/password-reset" class="btn">パスワードを忘れた方はこちら</a>
    </div>
@endsection
