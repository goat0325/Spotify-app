@extends('layouts.app')

@section('content')
<div class="container">
    <h1>ブックマーク一覧</h1>

    <h2>セットリスト</h2>

    @if($bookmarkedSetlists->isEmpty())
        <p>まだセットリストをブックマークしていません。</p>
    @else
        <ul>
            @foreach($bookmarkedSetlists as $bookmark)
                @if ($bookmark->bookmarkable)  <!-- bookmarkableが存在する場合のみ表示 -->
		    <li>
                        <a href="{{ route('setlists.show', $bookmark->bookmarkable->id) }}">
                            {{ $bookmark->bookmarkable->live_name }}
                        </a>
                        （{{ $bookmark->bookmarkable->concert_date }}）
                        <p><strong>コメント:</strong> {{ $bookmark->bookmarkable->creator_comment }}</p>
                    </li>
                @endif
            @endforeach
        </ul>
    @endif

    
    <h2>プレイリスト</h2>
    @if($bookmarkedPlaylists->isEmpty())
	<p>まだプレイリストをブックマークしていません。</p>
    @else    
	<ul>
            @foreach ($bookmarkedPlaylists as $bookmark)
                @php $playlist = $bookmark->bookmarkable; @endphp
                @if ($playlist)  <!-- bookmarkableが存在する場合のみ表示 -->
		    <li>
                        <a href="{{ route('playlists.show', $playlist->id) }}">
                            {{ $playlist->playlist_name }}
                        </a>
                        <p><strong>コメント:</strong> {{ $playlist->creator_comment }}</p>   
                    </li>
                @endif
            @endforeach
        </ul>
    @endif
</div>
@endsection

