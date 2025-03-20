@extends('layouts.app')

@section('content')

    <!-- <h1>セットリスト作成</h1> -->
    <h1>{{ isset($isEdit) ? 'セットリスト編集' : 'セットリスト作成' }}</h1>


<div class="setlist-container">
    <!-- 左側の検索エリア -->
    <div class="left-panel">

    <form id="setlistForm" 
            action="{{ isset($isEdit) ? secure_url(route('setlists.update', $setlist->id, [], false)) : secure_url(route('setlists.store', [], false)) }}"
            method="POST">
        @csrf
        @if(isset($setlist))
            @method('PUT') <!-- 編集の場合は PUT メソッドを使用 -->
        @endif

        <!-- ライブ名の入力フィールド -->
        <div>
            <label for="live_name">ライブ名:</label>
            <input type="text" id="live_name" name="live_name" value="{{ old('live_name', isset($setlist) ? $setlist->live_name : '') }}" required>
            @error('live_name')
                <div>{{ $message }}</div>
            @enderror
        </div>

        <!-- 日付の入力フィールド -->
        <div>
            <label for="concert_date">開催日:</label>
            <input type="date" id="concert_date" name="concert_date" value="{{ old('concert_date', isset($setlist) ? $setlist->concert_date : '') }}" required>
            @error('concert_date')
                <div>{{ $message }}</div>
            @enderror
        </div>

        <!-- コメントの入力フィールド -->
        <div>
            <label for="creator_comment">作成者コメント:</label>
            <textarea id="creator_comment" name="creator_comment">{{ old('creator_comment', isset($setlist) ? $setlist->creator_comment : '') }}</textarea>
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
        <div id="setlist">
            <h2>セットリスト</h2>
            <ul id="setlistSongs">
                <!-- 追加された曲がここに表示される -->
            </ul>
        </div>

        <div id="setlistCreateBtn"></div>
  
    </div>
</div>
    

<style>
    .setlist-container {
        display: flex;
        gap: 20px;
	overflow-y: hidden;  /* コンテナ自体のスクロールは不要 */ 
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

	border-radius: 8px;
    	max-height: 80vh; /* 左側と高さを統一 */
    	overflow-y: auto; /* スクロール可能にする */

        /* min-height: 300px; */
	/* position: sticky; */
	/* top: 0;  常に上に固定する */
    }

    #setlistSongs {
        list-style: none;
        padding: 0;
	max-height: 100%; /* 親要素と高さを揃える */
    	overflow-y: auto; /* ここもスクロール可能に */
    }

    #setlistSongs li {
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


<!-- 追加 -->
<script>
    let existingSongs = @json($setlist->songs ?? []);
