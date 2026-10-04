<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * モデルクラス名（class_basename）と、404時に返す日本語メッセージの対応表
     *
     * @var array<string, string>
     */
    protected array $notFoundMessages = [
        'Book' => '指定された書籍が見つかりません',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // ルートモデルバインディングで該当データが見つからなかった場合、
        // JSON（API）リクエストにはデフォルトの英語メッセージではなく
        // 日本語のメッセージを返す。
        //
        // Laravel は renderable を呼ぶ前に ModelNotFoundException を
        // NotFoundHttpException に置き換えるため、ここでは NotFoundHttpException を
        // 受け取り、元の例外（getPrevious）が ModelNotFoundException かどうかで判定する。
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            $previous = $e->getPrevious();

            if ($previous instanceof ModelNotFoundException && $request->expectsJson()) {
                $model = class_basename($previous->getModel());

                return response()->json([
                    'message' => $this->notFoundMessages[$model] ?? '指定されたデータが見つかりません',
                ], 404);
            }
        });

        // 未認証（401）。JSON（API）リクエストには英語の既定メッセージではなく日本語で返す。
        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => '認証が必要です'], 401);
            }
        });

        // 認可エラー（403）。Policy などで拒否された JSON（API）リクエストに日本語で返す。
        // Laravel は AuthorizationException を AccessDeniedHttpException に置き換えてから
        // renderable を呼ぶため、ここでは AccessDeniedHttpException を受け取る。
        $this->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'この操作を行う権限がありません'], 403);
            }
        });
    }
}
