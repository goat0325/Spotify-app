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
</head>
<body>
    <h1>ログイン</h1>
    
    <!-- Spotifyアカウントでログイン -->
    <a href="{{ route('spotify.redirect') }}" class="btn">Spotifyアカウントを使ってログイン</a>
    
    <!-- ゲスト利用のリンク -->
    <p>ゲストでアプリを利用の方はこちら（一部機能の制限あり）:</p>
    <a href="{{ route('home') }}" class="btn">ゲスト利用</a>

    <p>Spotifyアカウントを持っていない場合は、<a href="https://www.spotify.com/jp/signup">こちら</a>から作成できます。</p>
    <p>パスワードを忘れた場合は、<a href="https://www.spotify.com/jp/password-reset">こちら</a>からリセットできます。</p>
    
    <!-- <script src="{{ asset('js/app.js') }}"></script> -->
</body>
</html>


    