</script>

    
    <script>

    // CSRFトークンの設定
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {

	    let addedSongs = []; // 曲を追加するための配列
	    let draggedItem; //ドラッグ時の要素を保持する変数


	    if (existingSongs.length > 0) { 
		displaySetlistCreateBtn(); // 既存の曲が1曲でもあればボタンを表示 
	    }
	

	    console.log("existingSongsのデータ:", existingSongs);

            existingSongs.forEach((song, index) => {

		const addedSong = {  
		    song_name: song.song_name,  	
		    artist_name: song.artist ? song.artist.artist_name : "Unknown Artist", // ネストされた artist から取得  
		    song_spotify_id: song.spotify_id ?? "値なし",  // 修正ポイント！
		    artist_spotify_id: song.artist ? song.artist.spotify_id : null // ネストされた artist から取得
		};

		console.log("追加する曲オブジェクト:", addedSong); // ここで確認
		addedSongs.push(addedSong);

            	const listItem = document.createElement('li');
            	listItem.textContent = `${song.song_name} - ${song.artist ? song.artist.artist_name : "Unknown Artist"}`;
		listItem.setAttribute('data-index', index);            	
		listItem.setAttribute('draggable', true); // ドラッグ可能にする
		listItem.classList.add('setlist-item'); // クラスを追加（後でイベント登録するため）

		// **削除ボタンを追加** 
		const removeBtn = document.createElement('button'); 
		removeBtn.textContent = '×'; 
		removeBtn.classList.add('remove-song'); 
		removeBtn.setAttribute('data-index', index); 
		listItem.appendChild(removeBtn); // 削除ボタンをリストに追加		

		document.getElementById('setlistSongs').appendChild(listItem);
            });


	    $(document).on('click', '.remove-song', function() {
    		const index = $(this).closest('li').data('index');
    		addedSongs.splice(index, 1); // 配列から削除
    		$(this).closest('li').remove(); // UIから削除

    		// インデックスを更新（削除後のズレを防ぐ） 
    		$('#setlistSongs li').each(function(i) { 
		    $(this).attr('data-index', i); 
		    $(this).find('.remove-song').attr('data-index', i); 
    		});
	    });
  


	    // ドラッグ&ドロップ機能
	    $(document).on('dragstart', '.setlist-item', function(event) {
    		draggedItem = this;
    		$(this).addClass('dragging');
	    });

	    $(document).on('dragover', '#setlistSongs', function(event) {
    		event.preventDefault();
	    });

	    $(document).on('drop', '#setlistSongs', function(event) {
    		event.preventDefault();
    		if (draggedItem) {
        	    $(this).append(draggedItem);
        	    $(draggedItem).removeClass('dragging');
        	    draggedItem = null;
    		}
	    });


            // フォーム送信を停止する
            $('#setlistForm').submit(function(event) {
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

            // 曲をセットリストに追加するボタンのクリックイベントを監視
            $(document).on('click', '.add-song', function() {
                const track = $(this).data('track');
                addSongSet(track);
                displaySetlistCreateBtn()
            });

            // 「セットリストを作成」ボタンのクリックイベントを監視
            console.log("作成前"); 
            $(document).on('click', '.setlist-create-btn', function() {

                console.log(addedSongs); // 追加されたすべての曲を確認

                sendToServer(addedSongs);
                console.log("サーバー通ったよ")
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
                                <button class="add-song" data-track='${JSON.stringify(track)}'>セットリストに追加</button>
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

		
		document.getElementById('setlistSongs').appendChild(listItem);


                // ドラッグイベントのリスナーを設定
                listItem.addEventListener('dragstart', handleDragStart);
                listItem.addEventListener('dragover', handleDragOver);
                listItem.addEventListener('drop', handleDrop);

                // リストアイテムをリストに追加
                $('#setlistSongs').append(listItem);

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
                const dropTarget = e.target.closest('li'); 
		if (!dropTarget) return;  

		const targetIndex = dropTarget.dataset.index;


                if (draggedIndex !== targetIndex) {
                    // DOMのリストを更新
                    const draggedSong = addedSongs.splice(draggedIndex, 1)[0];
                    addedSongs.splice(targetIndex, 0, draggedSong);

                    
                    console.log("順序更新後のaddedSongs:", addedSongs);

                    // 曲順番号を更新
                    redrawSetlist();  // 並び替え後にリストを再描画
                }
            }

//============================================================

            // 曲順を更新する関数
            function updateSongOrder() {
                const listItems = document.querySelectorAll('#setlistSongs li');

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
                redrawSetlist();


		// ボタンの表示を更新（曲がゼロならボタンも消える）
    		displaySetlistCreateBtn();


            }
//============================================================

            // セットリストを再描画する関数
            function redrawSetlist() {
                const setlistContainer = document.getElementById('setlistSongs');
                setlistContainer.innerHTML = ""; // 現在のリストをクリア 

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
                    listItem.addEventListener('dragover', e => e.preventDefault());
                    listItem.addEventListener('drop', handleDrop);


                    // リストに追加
                    setlistContainer.appendChild(listItem);
                });

		// 削除ボタンのイベントを再設定 
		document.querySelectorAll('.remove-song').forEach(button => { 
			button.addEventListener('click', function () { 
				const removeIndex = this.dataset.index; 
				removeSongSet(removeIndex); 
			}); 
		});


                console.log("リスト再描画後のaddedSongs:", addedSongs);
            }
            
//============================================================

            function displaySetlistCreateBtn(){
                if (addedSongs.length > 0 || existingSongs.length > 0) {
        	    if ($('#setlistCreateBtn button').length === 0) {
            		$('#setlistCreateBtn').html('<button class="setlist-create-btn">セットリストを作成</button>');
        	    }
    		} else {
        	    $('#setlistCreateBtn').empty(); // 曲がない場合はボタンを非表示
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
                            user_id: userData.user_id,   //SetlistControllerのstoreメソッドからuser情報を引っ張ってきた方が良い
                            live_name: document.getElementById('live_name').value,  // ユーザーが入力したライブ名を取得
                            concert_date: document.getElementById('concert_date').value,  // ユーザーが入力した開催日を取得
                            creator_comment: document.getElementById('creator_comment').value,  // ユーザーが入力したコメントを取得
                            //artistName: "アーティスト名",
                            argInAddedSongs: argInAddedSongs  // 配列を追加

                        };
                            
                        console.log('Sending data to server:', data);

                        $.ajax({
                            url: '/setlists/insert-setlist',  // セットリストを保存するエンドポイント
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
