<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddRequest;
use App\Http\Requests\User\IdRequest;
use App\Http\Requests\User\ShowAllRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Models\EmployeeTable;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Throwable;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    // Show All Admins
    public function showAll(ShowAllRequest $request)
    {
        try {
            $admin = auth()->user();
            $data = $request->validated();
            $dataAdmin =  $this->userService->all($admin->restaurant_id, $admin->id);
            if (\count($dataAdmin) == 0) {
                return $this->successResponse([], trans('locale.dontHaveEmployee'), 200);
            }


            $query = Admin::with('type');
            // dd($query);

            // Filter by role
            if ($request->has('role')) {
                $role = Role::where('name', $request->role)->first();
                if ($role) {
                    $query->role($request->role);
                } else {
                    $rolesTranslations = trans('roles');

                    $roleKey = array_search($data['role'], $rolesTranslations);
                    if (!$roleKey) {
                        return "the role is incorrect";
                    }
                    $role = Role::where('name', $roleKey)->first();
                    $query->role($role);
                }
            }

            // Filter search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('name', 'like', '%' . $search . '%');
            }
            // Filter Active
            if ($request->has('active')) {
                $query->where('is_active', $request->active);
            }
            // Filter By City
            if ($request->has('city_id')) {
                $query->where('city_id', $request->city_id);
            }

            if ($request->has('type_id')) {
                $query->where('type_id', $request->type_id);
            }

            if ($admin->hasRole(['admin'])) {
                $superAdmin = $query->where('id', '!=', $admin->id)->whereDoesntHave('roles', function ($query) {
                    $query->where('name', 'restaurantManager');
                })->whereRestaurantId($admin->restaurant_id)->paginate($request->input('per_page', 25));
                $data = AdminResource::collection($superAdmin);
                return $this->paginateSuccessResponse($data, trans('locale.foundSuccessfully'), 200);
            }
            $superAdmin = $query->where('id', '!=', $admin->id)->whereRestaurantId($admin->restaurant_id)->paginate($request->input('per_page', 25));
            $data = AdminResource::collection($superAdmin);
            return $this->paginateSuccessResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add User
    public function create(AddRequest $request)
    {
        try {
            $dataValid = $request->validated();
            $dataValid['role'] = 'موظف';
            $admin = auth()->user();
            // if($admin->hasRole(['admin']))
            // {
            //     $data =  Admin::whereRestaurantId($admin->restaurant_id)->role('admin')->get();
            //     if (\count($data) != 0)
            //         return $this->messageErrorResponse(trans('locale.theRestaurantHasAdmin'),400);
            // }
            // elseif($admin->hasRole(['restaurantManager']))
            // {
            //     $data = Admin::whereRestaurantId($admin->restaurant_id)->role('admin')->get();
            //     if (\count($data) != 0 )
            //     {
            //         if($request->role == 'Admin' || $request->role == 'أدمن')
            //             return $this->messageErrorResponse(trans('locale.theRestaurantHasAdmin'),400);
            //     }
            // }
            $restaurant_id = auth()->user()->restaurant_id;
            $user = $this->userService->create($dataValid, $restaurant_id);
            if ($user === "the role is incorrect")
                return $this->messageErrorResponse(trans('locale.theRoleIsIncorrect'), 400);
            $data = AdminResource::make($user);
            return $this->successResponse($data, trans('locale.created'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Update User
    public function update(UpdateRequest $request)
    {
        try {
            // $id = auth()->user()->id;
            $admin = auth()->user();
            $arrRole = Arr::only(
                $request->validated(),
                ['role', 'permission']
            );

            $arrAdmin = Arr::only(
                $request->validated(),
                ['id', 'name', 'password', 'user_name', 'mobile', 'type_id']
            );
            $user = $this->userService->update($admin, $arrAdmin, $arrRole, $admin->id);
            if ($user == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            $showUser = $this->userService->show($admin->restaurant_id, $arrAdmin, $admin->id);

            if ($showUser) {
                $showUser->tokens()->delete();
            }

            $data = AdminResource::make($showUser);
            return $this->successResponse($data, trans('locale.updated'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show User By Id
    public function showById(IdRequest $request)
    {
        try {
            // $id = auth()->user()->id;
            $admin = auth()->user();
            $user = $this->userService->show($admin->restaurant_id, $request->validated(), $admin->id);
            $data = AdminResource::make($user);
            return $this->successResponse($data, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete User
    public function delete(IdRequest $request)
    {
        try {
            $restaurant_id = auth()->user()->restaurant_id;
            $user = $this->userService->destroy($request->validated(), $restaurant_id);
            if ($user == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive User
    public function deactivate(IdRequest $request)
    {
        try {
            // $id = Auth()->user()->id;
            $admin = auth()->user();
            $user = $this->userService->show($admin->restaurant_id, $request->validated(), $admin->id);
            $item = $this->userService->activeOrDesactive($user);
            if ($item == 0) {
                return $this->messageErrorResponse(trans('locale.invalidItem'), 403);
            }
            return $this->messageSuccessResponse(trans('locale.successfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function detail2()
    {
        try {
            $admin = auth()->user();
            $dataAdmin =  $this->userService->all($admin->restaurant_id, $admin->id);
            if (\count($dataAdmin) == 0) {
                return $this->successResponse([], trans('locale.dontHaveEmployee'), 200);
            }

            $responseData = [];

            if ($admin->hasRole(['admin'])) {
                $superAdmin = Admin::where('id', '!=', $admin->id)->whereDoesntHave('roles', function ($query) {
                    $query->where('name', 'restaurantManager');
                })->whereRestaurantId($admin->restaurant_id)->get();

                foreach ($superAdmin as $employee) {
                    $responseTimes = [];
                    for ($i = 0; $i <= 30; $i++) {
                        $day = now()->subDays($i)->format('Y-m-d');

                        $totalSeconds = 0;
                        $num = EmployeeTable::whereAdminId($employee->id)->whereDate('created_at', $day)->get();
                        for ($j = 0; $j < count($num); $j++) {
                            $firstElement = $num->get($j);
                            $time = $firstElement->order_time;
                            list($hours, $minutes, $seconds) = explode(':', $time);
                            $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
                        }

                        $n = count($num) == 0 ? 1 : count($num);
                        $total = $totalSeconds / $n;
                        $hours = floor($total / 3600);
                        $minutes = floor(($total % 3600) / 60);
                        $seconds = $total % 60;
                        $avg = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                        $responseTimes[] = [
                            'date' => $day,
                            'number' => count($num),
                            'average_response_time' => $avg
                        ];
                    }

                    $responseData[] = [
                        'name' => $employee->name,
                        'user_name' => $employee->user_name,
                        'mobile' => $employee->mobile,
                        'response_times' => $responseTimes
                    ];
                }
                return $this->successResponse($responseData, trans('locale.foundSuccessfully'), 200);
            }
            $superAdmin = Admin::where('id', '!=', $admin->id)->whereRestaurantId($admin->restaurant_id)->get();
            foreach ($superAdmin as $employee) {
                $responseTimes = [];
                for ($i = 0; $i <= 30; $i++) {
                    $day = now()->subDays($i)->format('Y-m-d');

                    $totalSeconds = 0;
                    $num = EmployeeTable::whereAdminId($employee->id)->whereDate('created_at', $day)->get();
                    for ($j = 0; $j < count($num); $j++) {
                        $firstElement = $num->get($j);
                        $time = $firstElement->order_time;
                        list($hours, $minutes, $seconds) = explode(':', $time);
                        $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
                    }

                    $n = count($num) == 0 ? 1 : count($num);
                    $total = $totalSeconds / $n;
                    $hours = floor($total / 3600);
                    $minutes = floor(($total % 3600) / 60);
                    $seconds = $total % 60;
                    $avg = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                    $responseTimes[] = [
                        'date' => $day,
                        'number' => count($num),
                        'average_response_time' => $avg
                    ];
                }

                $responseData[] = [
                    'name' => $employee->name,
                    'user_name' => $employee->user_name,
                    'mobile' => $employee->mobile,
                    'response_times' => $responseTimes
                ];
            }
            return $this->successResponse($responseData, trans('locale.foundSuccessfully'), 200);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function detail()
    {
        try {
            $admin = auth()->user();
            $dataAdmin =  $this->userService->all($admin->restaurant_id, $admin->id);
            if (\count($dataAdmin) == 0) {
                return $this->successResponse([], trans('locale.dontHaveEmployee'), 200);
            }
            if (request()->has('startDate') && request()->has('endDate')) {
                //if($admin->hasRole(['admin']))
                //{
                if (request()->has('type_id')) {
                    $superAdmin = Admin::role('employee')->where('id', '!=', $admin->id)->whereDoesntHave('roles', function ($query) {
                        $query->where('name', 'restaurantManager');
                    })->whereRestaurantId($admin->restaurant_id)->where('type_id', request()->type_id)->get();
                } else {
                    $superAdmin = Admin::role('employee')->where('id', '!=', $admin->id)->whereDoesntHave('roles', function ($query) {
                        $query->where('name', 'restaurantManager');
                    })->whereRestaurantId($admin->restaurant_id)->where('type_id', '>', 2)->get();
                }
                foreach ($superAdmin as $employee) {
                    $responseTimes = [];
                    $date1 = \Carbon\Carbon::createFromFormat('Y-m-d', request()->startDate);
                    $date2 = \Carbon\Carbon::createFromFormat('Y-m-d', request()->endDate);
                    $diffInDays = $date1->diffInDays($date2);

                    for ($i = 0; $i <= $diffInDays; $i++) {
                        $day = $date1->format('Y-m-d');
                        $date1->addDay();

                        $totalSeconds = 0;
                        $num = EmployeeTable::whereAdminId($employee->id)->whereDate('created_at', $day)->get();
                        for ($j = 0; $j < count($num); $j++) {
                            $firstElement = $num->get($j);
                            $time = $firstElement->order_time;
                            list($hours, $minutes, $seconds) = explode(':', $time);
                            $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
                        }

                        $n = count($num) == 0 ? 1 : count($num);
                        $total = $totalSeconds / $n;
                        $hours = floor($total / 3600);
                        $minutes = floor(($total % 3600) / 60);
                        $seconds = $total % 60;
                        $avg = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                        $responseTimes[] = [
                            'date' => $day,
                            'number' => count($num),
                            'average_response_time' => $avg
                        ];
                    }

                    $responseData[] = [
                        'name' => $employee->name,
                        'user_name' => $employee->user_name,
                        'mobile' => $employee->mobile,
                        'response_times' => $responseTimes
                    ];
                }
                return $this->successResponse($responseData, trans('locale.foundSuccessfully'), 200);
                //}
                // $superAdmin = Admin::role('employee')->where('id','!=',$admin->id)->whereDoesntHave('roles', function($query) {
                //     $query->where('name', 'restaurantManager');
                // })->whereRestaurantId($admin->restaurant_id)->where('type_id',5)->orWhere('type_id',6)->get();
                // //$responseData = [];
                // foreach ($superAdmin as $employee) {
                //     $responseTimes = [];

                //     $date1 = \Carbon\Carbon::createFromFormat('Y-m-d', request()->startDate);
                //     $date2 = \Carbon\Carbon::createFromFormat('Y-m-d', request()->endDate);
                //     $diffInDays = $date1->diffInDays($date2);

                //     for ($i = 0; $i <= $diffInDays; $i++) {
                //         $day = $date1->format('Y-m-d');
                //         $date1->addDay();

                //         $totalSeconds = 0;
                //         $num = EmployeeTable::whereAdminId($employee->id)->whereDate('created_at', $day)->get();

                //         for($j=0;$j< count($num);$j++)
                //         {
                //             $firstElement = $num->get($j);
                //             $time = $firstElement->order_time;
                //             list($hours, $minutes, $seconds) = explode(':', $time);
                //             $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
                //         }

                //         $n = count($num) == 0 ? 1:count($num);
                //         $total = $totalSeconds/$n;
                //         $hours = floor($total / 3600);
                //         $minutes = floor(($total % 3600) / 60);
                //         $seconds = $total % 60;
                //         $avg = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                //         $responseTimes[] = [
                //             'date' => $day,
                //             'number' => count($num),
                //             'average_response_time' => $avg
                //         ];
                //     }

                //     $responseData[] = [
                //         'name' => $employee->name,
                //         'user_name' => $employee->user_name,
                //         'mobile' => $employee->mobile,
                //         'response_times' => $responseTimes
                //     ];
                // }
            } else {
                $responseData = [];
                if ($admin->hasRole(['admin'])) {
                    if (request()->has('type_id')) {
                        $superAdmin = Admin::where('id', '!=', $admin->id)->whereDoesntHave('roles', function ($query) {
                            $query->where('name', 'restaurantManager');
                        })->whereRestaurantId($admin->restaurant_id)->where('type_id', request()->type_id)->get();
                    } else {
                        $superAdmin = Admin::where('id', '!=', $admin->id)->whereDoesntHave('roles', function ($query) {
                            $query->where('name', 'restaurantManager');
                        })->whereRestaurantId($admin->restaurant_id)->where('type_id', '>', 2)->get();
                    }
                    foreach ($superAdmin as $employee) {
                        $responseTimes = [];
                        for ($i = 0; $i <= 30; $i++) {
                            $day = now()->subDays($i)->format('Y-m-d');

                            $totalSeconds = 0;
                            $num = EmployeeTable::whereAdminId($employee->id)->whereDate('created_at', $day)->get();
                            for ($j = 0; $j < count($num); $j++) {
                                $firstElement = $num->get($j);
                                $time = $firstElement->order_time;
                                list($hours, $minutes, $seconds) = explode(':', $time);
                                $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
                            }

                            $n = count($num) == 0 ? 1 : count($num);
                            $total = $totalSeconds / $n;
                            $hours = floor($total / 3600);
                            $minutes = floor(($total % 3600) / 60);
                            $seconds = $total % 60;
                            $avg = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                            $responseTimes[] = [
                                'date' => $day,
                                'number' => count($num),
                                'average_response_time' => $avg
                            ];
                        }

                        $responseData[] = [
                            'name' => $employee->name,
                            'user_name' => $employee->user_name,
                            'mobile' => $employee->mobile,
                            'response_times' => $responseTimes
                        ];
                    }
                    return $this->successResponse($responseData, trans('locale.foundSuccessfully'), 200);
                }

                if (request()->has('type_id')) {
                    $superAdmin = Admin::where('id', '!=', $admin->id)->whereRestaurantId($admin->restaurant_id)->where('type_id', request()->type_id)->get();
                } else {
                    $superAdmin = Admin::where('id', '!=', $admin->id)->whereRestaurantId($admin->restaurant_id)->where('type_id', '>', 2)->get();
                }
                foreach ($superAdmin as $employee) {
                    $responseTimes = [];
                    for ($i = 0; $i <= 30; $i++) {
                        $day = now()->subDays($i)->format('Y-m-d');

                        $totalSeconds = 0;
                        $num = EmployeeTable::whereAdminId($employee->id)->whereDate('created_at', $day)->get();
                        for ($j = 0; $j < count($num); $j++) {
                            $firstElement = $num->get($j);
                            $time = $firstElement->order_time;
                            list($hours, $minutes, $seconds) = explode(':', $time);
                            $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
                        }

                        $n = count($num) == 0 ? 1 : count($num);
                        $total = $totalSeconds / $n;
                        $hours = floor($total / 3600);
                        $minutes = floor(($total % 3600) / 60);
                        $seconds = $total % 60;
                        $avg = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

                        $responseTimes[] = [
                            'date' => $day,
                            'number' => count($num),
                            'average_response_time' => $avg
                        ];
                    }

                    $responseData[] = [
                        'name' => $employee->name,
                        'user_name' => $employee->user_name,
                        'mobile' => $employee->mobile,
                        'response_times' => $responseTimes
                    ];
                }
                return $this->successResponse($responseData, trans('locale.foundSuccessfully'), 200);
            }
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function showWaiters()
    {
        $admin = auth()->user();
        $waiters = Admin::whereRestaurantId($admin->restaurant_id)->role('employee')->whereTypeId(5)->get();
        return $this->successResponse($waiters, trans('locale.foundSuccessfully'), 200);
    }
}
