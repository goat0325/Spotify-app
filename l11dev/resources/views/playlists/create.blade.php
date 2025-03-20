<!-- playlists/create.blade.php -->
@extends('layouts.app')

@section('content')

    <!-- <h1>プレイリスト作成</h1> -->
    <h1>{{ isset($playlist) && isset($playlist->id) ? 'プレイリスト編集' : 'プレイリスト作成' }}</h1>


<div class="playlist-container">
    <!-- 左側の検索エリア -->
    <div class="left-panel">

    <form id="playlistForm" 
        action="{{ isset($playlist) && isset($playlist->id) ? secure_url('playlists.update', $playlist->id) : secure_url('playlists.store') }}"
        method="POST">
        @csrf
        @if(isset($playlist))
            @method('PUT') <!-- 編集の場合は PUT メソッドを使用 -->
        @endif

        <!-- ライブ名の入力フィールド -->
        <div>
            <label for="playlist_name">プレイリスト名:</label>
            <input type="text" id="playlist_name" name="playlist_name" value="{{ old('playlist_name', $playlist->playlist_name ?? '') }}" required>
            @error('playlist_name')
                <div>{{ $message }}</div>
            @enderror
        </div>

        <!-- コメントの入力フィールド -->
        <div>
            <label for="creator_comment">作成者コメント:</label>
            <textarea id="creator_comment" name="creator_comment">{{ old('creator_comment', $playlist->creator_comment ?? '') }}</textarea>
        </div>

        <!-- 曲検索フォーム -->
        <div>
            <h1>曲を検索</h1>
            <input type="text" id="searchInput" placeholder="曲名、アーティスト名で検索">
            <button id="searchBtn" type="button">検索</button>
        </div>

        <!-- 検索結果の表示エリア -->
        <div id="dbResults"></div>
        
	</form>
        </div>



<!-- 右側のセットリストエリア -->
    <div class="right-panel">
        <!-- セットリストに追加された曲を表示 -->
        <div id="playlist">
            <h2>セットリスト</h2>
            <ul id="playlistSongs">
                <!-- 追加された曲がここに表示される -->
            </ul>
        </div>

        <!-- <input type="hidden" id="playlistData" name="playlistData" value=""> -->
        <div id="playlistCreateBtn"></div>
        <!-- <button type="submit" class="playlistData" data-track='${JSON.stringify(track)}'>プレイリストを作成</button> -->
    

	</div>
    </div>


    <style>
    .playlist-container {
        display: flex;
        gap: 20px;
	overflow-y: auto; 
	max-height: 80vh; /* 画面いっぱいにスクロールさせる */
    }

    .left-panel {
        width: 50%;
	overflow-y: auto; 
	max-height: 80vh; /* 画面いっぱいにスクロールさせる */
    }

    .right-panel {
        width: 50%;
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        min-height: 300px;
	position: sticky; 
	top: 0; /* 常に上に固定する */
    }

    #playlistSongs {
        list-style: none;
        padding: 0;
    }

    #playlistSongs li {
        background: white;
        padding: 8px;
        margin-bottom: 5px;
        cursor: grab;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dragging {
        opacity: 0.5;
    }
