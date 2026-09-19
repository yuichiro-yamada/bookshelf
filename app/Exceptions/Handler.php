<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
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
    }
}
