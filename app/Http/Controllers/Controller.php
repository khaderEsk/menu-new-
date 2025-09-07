<?php

namespace App\Http\Controllers;

use App\Http\Requests\Delivey\AddRequest;
use App\Http\Requests\LoginDeliveryRequest;
use App\Http\Requests\UpdateSuperAdminRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\ModifyPasswordRequest;
use App\Http\Resources\CitySuperAdminResource;
use App\Http\Resources\LoginAdminResource;
use App\Jobs\SendEmailTolUser;
use App\Mail\SendCodeMail;
use App\Models\Admin;
use App\Models\Restaurant;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, ResponseTrait;

    public function logout()
    {
        $user = auth()->user();
        $user->update([
            'fcm_token' => null,
        ]);
        $user->tokens()->delete();
        return $this->messageSuccessResponse('logout successfully', 200);
    }
    // Login For Actors
    public function Login(LoginRequest $request)
    {
        $model = \request()->model;
        $actor = ('App\Models\\' . $model)::where('user_name', $request->user_name)->first();
        // dd($actor);
        if ($actor && Hash::check($request->password, $actor->password)) {
            $platform = $request->header('platform');
            // $token = $actor->createToken('authToken', [$model])->accessToken;
            if ($actor->is_active == 0) {
                return response()->json(['status' => false, 'message' =>  trans('locale.blocked')]);
            }
            if ($actor->hasAnyRole(['admin', 'employee', 'restaurantManager', 'customer'])) {
                if ($actor->getTable() == 'admins') {
                    if ($actor->restaurant !== null) {
                        if ($actor->restaurant->end_date < Carbon::now()->toDateString()) {
                            return response()->json(['status' => false, 'message' => trans('locale.restaurantHasExpired')]);
                        }
                    }
                }
            }
            // $token = $actor->createToken('Auth-Token')->plainTextToken;
            $tokenResult = $actor->createToken('auth_token');
            $token = $tokenResult->plainTextToken;
            $tokenResult->accessToken->forceFill(['platform' => $request->header('User-Agent')])->save();

            // If Actor Admin And Request Have Fcm Token Save It
            if ($request->fcm_token != null && $model == 'Admin') {
                $actor->update([
                    'fcm_token' => $request->fcm_token,
                ]);
            }

            $actor['token'] = $token;
            if ($model == 'Admin') {
                if ($actor->restaurant !== null) {
                    $restaurant = Restaurant::whereId($actor->restaurant_id)->first();
                    // $data = RestaurantResource::make($restaurant);
                    $actor['restaurant'] = $restaurant;
                    $data['roles'] = $actor->roles->pluck('name');
                    $data['permissions'] = $actor->permissions->pluck('name');
                    // if($actor->roles[0]->name == 'admin')
                    // {
                    //     if($actor->restaurant->is_active == 0)
                    //         return response()->json(['status' => false ,'message'=> trans('locale.blocked')]);
                    //     $query = $actor->permissions();
                    //     $restaurant = Restaurant::whereId($actor->restaurant_id)->first(['is_advertisement','is_rate','is_table','is_order','is_news']);
                    //     if ($restaurant->is_advertisement == 0) {
                    //         $search = 'advertisement';
                    //         $query->where('name','not like', "%$search%");
                    //     }

                    //     if ($restaurant->is_rate == 0) {
                    //         $search = 'rate';
                    //         $query->where('name','not like', "%$search%");
                    //     }
                    //     if ($restaurant->is_table == 0) {
                    //         $search = 'table';
                    //         $query->where('name','not like', "%$search%");
                    //     }
                    //     if ($restaurant->is_order == 0) {
                    //         $search = 'order';
                    //         $query->where('name','not like', "%$search%");
                    //     }
                    //     if ($restaurant->is_news == 0) {
                    //         $search = 'news';
                    //         $query->where('name','not like', "%$search%");
                    //     }
                    //     $permissions = $query->get();
                    //     $actor['permissions'] = $permissions;
                    // }
                    $data = LoginAdminResource::make($actor);
                    return response()->json(['status' => true, 'data' => $data, 'message' => trans('locale.login')], 200);
                }
                $actor['roles'] = $actor->roles->pluck('name');
                $actor['permissions'] = $actor->permissions->pluck('name');
                return response()->json(['status' => true, 'data' => $actor, 'message' => trans('locale.login')], 200);
            }
            // $role = $actor->roles->unique($actor['id']);
            // $permissions = $actor->getAllPermissions()->unique($actor['id']);
            // $data = [$actor,$permissions];
            else {
                $actor['roles'] = $actor->roles->pluck('name');
                $actor['permissions'] = $actor->permissions->pluck('name');
                return response()->json(['status' => true, 'data' => $actor, 'message' => trans('locale.login')], 200);
            }
        }
        return response()->json(['status' => false, 'message' => trans('locale.error')], 400);
    }

    // Update Super Admin Details
    public function UpdateSuperAdmin(UpdateSuperAdminRequest $request)
    {
        $admin = auth()->user();
        $data = $request->validated();
        $superAdmin = SuperAdmin::find($admin->id);
        if ($request->has('password')) {
            if (is_null($data['password']))
                $data = Arr::only($data, ['name', 'user_name']);
            else
                $data['password'] = $data['password'];
        }
        $superAdmin->update($data);
        $data = CitySuperAdminResource::make($superAdmin);
        return $this->successResponse($data, trans('locale.updated'), 200);
    }

    // Update fcm token
    public function fcmToken(Request $request)
    {
        $admin = auth()->user();
        $superAdmin = Admin::find($admin->id);
        $data['fcm_token'] = $request->fcm_token;
        $superAdmin->update($data);
        return $this->messageSuccessResponse(trans('locale.successfully'), 200);
    }

    public function LoginUser(LoginUserRequest $request)
    {
        $actor = User::where('restaurant_id', $request->restaurant_id)->where(function ($query) use ($request) {
            $query->where('username', $request->username)
                ->orWhere('phone', $request->username);
        })->first();
        if ($actor && Hash::check($request->password, $actor->password)) {
            $platform = $request->header('platform');
            if ($actor->is_active == 0) {
                return response()->json(['status' => false, 'message' =>  trans('locale.blocked')]);
            }

            $tokenResult = $actor->createToken('auth_token');
            $token = $tokenResult->plainTextToken;
            $tokenResult->accessToken->forceFill(['platform' => $request->header('User-Agent')])->save();

            if ($request->fcm_token != null) {
                $actor->update([
                    'fcm_token' => $request->fcm_token,
                ]);
            }
            $actor['token'] = $token;
            return response()->json(['status' => true, 'data' => $actor, 'message' => trans('locale.login')], 200);
        }
        return response()->json(['status' => false, 'message' => trans('locale.error')], 400);
    }

    public function LoginDelivery(LoginDeliveryRequest $request)
    {
        $actor = User::where('role', 1)->where('username', $request->username)->first();
        if ($actor && Hash::check($request->password, $actor->password)) {
            $platform = $request->header('platform');
            // if ($actor->is_active == 0)
            // {
            //     return response()->json(['status' => false ,'message'=>  trans('locale.blocked')]);
            // }

            $tokenResult = $actor->createToken('auth_token');
            $token = $tokenResult->plainTextToken;
            $tokenResult->accessToken->forceFill(['platform' => $request->header('User-Agent')])->save();

            if ($request->fcm_token != null) {
                $actor->update([
                    'fcm_token' => $request->fcm_token,
                ]);
            }
            $actor['token'] = $token;
            return response()->json(['status' => true, 'data' => $actor, 'message' => trans('locale.login')], 200);
        }
        return response()->json(['status' => false, 'message' => trans('locale.error')], 400);
    }

    // show questions
    public function question(Request $request)
    {
        $locale = app()->getLocale();
        if ($locale == 'ar') {
            $questions = [
                ['id' => 1, 'question' => 'ما هو اسم والدك؟'],
                ['id' => 2, 'question' => 'ما هو اسم أول حيوان أليف لك؟'],
                ['id' => 3, 'question' => 'ما هي المدينة التي ولدت فيها؟'],
                ['id' => 4, 'question' => 'ما هو اسم أول مدرسة درست فيها؟'],
                ['id' => 5, 'question' => 'ما هو الطعام المفضل لديك في طفولتك؟']
            ];
        } else {
            $questions = [
                ['id' => 1, 'question' => 'What is your father\'s name?'],
                ['id' => 2, 'question' => 'What is the name of your first pet?'],
                ['id' => 3, 'question' => 'What city were you born in?'],
                ['id' => 4, 'question' => 'What is the name of the first school you attended?'],
                ['id' => 5, 'question' => 'What was your favorite childhood food?']
            ];
        }
        return $this->successResponse($questions, trans('locale.foundSuccessfully'), 200);
    }

    public function modifyPassword(ModifyPasswordRequest $request)
    {
        if ($request->method == 0) {
            $actor = User::where('role', 0)
                ->where('restaurant_id', $request->restaurant_id)
                ->where('username', $request->username)->first();
            if (!$actor) {
                return $this->returnError(404, trans('locale.UserNotFound'));
            }
            if ($actor) {
                if ($actor->question == $request->question && $actor->answer == $request->answer) {
                    $tokenResult = $actor->createToken('auth_token');
                    $token = $tokenResult->plainTextToken;
                    $tokenResult->accessToken->forceFill(['platform' => $request->header('User-Agent')])->save();
                    $data = [
                        'token' => $token,
                    ];
                    return $this->successResponse($data, trans('locale.successfully'), 200);
                }
            }
        } elseif ($request->method == 1) {
            $actor = User::where('role', 0)->where('restaurant_id', $request->restaurant_id)->where('email', $request->email)->first();
            if ($actor) {
                do {
                    $randomNumber = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    $exists = User::where('code', $randomNumber)->exists();
                } while ($exists);
                $actor->update([
                    'code' => $randomNumber,
                ]);
                SendEmailTolUser::dispatch(
                    $actor->email,
                    $actor->name,
                    $randomNumber
                );
                // Mail::to($actor->email)->send(new SendCodeMail($actor->name, $randomNumber));
                return $this->messageSuccessResponse('تم إرسال الرابط.', 200);

                // $response = Password::sendResetLink($request->only('email'));
                // if ($response == Password::RESET_LINK_SENT) {
                // return response()->json(['message' => 'رابط إعادة تعيين كلمة المرور تم إرساله إلى بريدك الإلكتروني.']);
                // }
                // return response()->json(['error' => 'حدث خطأ أثناء إرسال الرابط.'], 500);
            }
        }
        return response()->json(['status' => false, 'message' => trans('locale.error')], 400);
    }

    public function codeVerification(Request $request)
    {
        $actor = User::where('role', 0)->where('code', $request->code)->first();
        if ($actor) {
            $tokenResult = $actor->createToken('auth_token');
            $token = $tokenResult->plainTextToken;
            $tokenResult->accessToken->forceFill(['platform' => $request->header('User-Agent')])->save();
            $data = [
                'token' => $token,
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
                    'password' => $newPassword,
                    'code' => null,
                ]);
                return $this->messageSuccessResponse(trans('locale.successfully'), 200);
            }
            return $this->messageErrorResponse(trans('locale.passwordDoesNotMatch'), 400);
        }

        // if($request->method == 0)
        // {
        //     $actor = User::where('role',0)->where('code',$request->code)->where('username', $request->username)->first();
        //     if($actor)
        //     {
        //         if($request->repeat_password == $request->password)
        //         {
        //             $newPassword = Hash::make($request->password);

        //             $actor->update([
        //                 'password' => $newPassword,
        //                 'code' => null,
        //             ]);
        //             return $this->messageSuccessResponse(trans('locale.successfully'),200);
        //         }
        //         return $this->messageErrorResponse(trans('locale.passwordDoesNotMatch'),200);
        //     }
        // }
        // elseif($request->method == 1)
        // {
        //     $request->validate([
        //         'email' => 'required|email',
        //         'password' => 'required|confirmed|min:8',
        //         'token' => 'required',
        //     ]);

        //     $response = Password::reset(
        //         $request->only('email', 'password', 'password_confirmation', 'token'),
        //         function ($user, $password) {
        //             $user->password = bcrypt($password);
        //             $user->save();
        //         }
        //     );

        //     if ($response == Password::PASSWORD_RESET) {
        //         return response()->json(['message' => 'تم تحديث كلمة المرور بنجاح.']);
        //     }

        //     return response()->json(['error' => 'فشل في إعادة تعيين كلمة المرور.'], 500);
        // }
    }
}
