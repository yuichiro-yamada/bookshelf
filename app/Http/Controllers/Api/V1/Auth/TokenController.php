<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\TokenRequest;
use Illuminate\Http\JsonResponse;

class TokenController extends Controller
{
    /**
     * アクセストークンを発行する
     *
     * POST /api/v1/auth/token
     *
     * メールアドレスとパスワードで認証し、Sanctum の個人アクセストークンを返す。
     * 発行したトークンは、書籍の登録・更新・削除など認証が必要な API で
     * Authorization: Bearer {トークン} として使用する。
     * 平文のトークンは発行時のレスポンスでしか取得できない。
     */
    public function store(TokenRequest $request): JsonResponse
    {
        $user = $request->authenticate();

        $token = $user->createToken($request->validated('device_name') ?? 'api-token');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }
}
