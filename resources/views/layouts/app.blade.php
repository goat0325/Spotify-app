<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>セットリストからプレイリストへ  - @yield('title')</title>

    <!-- Spotify APIを組み込み -->
    @if(session('spotify_access_token'))
        <script>
            var spotifyAccessToken = "{{ session('spotify_access_token') }}";
        </script>
    @endif

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Viteでのアセット読み込み -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body>
    <!-- 共通ヘッダー -->
    <header>
        <nav aria-label="メインナビゲーション">
            <ul>
                <li><a href="{{ route('home') }}">ホーム</a></li>
                <li><a href="{{ route('search.index') }}">検索</a></li>
                <li><a href="{{ route('setlists.create') }}">セットリスト作成</a></li>
                <li><a href="{{ route('playlist.create') }}">プレイリスト作成</a></li>
                <li><a href="{{ route('bookmarks.show') }}">ブックマーク</a></li>
                <li><a href="{{ route('my.lists') }}">Myセットリスト/プレイリスト</a></li>
            </ul>
        </nav>
    </header>

    <main>
        @yield('content') <!-- 各ページの内容がここに埋め込まれる -->
    </main>

    <!-- 共通フッター -->
    <footer>
        <p>&copy; 2024 セットリストからプレイリストへ </p>
    </footer>

</body>

</html>