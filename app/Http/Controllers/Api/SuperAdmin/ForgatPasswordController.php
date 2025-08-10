<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgetPasswordRequest;
use App\Mail\ForgetPasswordMail;
use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgatPasswordController extends Controller
{
    use ResponseTrait;

    public function sendResetCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = SuperAdmin::where('email', $request->email)->first();
        if (!$user) {
            return $this->returnError(404, 'الحساب غير مسجل');
        }
        $code = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(30);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );
        Mail::to($user->email)->queue(new ForgetPasswordMail(
            $code
        ));
        return $this->returnData(['expires_at' => $expiresAt->toDateTimeString()],  'تم إرسال رمز إعادة التعيين',);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|digits:6'
        ]);
        $record = DB::table('password_reset_tokens')
            ->where('email', $request['email'])
            ->first();
        if (!$record || !Hash::check($request->otp_code, $record->token)) {
            return $this->returnError(400, 'الرمز غير صحيح');
        }
        $tempToken = Str::random(60);
        DB::table('password_reset_tokens')
            ->where('email', $request['email'])
            ->update(['token' => Hash::make($tempToken)]);
        return $this->returnData(['temp_token' => $tempToken], 'تم التحقق بنجاح');
    }

    public function forgotPassword(ForgetPasswordRequest $request)
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $request['email'])
            ->first();
        if (!$record || !Hash::check($request['token'], $record->token)) {
            return $this->returnError(400,  'الرمز غير صحيح أو منتهي الصلاحية');
        }
        $affectedRows = SuperAdmin::where('email', $request['email'])
            ->update(['password' => Hash::make($request['password'])]);
        if ($affectedRows === 0) {
            return $this->returnError(404,  'لم يتم العثور على أي حسابات بهذا الإيميل');
        }
        DB::table('password_reset_tokens')->where('email', $request['email'])->delete();
        return $this->returnData(200,  'تم تحديث كلمة المرور بنجاح');
    }
}
