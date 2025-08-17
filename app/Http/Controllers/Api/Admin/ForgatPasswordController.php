<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgetPasswordRequest;
use App\Http\Requests\ModifyPasswordRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Jobs\SendEmailTolUser;
use App\Mail\ForgetPasswordMail;
use App\Models\Admin;
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


    public function modifyPassword(Request $request)
    {
        $actor = Admin::where('email', $request->email)->first();
        // return $actor;
        if ($actor) {
            do {
                $randomNumber = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $exists = Admin::where('code', $randomNumber)->exists();
            } while ($exists);
            $actor->update([
                'code' => $randomNumber,
            ]);

            SendEmailTolUser::dispatch(
                $actor->email,
                $actor->name,
                $randomNumber
            );
            return $this->messageSuccessResponse('تم إرسال الرابط.', 200);
        }
        return response()->json(['status' => false, 'message' => trans('locale.error')], 400);
    }


    public function codeVerification(Request $request)
    {
        $actor = Admin::where('code', $request->code)->first();
        if ($actor) {
            $tokenResult = $actor->createToken('auth_token');
            $token = $tokenResult->plainTextToken;
            $tokenResult->accessToken->forceFill(['platform' => $request->header('User-Agent')])->save();
            $data = [
                'token' => $token,
                'actor' => $actor
            ];
            return $this->successResponse($data, trans('locale.successfully'), 200);
        }
        return $this->messageErrorResponse(trans('locale.error'), 400);
    }

    public function resetPassword(UpdatePasswordRequest $request)
    {
        $actor = auth()->user();
        if ($actor) {
            if ($request->repeat_password == $request->password) {
                $newPassword = Hash::make($request->password);

                $actor->update([
                    'password' => $request->password,
                    'code' => null,
                ]);
                return $this->messageSuccessResponse(trans('locale.successfully'), 200);
            }
            return $this->messageErrorResponse(trans('locale.passwordDoesNotMatch'), 400);
        }
    }
}
