<!-- resources/views/auth/login.blade.php -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <!-- Vite アセットを読み込む -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- <link rel="stylesheet" href="{{ asset('css/app.css') }}"> -->


    <style> 
	/* Spotify風のスタイル */ 
	body { 
		background-color: #121212; /* Spotifyのダーク背景 */ 
		color: white; 
		font-family: 'Arial', sans-serif; 
		display: flex; 
		justify-content: center; 
		align-items: center; 
		height: 100vh; 
		margin: 0; 
	} 

	.login-container { 
		background: #181818; 
		padding: 40px; 
		border-radius: 10px; 
		box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2); 
		text-align: center; 
		width: 350px; 
	} 

	h1 { 
		font-size: 24px; 
		margin-bottom: 20px; 
	} 

	.btn { 
		display: block; 
		background-color: #1DB954; /* Spotifyの緑 */ 
		color: white; padding: 12px 20px; 
		border-radius: 50px; 
		text-decoration: none; 
		font-weight: bold; 
		transition: background 0.3s; 
		margin-top: 10px; 
	} 

	.btn:hover { 
		background-color: #1ed760; 
	} 

	.guest-link { 
		color: #b3b3b3; 
		font-size: 14px; 
		margin-top: 15px; 
	} 

	.guest-link a { 
		color: #1DB954; 
		text-decoration: none; 
		font-weight: bold; 
	} 

	.guest-link a:hover { 
		text-decoration: underline; 
	} 
	
    </style>


</head>
<body>
    <div class="login-container">
	<h1>ログイン</h1>
    
	<!-- Spotifyアカウントでログイン -->
	<a href="{{ route('spotify.redirect') }}" class="btn">Spotifyアカウントを使ってログイン</a>
    
	<!-- ゲスト利用のリンク -->
	<p class="guest-link">ゲストでアプリを利用の方はこちら（一部機能の制限あり）:</p>
	<a href="{{ route('home') }}" class="btn">ゲスト利用</a>

	<p class="guest-link">Spotifyアカウントを持っていない場合は、<a href="https://www.spotify.com/jp/signup">こちら</a>から作成できます。</p>
	<p class="guest-link">パスワードを忘れた場合は、<a href="https://www.spotify.com/jp/password-reset">こちら</a>からリセットできます。</p>
    
    </div>

    <!-- <script src="{{ asset('js/app.js') }}"></script> -->
</body>
</html>


    
