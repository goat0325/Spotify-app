<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\User; // ユーザーモデルのインポート
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use SpotifyWebAPI\SpotifyWebAPI;


class LoginController extends Controller
{
    // 今回追加箇所
    protected $spotifyApi;

    public function __construct()
    {
        // SpotifyのAPIインスタンスを作成
        $this->spotifyApi = new SpotifyWebAPI();
    }

//===========================================================================

    // ログイン画面を表示するメソッド
    public function showLoginForm()
    {
        return view('auth.login');  //resources/views/auth/login.blade.php でログイン画面を作成
    }

//===========================================================================

    // Spotify認証ページへのリダイレクトメソッド
    public function redirectToSpotify()
    {
        $clientId = config('services.spotify.client_id');
        $redirectUri = config('services.spotify.redirect_uri');

        // 今回追加箇所　認証時に必要なスコープを指定
        $scope = implode(' ', [
            'user-read-private',
            'user-read-email',
            'user-modify-playback-state',
            'user-read-playback-state',
            'user-read-currently-playing',
            'playlist-modify-public',
            'playlist-modify-private'
        ]);

        $url = "https://accounts.spotify.com/authorize?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            // 必要なスコープを指定
            'scope' => $scope,
            'prompt' => 'login', // 毎回ログイン画面を表示させる
        ]);

        return redirect($url);
    }

//===========================================================================

    // ログイン処理を行うメソッド
    public function login(Request $request)
    {
        // 直接的なログイン処理は行わず、Spotifyの認証を経る
        return redirect()->route('spotify.redirect'); // Spotifyのリダイレクトへ
    }

//===========================================================================

    // Spotifyからのコールバックを処理するメソッド
    public function handleSpotifyCallback(Request $request)
    {
        $code = $request->get('code');

        if (is_null($code)) {
            return redirect()->route('login')->withErrors(['spotify' => '認証プロセスに問題があります。']);
        }

        try {
            // Spotifyのアクセストークンを取得するためのGuzzleリクエスト
            $client = new Client();
            $response = $client->post('https://accounts.spotify.com/api/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => config('services.spotify.redirect_uri'),
                    'client_id' => config('services.spotify.client_id'),
                    'client_secret' => config('services.spotify.client_secret'),
                ],
            ]);

            // ステータスコードを確認
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Unable to get access token.');
            }

            // 取得したトークンの処理
            $token = json_decode($response->getBody(), true);
            $accessToken = $token['access_token'];
            $refreshToken = $token['refresh_token'];

            // Spotify APIインスタンスにアクセストークンをセット
            $this->spotifyApi->setAccessToken($accessToken);

            // ユーザー情報を取得
            $user = $this->getUserInfo($accessToken, $refreshToken);

            if (!$user) {
                return redirect()->route('login')->withErrors(['spotify' => 'ユーザー情報の取得に失敗しました。']);
            }

            // ユーザーのログイン処理
	    Auth::login($user);

            $this->authenticated($request, $user);
	    
            return redirect()->route('home');
        } catch (\Exception $e) {
            Log::error('Spotify APIエラー: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['spotify' => 'Spotifyとの認証に失敗しました。']);
        }
    }

//===========================================================================

    // ユーザー情報を取得してデータベースに保存するメソッド
    protected function getUserInfo($accessToken, $refreshToken)
    {
        $client = new Client();

	try {
            $response = $client->get('https://api.spotify.com/v1/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);


            // ステータスコードを確認
	    if ($response->getStatusCode() !== 200) {
		    if ($response->getStatusCode() === 401) {
		    // アクセストークンが期限切れの場合
                    $newToken = $this->refreshAccessToken($refreshToken);
			if (isset($newToken['access_token'])) {
				// 新しいトークンを使用して再度API呼び出し
				$accessToken = $newToken['access_token'];
				$response = $client->get('https://api.spotify.com/v1/me', [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $accessToken,
                            ],
                        ]);
                    } else {
                        throw new \Exception('アクセストークンの更新に失敗しました: ' . $newToken['error']);
                    }
                } else {
                    throw new \Exception('Unable to get user info.');
                }
	    }

            $userData = json_decode($response->getBody(), true);

            // Spotifyユーザー情報をログに出力
            Log::info('Spotify User Data: ' . json_encode($userData));


            // データベースにユーザー情報を保存
            $user = User::updateOrCreate(
                ['spotify_user_id' => $userData['id']],
                [
                    'account_name' => !empty($userData['display_name']) ? $userData['display_name'] : 'ゲストユーザー',
                    'access_token' => $accessToken,  // アクセストークン
                    'refresh_token' => $refreshToken, // リフレッシュトークン
                    //'refresh_token' => $userData['refresh_token'] ?? null, // 取得できなかった場合はnull
                    'profile_image' => $userData['images'][0]['url'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // アクセストークンをセッションに保存
            session(['spotify_access_token' => $accessToken]);
            session(['spotify_refresh_token' => $refreshToken]);
            
            // ユーザーを返す
            return $user;

            Auth::login($user); // ユーザーをログインさせる

        } catch (\Exception $e) {
            Log::error('ユーザー情報取得エラー: ' . $e->getMessage());
        }
    }

//===========================================================================

    protected function refreshAccessToken($refreshToken) {
        $url = "https://accounts.spotify.com/api/token";

        $headers = [
            'Authorization: Basic ' . base64_encode(config('services.spotify.client_id') . ':' . config('services.spotify.client_secret')),
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }


//===========================================================================

    // LoginControllerなどで、ログイン成功後にトークンをセッションに保存する
    public function authenticated(Request $request, $user) {
        // データベースからアクセストークンを取得
        $accessToken = $user->access_token;

        // セッションに保存
        $request->session()->put('spotify_access_token', $accessToken);
        
        // 必要であればリフレッシュトークンも保存
        $request->session()->put('spotify_refresh_token', $user->refresh_token);

        //確認用ログ
        //Log::info('現在のセッションから取得したアクセストークン: ' . $accessToken);
    }

//===========================================================================

    //ログアウト処理を行うメソッド
    public function logout(Request $request) // ← Requestを引数で受け取る
    {
        // 特定のクッキーを削除
        Cookie::queue(Cookie::forget('クッキー名'));

        Auth::logout();  //ユーザーのセッションを終了
        $request->session()->invalidate(); // セッションを無効化
        $request->session()->regenerateToken(); // CSRFトークンを再生成

        // ここでSpotify関連のセッションデータを削除
        $request->session()->forget('access_token');
        $request->session()->forget('refresh_token');

        return redirect('/login');
    }
}
