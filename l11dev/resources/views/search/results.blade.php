<!-- resources/views/search/results.blade.php -->
@extends('layouts.app')

@section('content')
    <h1>検索結果</h1>

    <form action="{{ secure_url(route('search.results', [], false)) }}" method="get">
        <input type="text" name="query" value="{{ $query }}" placeholder="アーティスト名、曲名、ライブ名などで検索" required>
        <button type="submit">再検索</button>
    </form>

    <hr>

    @if($setlists->isNotEmpty())
        <h3>セットリストの検索結果:</h3>
        <ul>
            @foreach ($setlists as $setlist)
                <li>
                    <strong>{{ $setlist->live_name }}</strong> - {{ $setlist->concert_date }}<br>
                    コメント: {{ $setlist->creator_comment }}<br>
                    曲目リスト:
                    <ul>
                        @foreach ($setlist->songs as $song)
                            <li>{{ $song->song_name }} ({{ $song->artist->artist_name }})</li>
                        @endforeach
                    </ul>
                    <a href="{{ secure_url(route('setlists.show', $setlist->id, [], false)) }}">詳細</a>
                </li>
            @endforeach
        </ul>
    @else
        <p>該当するセットリストはありません。</p>
    @endif


    @if($playlists->isNotEmpty())
        <h3>プレイリストの検索結果:</h3>
        <ul>
            @foreach ($playlists as $playlist)
                <li>
                    <strong>{{ $playlist->playlist_name }}</strong><br>
                    コメント: {{ $playlist->creator_comment }}<br>
                    曲目リスト:
                    <ul>
                        @foreach ($playlist->songs as $song)
                            <li>{{ $song->song_name }} ({{ $song->artist->artist_name }})</li>
                        @endforeach
                    </ul>
                    <a href="{{ secure_url(route('playlists.show', $playlist->id, [], false)) }}">詳細</a>
                </li>
            @endforeach
        </ul>
    @else
        <p>該当するプレイリストはありません。</p>
    @endif
@endsection
