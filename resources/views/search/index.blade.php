<!-- search/index.blade.php -->
@extends('layouts.app')

@section('content')

    <h1>検索ページ</h1>
    <p>アーティスト名、曲名、ライブ名などでセットリストやプレイリストを検索できます。</p>

    <form action="{{ secure_url(route('search.results', [], false)) }}" method="get">
        <input type="text" name="query" placeholder="キーワードを入力してください" required>
        <button type="submit">検索</button>
    </form>


@endsection
