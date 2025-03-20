@extends('layouts.app')

@section('content')

    <div class="container">
        <h1>プレイリスト詳細</h1>
        <a href="{{ secure_url(route('playlists.edit', ['id' => $playlist->id], [], false)) }}" class="btn btn-primary">編集</a>

        <!-- ブックマーク -->

        <button id="bookmark-btn" data-id="{{ $playlist->id }}" data-bookmarked="{{ $playlist->bookmarks->contains('user_id', auth()->id()) ? 'true' : 'false' }}">
            <span id="bookmark-icon" class="{{ $playlist->bookmarks->contains('user_id', auth()->id()) ? 'bookmarked' : 'not-bookmarked' }}">
                ★
            </span>
        </button>

        <!-- セットリストの共通情報を表示 -->
        <div>
            <h2>{{ $playlist->playlist_name }}</h2>
            <p>コメント: {{ $playlist->creator_comment }}</p>
        </div>

        <!-- Spotify プレイリストのリンク -->
        @if($spotifyPlaylistLink)
            <p>Spotify でプレイリストを聴く: 
                <a href="{{ $spotifyPlaylistLink }}" target="_blank">こちら</a>
            </p>
        @else
            <p>Spotify プレイリストはまだ作成されていません。</p>
        @endif

        <h3>プレイリストの曲</h3>
        @if($playlist->songs->isNotEmpty())
            <ul>
                @foreach ($playlist->songs as $index => $song)
                    <li>
                        {{ $index + 1 }}. 
                        {{ $song->song_name }} by 
                        {{ $song->artist->artist_name }}  <!-- リレーションを使ってアーティスト名を表示 -->
                    </li>
                @endforeach
            </ul>
        @else
            <p>このプレイリストには曲が追加されていません。</p>
        @endif
    </div>


    <!-- コメント投稿フォーム -->
    @auth
        <form action="{{ secure_url(route('playlists.comments.store', $playlist->id, [], false)) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="content">コメントを投稿:</label>
                <textarea name="content" id="content" rows="3" class="form-control" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">送信</button>
        </form>
    @else
        <p><a href="{{ secure_url('login') }}">ログイン</a>してコメントを投稿してください。</p>
    @endauth


    <!-- コメント一覧 -->
    <h2>コメント</h2>
    @if($comments->isNotEmpty())
        <ul>
            @foreach($comments as $comment)
                <li>
                    <!-- プロフィール画像 -->
                    <img src="{{ asset($comment->user->profile_image) }}" alt="{{ $comment->user->account_name }}" style="width: 50px; height: 50px; border-radius: 50%;">

                    <!-- ユーザー名とコメント内容 -->
                    <strong>{{ $comment->user->account_name }}</strong>:
                    <p>{{ $comment->content }}</p>
                    <!-- 投稿日時 -->
                    <small>{{ $comment->created_at->format('Y年m月d日 H:i') }}</small>
                </li>
            @endforeach
        </ul>

        <!-- ページネーション  -->
        <div class="pagination">
            {{ $comments->links() }} 
        </div>

    @else
        <p>まだコメントがありません。</p>
    @endif


    <!-- JavaScript for Bookmark -->
    <script>

        document.addEventListener('DOMContentLoaded', function () {
            const bookmarkBtn = document.getElementById('bookmark-btn');
            if (!bookmarkBtn) return;

            bookmarkBtn.addEventListener('click', function () {
                const playlistId = this.dataset.id;
                const bookmarked = this.dataset.bookmarked === 'true';

                fetch(`/playlists/${playlistId}/bookmark`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ bookmarked: !bookmarked })
                })
                .then(response => response.json())
                .then(data => {
                    const icon = document.getElementById('bookmark-icon');
                    if (data.bookmarked) {
                        icon.classList.remove('not-bookmarked');
                        icon.classList.add('bookmarked');
                    } else {
                        icon.classList.remove('bookmarked');
                        icon.classList.add('not-bookmarked');
                    }
                    bookmarkBtn.dataset.bookmarked = data.bookmarked;
                });
            });
        });

    </script>

@endsection