</style>


    <!-- JavaScript for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>

    // CSRFトークンの設定
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {
            let addedSongs = []; // 曲を追加するための配列

            // フォーム送信を停止する
            $('#playlistForm').submit(function(event) {
                event.preventDefault();
            });
            
            // 曲検索ボタンが押されたときの処理
            $('#searchBtn').click(function(event) {
                event.preventDefault(); // ボタンのデフォルトの動作を防止
                const query = $('#searchInput').val();

                $.ajax({
                    url: '{{ secure_url(route('songs.search', [], false)) }}', // ルーティング名を指定
                    method: 'GET',
                    data: {
                        query: query,
                        type: 'track',  // ここで "track" を指定することで、トラック情報を取得
                    },
                    headers: {
                        'Authorization': 'Bearer ' + spotifyAccessToken // spotifyAccessTokenが正しく定義されているか確認
                    },
                    success: function(response) {
                        console.log(response);  // ここでAPIから返ってきたレスポンスをコンソールに出力
                        displayResults(response);  // 検索結果を表示するために displayResults を呼び出す
                    },
                    error: function(err) {
                        console.error('エラーが発生しました:', err);
                    }
                });
            });

            // 曲をプレイリストに追加するボタンのクリックイベントを監視
            $(document).on('click', '.add-song', function() {
                const track = $(this).data('track');
                addSongSet(track);
                displayPlaylistCreateBtn()
            });

            // 「プレイリストを作成」ボタンのクリックイベントを監視
            console.log("作成前"); 
            $(document).on('click', '.playlist-create-btn', function() {

                console.log(addedSongs); // 追加されたすべての曲を確認

                sendToServer(addedSongs);
                console.log("プレイリストのサーバー通ったよ")
            });

//============================================================

            // 検索結果を表示する関数
            function displayResults(response) {
                const spotifyResults = response.spotify_results || [];  // Spotifyからの結果
                const resultsContainer = $('#dbResults');   // 検索結果を表示するコンテナのID
                        
                // コンテナの中身をクリアする
                resultsContainer.empty();   

                // 結果がある場合、表示
                if (spotifyResults.length > 0) {
                    spotifyResults.forEach(function(track) {
                        const listItem = `
                            <div>
                                <img src="${track.album_image_url}" alt="${track.song_name} album cover" style="width: 75px; height: 75px; object-fit: cover;">
                                <p>${track.song_name} : ${track.artist_name || 'Unknown Artist'}</p>
                                <button class="add-song" data-track='${JSON.stringify(track)}'>プレイリストに追加</button>
                            </div>`;
                        resultsContainer.append(listItem);
                    });
                } else {
                    resultsContainer.html('<p>曲が見つかりませんでした。</p>');
                }
            }

//============================================================

            // 曲をセットリストに追加する関数
            function addSongSet(track) {
                console.log("トラック名"); // trackの内容を確認
                console.log(track); // trackの内容を確認

                // 追加処理
                const index = addedSongs.length; // 現在の追加順を取得
                addedSongs.push({
                    song_name: track.song_name,
                    artist_name: track.artist_name,
                    song_spotify_id: track.song_spotify_id,
                    artist_spotify_id: track.artist_spotify_id
                });
                
                console.log("追加後のaddedSongs:", addedSongs); // trackの内容を確認

                // リストアイテムを作成
                const listItem = document.createElement('li');
                listItem.setAttribute('data-index', index); // 曲を識別するためのデータ属性を設定
                listItem.setAttribute('draggable', true); // ドラッグ可能にする

                // 曲名、アーティスト名、削除ボタンを含むHTML
                listItem.innerHTML = `
                    ${index + 1}. ${track.song_name} : ${track.artist_name || 'Unknown Artist'}
                    <button class="remove-song" data-index="${index}">×</button>
                `;

                // ドラッグイベントのリスナーを設定
                listItem.addEventListener('dragstart', handleDragStart);
                listItem.addEventListener('dragover', handleDragOver);
                listItem.addEventListener('drop', handleDrop);

                // リストアイテムをリストに追加
                $('#playlistSongs').append(listItem);

                // 削除ボタンのクリックイベントを設定
                $(listItem).find('.remove-song').click(function () {
                    const removeIndex = $(this).data('index'); // 削除する曲のインデックスを取得
                    removeSongSet(removeIndex); // 削除処理を実行
                });
            }

//============================================================

            // ドラッグの開始時
            function handleDragStart(e) {
                e.dataTransfer.setData('text/plain', e.target.dataset.index);
                e.target.classList.add('dragging');
            }

//============================================================

            // ドラッグしているアイテムが他のアイテムの上に来た時
            function handleDragOver(e) {
                e.preventDefault(); // デフォルトの動作を無効化してドロップ可能にする
            }

//============================================================

            // ドロップ時の処理
            function handleDrop(e) {
                e.preventDefault();
                e.target.classList.remove('dragging');

                const draggedIndex = e.dataTransfer.getData('text/plain'); // ドラッグしていた要素のインデックス
                const targetIndex = e.target.closest('li').dataset.index; // ドロップ先の要素のインデックス

                if (draggedIndex !== targetIndex) {
                    // DOMのリストを更新
                    const draggedItem = $('#playlistSongs li')[draggedIndex];
                    const targetItem = $('#playlistSongs li')[targetIndex];

                    if (draggedIndex < targetIndex) {
                        targetItem.after(draggedItem);
                    } else {
                        targetItem.before(draggedItem);
                    }

                    // addedSongsの順序を更新
                    const draggedSong = addedSongs.splice(draggedIndex, 1)[0];
                    addedSongs.splice(targetIndex, 0, draggedSong);
                    console.log("順序更新後のaddedSongs:", addedSongs);

                    // 曲順番号を更新
                    updateSongOrder();
                }
            }

//============================================================

            // 曲順を更新する関数
            function updateSongOrder() {
                const listItems = document.querySelectorAll('#playlistSongs li');

                listItems.forEach((item, index) => {
                    // 曲順番号を更新
                    const button = item.querySelector('.remove-song'); // 削除ボタンの位置を取得
                    const songText = item.textContent.split('. ')[1] || item.textContent; // 現在の曲情報を取得
                    const newText = `${index + 1}. ${songText.split('×')[0].trim()}`; // 新しい曲順番号と曲名を結合
                    item.innerHTML = `
                        ${newText}
                        <button class="remove-song" data-index="${index}">×</button>
                    `;

                    // ドラッグイベントを再設定
                    item.setAttribute('data-index', index);
                    item.setAttribute('draggable', true);
                    item.addEventListener('dragstart', handleDragStart);
                    item.addEventListener('dragover', handleDragOver);
                    item.addEventListener('drop', handleDrop);

                    // 削除ボタンのイベントを再設定
                    $(item).find('.remove-song').click(function () {
                        const removeIndex = $(this).data('index');
                        removeSongSet(removeIndex);
                    });

                    // addedSongs配列も更新
                    addedSongs[index].track_order = index + 1;
                });
            }

//============================================================

            // 曲をセットリストから削除する関数
            function removeSongSet(index) {
                console.log(`削除する曲のインデックス: ${index}`);
                
                // 配列から削除
                addedSongs.splice(index, 1);
                console.log("削除後のaddedSongs:", addedSongs);

                // リストを再描画
                redrawPlaylist();

		// ボタンの表示を更新（曲がゼロならボタンも消える）
    		displayPlaylistCreateBtn();
            }
//============================================================

            // セットリストを再描画する関数
            function redrawPlaylist() {
                const playlistContainer = $('#playlistSongs');
                playlistContainer.empty(); // 現在のリストをクリア

                // 配列の内容をリストに再描画
                addedSongs.forEach((song, i) => {
                    const listItem = document.createElement('li');
                    listItem.setAttribute('data-index', i);
                    listItem.setAttribute('draggable', true); // ドラッグ可能にする
                    listItem.innerHTML = `
                        ${i + 1}. ${song.song_name} : ${song.artist_name || 'Unknown Artist'}
                        <button class="remove-song" data-index="${i}">×</button>
                    `;
                    
                    // ドラッグイベントのリスナーを設定
                    listItem.addEventListener('dragstart', handleDragStart);
                    listItem.addEventListener('dragover', handleDragOver);
                    listItem.addEventListener('drop', handleDrop);

                    // 削除ボタンのイベントを設定
                    $(listItem).find('.remove-song').click(function () {
                        const removeIndex = $(this).data('index');
                        removeSongSet(removeIndex);
                    });

                    // リストに追加
                    playlistContainer.append(listItem);
                });

                console.log("リスト再描画後のaddedSongs:", addedSongs);
            }

//============================================================

            function displayPlaylistCreateBtn(){
                const resultsContainer = $('#playlistCreateBtn');
		const playlistContainer = $('#playlistSongs');   // 曲が追加されるリスト             
   
                // 追加された曲が1曲以上あるかを確認
    		if (addedSongs.length === 0) {
        	    resultsContainer.empty(); // 曲がない場合はボタンを非表示
        	return;
    		} 
                
		// すでにボタンが存在する場合は追加しない
    		if (resultsContainer.children().length === 0) {
                const listItem = `
                    <div>
                        <button class="playlist-create-btn" >プレイリストを作成</button>
                    </div>`
                    
                resultsContainer.append(listItem);
            	}
	    }

//============================================================

            // サーバーに曲情報を送信するための関数
            function sendToServer(argInAddedSongs) {
                // AJAXでユーザーIDを取得
                $.ajax({
                    url: '/get-user-data',  // ユーザーIDを取得するエンドポイント
                    method: 'GET',
                    success: function(userData) {
                        console.log('取得したuser_id:', userData.user_id);

                        const data = {
                            user_id: userData.user_id,   //PlaylistControllerのstoreメソッドからuser情報を引っ張ってきた方が良い
                            playlist_name: document.getElementById('playlist_name').value,  // ユーザーが入力したライブ名を取得
                            creator_comment: document.getElementById('creator_comment').value,  // ユーザーが入力したコメントを取得
                            //artistName: "アーティスト名",
                            argInAddedSongs: argInAddedSongs  // 配列を追加

                        };
                            
                        console.log('Sending data to server:', data);

                        $.ajax({
                            url: '/playlists/insert-playlist',  // セットリストを保存するエンドポイント
                            type: 'POST',
                            contentType: 'application/json',  // リクエストヘッダーを JSON と指定
                            data: JSON.stringify(data),  // データを JSON 文字列に変換して送信

                            success: function(response) {
                                console.log('Success:', response.message);
                                window.location.href = response.redirect_url;  // 成功時にリダイレクト
                            },
                            error: function(xhr) {
                                console.error('Error:', xhr.responseJSON);
                                if (xhr.responseJSON.errors) {
                                    alert(xhr.responseJSON.errors.join('\n')); // エラーメッセージを表示
                                } else {
                                    alert('エラーが発生しました。');
                                }
                            }
                        });
                    },
                    error: function(xhr) {
                        console.error('Failed to fetch user data:', xhr.responseJSON);
                        alert('ユーザー情報の取得に失敗しました。');
                    }
                });
            }
        });


    </script>
@endsection
    
