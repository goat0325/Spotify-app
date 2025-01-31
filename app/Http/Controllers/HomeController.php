<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class HomeController extends Controller
{
    public function index()
    {
        // 認証されたユーザー情報を取得
        $user = Auth::user();
        return view('home', compact('user')); // resources/views/home.blade.php でユーザー情報を表示
    }
}