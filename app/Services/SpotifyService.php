<?php

namespace App\Services;

use SpotifyWebAPI\SpotifyWebAPI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

// Autoloadをプロジェクトのルートから読み込む
require_once __DIR__ . '/../../vendor/autoload.php'; // Autoloadのパスを修正


class SpotifyService
{
    protected $accessToken;
    protected $spotifyAPI;

//=================================================================

    public function __construct()
    {
        $this->spotifyAPI = new SpotifyWebAPI();
        $this->setAccessToken(); // アクセストークンを設定するメソッドを呼び出す
    }

//=================================================================

    public function setAccessToken()
    {
        // セッションからアクセストークンを取得
        $accessToken = session('spotify_access_token');
        
        if ($accessToken) {
            $this->spotifyAPI->setAccessToken($accessToken); // SpotifyWebAPIのインスタンスにアクセストークンを設定
            $this->accessToken = $accessToken; // 新しいメソッド用にも保存
            Log::info('アクセストークンOK: ' . $accessToken);
        } else {
            Log::error('アクセストークンが見つかりません。');
        }
    }

//=================================================================

    // プレイリスト用：プレイリストを作成するメソッド
    public function createPlaylist($spotifyUserId, $playlistName, $description = "Generated from Playlist", $public = false)
    {
        // Spotify APIを呼び出し
        $response = Http::withToken($this->accessToken)->post("https://api.spotify.com/v1/users/{$spotifyUserId}/playlists", [
            'name' => $playlistName,
            'description' => $description,
            'public' => $public,
        ]);

        if ($response->successful()) {
            return $response->json()['id'];  // 成功した場合、プレイリストのIDを返す
        }

        throw new \Exception("プレイリストFailed to create playlist: " . $response->body());
    }

//=================================================================

    // プレイリスト用：プレイリストにトラックを追加するメソッド
    public function addTracksToPlaylist($accessToken, string $playlistId, array $trackUris)
    {
        // リクエストのURLと送信データをログに記録
        /*Log::info('プレイリストにトラックを追加するリクエスト送信: ', [
            'playlistId' => $playlistId,
            'trackUris' => $trackUris
        ]);*/

        $response = Http::withToken($this->accessToken)->post("https://api.spotify.com/v1/playlists/{$playlistId}/tracks", [
            'uris' => $trackUris,
        ]);

        // レスポンスをログに記録
        /*Log::info('Spotify APIからのレスポンス: ', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);*/

        // エラーハンドリング
        if ($response->failed()) {
            // レスポンスが失敗した場合、エラー内容をログに記録
            Log::error("プレイリストにトラックを追加できませんでした。レスポンス: " . $response->body());
            throw new \Exception("Failed to add tracks to playlist: " . $response->body());
        } else {
            Log::info("プレイリストにトラックが正常に追加されました。");
        }
    }

//=================================================================
//=================================================================

    // セットリスト用：プレイリストを作成するメソッド
    public function createSetlist($spotifyUserId, $playlistName, $description = "Generated from Setlist", $public = false)
    {
        // Spotify APIを呼び出し
        $response = Http::withToken($this->accessToken)->post("https://api.spotify.com/v1/users/{$spotifyUserId}/playlists", [
            'name' => $playlistName,
            'description' => $description,
            'public' => $public,
        ]);

        if ($response->successful()) {
            return $response->json()['id'];  // 成功した場合、プレイリストのIDを返す
        }

        throw new \Exception("ああFailed to create playlist: " . $response->body());
    }

//=================================================================

    // セットリスト用：プレイリストにトラックを追加するメソッド
    public function addTracksToSetlist($accessToken, string $playlistId, array $trackUris)
    {
        // リクエストのURLと送信データをログに記録
        /*Log::info('プレイリストにトラックを追加するリクエスト送信: ', [
            'playlistId' => $playlistId,
            'trackUris' => $trackUris
        ]);*/

        $response = Http::withToken($this->accessToken)->post("https://api.spotify.com/v1/playlists/{$playlistId}/tracks", [
            'uris' => $trackUris,
        ]);

        // レスポンスをログに記録
        /*Log::info('Spotify APIからのレスポンス: ', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);*/

        // エラーハンドリング
        if ($response->failed()) {
            // レスポンスが失敗した場合、エラー内容をログに記録
            Log::error("プレイリストにトラックを追加できませんでした。レスポンス: " . $response->body());
            throw new \Exception("Failed to add tracks to playlist: " . $response->body());
        } else {
            Log::info("プレイリストにトラックが正常に追加されました。");
        }
    }

//=================================================================

    // SpotifyWebAPIライブラリのsearchメソッドを使って検索
    public function searchTracks($query)
    {
        // $queryがnullかどうかをチェックし、nullであればログを出力してエラーを防ぐ
        if (is_null($query)) {
            Log::error('Spotify APIに渡す検索クエリがnullです');
            return null; // nullを返してエラーを防ぐ、またはデフォルト値を使用する
        }
        
        // Spotify APIへのリクエスト前にログを出力
        Log::info('Spotify APIへ検索リクエスト送信: ' . $query);

        try {
            // Spotify APIへのリクエスト時に日本語の結果を取得するようにリクエストヘッダーを設定
            $this->spotifyAPI->setOptions([
                'headers' => [
                    'Accept-Language' => 'ja'
                ]
            ]);

            // Spotify APIでトラックを検索
            $response = $this->spotifyAPI->search($query, 'track');

            // Spotify APIからのレスポンスをログに出力
            Log::info('Spotify APIからのレスポンス: ' . json_encode($response));

            return $response;

        } catch (\Exception $e) {
            Log::error('Spotify API searchTracks エラー: ' . $e->getMessage());
            throw $e;
        }
    }

//=================================================================
    public function getTrackById($trackId)
    {
        try {
            $this->spotifyAPI->setOptions([
                'headers' => [
                    'Accept-Language' => 'ja'
                ]
            ]);

            // Spotify APIの`getTrack`メソッドで特定のトラックの情報を取得
            $track = $this->spotifyAPI->getTrack($trackId);

            // トラック情報をログ出力
            //Log::info('Spotify APIから取得したトラック情報: ' . json_encode($track));

            return $track;

        } catch (\Exception $e) {
            Log::error('Spotify API getTrackById エラー: ' . $e->getMessage());
            throw $e;
        }
    }

}