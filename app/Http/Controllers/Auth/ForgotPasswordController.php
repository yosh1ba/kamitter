<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    public function __construct()
    {
        $this->middleware('guest');
    }

    // public function sendResetLinkEmail(Request $request)
    // {
    //     //メールリセットのリクエストをチェックする
    //     $validate = $this->validateEmail($request->all());

    //     // メールが存在しないときはエラーを返す
    //     if ($validate->fails()) {
    //         return new JsonResponse('Email is Invalid');
    //     }


    //     $response = $this->broker()->sendResetLink(
    //         $request->only('email')
    //     );

    //     return $response == Password::RESET_LINK_SENT
    //         ? $this->sendResetLinkResponse($request, $response)
    //         : $this->sendResetLinkFailedResponse($request, $response);
    // }

    // //メールアドレスチェック
    // protected function validateEmail(array $data)
    // {
    //     return Validator::make($data, [
    //         'email' => 'required|email',
    //     ]);
    // }

    // // パスワードリセットメールが送信できたときのレスポンス内容を記載する
    // protected function sendResetLinkResponse(): JsonResponse
    // {
    //     return response()->json([
    //         'success' => true,
    //         'data' => [
    //             "Send Reset Mail"
    //         ]
    //     ], 200);
    // }

    // // リセットメール送信が失敗したときに
    // protected function sendResetLinkFailedResponse(Request $request, $response): JsonResponse
    // {
    //     return response()->json([
    //         'success' => false,
    //         'errors' => trans($response)
    //     ], 422);
    // }
}
