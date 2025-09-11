<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $response = Password::sendResetLink($request->only('email'));

        if ($response == Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'رابط إعادة تعيين كلمة المرور تم إرساله إلى بريدك الإلكتروني.']);
        }

        return response()->json(['error' => 'حدث خطأ أثناء إرسال الرابط.'], 500);
    }
}
