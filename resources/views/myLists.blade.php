<!-- mySetlists.blade.php -->
@extends('layouts.app')

@section('content')

    <div class="container">
        <h1>セットリスト＆プレイリスト一覧ページ</h1>

        <h2>セットリスト</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ライブ名</th>
                    <th>開催日</th>
                    <th>コメント</th>
                </tr>
            </thead>
            <tbody>
                @foreach($setlists as $setlist)
                    <tr>
                        <td>{{ $setlist->live_name }}</td>
                        <td>{{ $setlist->concert_date }}</td>
                        <td>{{ $setlist->creator_comment }}</td>
                        <td>
                            <!-- ボタンをラップするdivを追加 -->
                            <div class="setlist-actions">

                            <!-- 詳細ボタン　setlists/showページへ遷移 -->
                            <a href="{{ secure_url(route('setlists.show', $setlist->id, [], false)) }}" class="btn btn-primary">詳細</a>

                            <!-- 削除ボタン -->
                            <form action="{{ secure_url(route('setlists.destroy', $setlist->id, [], false)) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">削除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>


        <h2>プレイリスト</h2>
        
        <table class="table">
            <thead>
                <tr>
                    <th>プレイリスト名</th>
                    <th>コメント</th>
                </tr>
            </thead>
            <tbody>
                @foreach($playlists as $playlist)
                    <tr>
                        <td>{{ $playlist->playlist_name }}</td>
                        <td>{{ $playlist->creator_comment }}</td>
                        <td>
                            <!-- ボタンをラップするdivを追加 -->
                            <div class="setlist-actions">

                            <!-- 詳細ボタン　playlists/showページへ遷移 -->
                            <a href="{{ secure_url(route('playlists.show', $playlist->id, [], false)) }}" class="btn btn-primary">詳細</a>
                                
                            <!-- 削除ボタン -->
                            <form action="{{ secure_url(route('playlists.destroy', $playlist->id, [], false)) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">削除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
@endsection



