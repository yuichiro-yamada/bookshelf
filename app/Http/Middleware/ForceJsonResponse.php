<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API（/api/*）へのリクエストを、常に「JSONで返してほしい」リクエストとして扱う。
 *
 * Laravel は Accept: application/json が付いていないリクエストを画面（ブラウザ）からの
 * リクエストとみなし、未認証ならログイン画面へ、バリデーションエラーなら元の画面へ
 * リダイレクトし、404 は HTML の画面で返す。APIクライアントが Accept ヘッダーを
 * 付け忘れても 401 / 422 / 404 を JSON で返せるよう、Accept ヘッダーを補う。
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
