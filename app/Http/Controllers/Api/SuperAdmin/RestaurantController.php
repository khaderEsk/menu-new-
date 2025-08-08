<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Restaurant\AddAdminRequest;
use App\Http\Requests\Restaurant\AddRequest;
use App\Http\Requests\Restaurant\DeleteRequest;
use App\Http\Requests\Restaurant\IdRequest;
use App\Http\Requests\Restaurant\ShowRequest;
use App\Http\Requests\Restaurant\PageRequest;
use App\Http\Requests\Restaurant\UpdateRequest;
use App\Http\Resources\RestaurantResource;
use App\Http\Resources\ShowContractsResource;
use App\Models\Admin;
use App\Models\CitySuperAdmin;
use App\Models\IpQr;
use App\Models\Restaurant;
use App\Models\SuperAdmin;
use App\Services\RestaurantService;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use SimpleSoftwareIO\QrCode\Facades\QrCode as FacadesQrCode;
use Throwable;

class RestaurantController extends Controller
{
    public function __construct(private RestaurantService $restaurantService, private QrService $qrService)
    {
    }

    // Show All Restaurant Pagination
    public function showAll(ShowRequest $request)
    {
        try{
            $data =  $this->restaurantService->all();
            if (\count($data) == 0)
                return $this->successResponse([],trans('locale.dontHaveRestaurants'),200);

            $data = $request->validated();
            $where = [];
            $admin = auth()->user();

            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    // Filter Search
                    if(\array_key_exists('search',$data))
                    {
                        $restaurant = Restaurant::whereCityId($admin->city_id)->with('admins')->whereTranslationLike('name',"%".$data['search']."%")->latest()->paginate($request->input('per_page', 25));
                        // $restaurant = Restaurant::whereCityId($admin->city_id)->with('admins')->whereTranslationLike('name',"%$data%")->paginate($request->input('per_page', 25));
                        $restaurants = RestaurantResource::collection($restaurant);
                        return $this->paginateSuccessResponse($restaurants,trans('locale.restaurantFound'),200);
                    }
                    $where = \array_merge($where,['city_id'=> $admin->city_id]);

                    $restaurant =  $this->restaurantService->filter($where,$request->input('per_page', 25));
                    $data = RestaurantResource::collection($restaurant);
                    return $this->paginateSuccessResponse($data,trans('locale.restaurantFound'),200);

                }
            }

            $query = Restaurant::query();

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereTranslationLike('name', "%$search%");
            }

            if ($request->has('city_id')) {
                $query->where('city_id', $request->city_id);
            }

            if ($request->has('restaurant_manager_id')) {
                $query->where('admin_id', $request->restaurant_manager_id);
            }


            $restaurant = $query->latest()->paginate($request->input('per_page', 25));
            $data = RestaurantResource::collection($restaurant);
            return $this->paginateSuccessResponse($data,trans('locale.restaurantFound'),200);

            // // Filter By Search
            // if(\array_key_exists('search',$data))
            // {
            //     $data = $request->validated();
            //     $restaurant =  $this->restaurantService->search($data['search'],$request->input('per_page', 25));
            //     $restaurants = RestaurantResource::collection($restaurant);
            //     return $this->paginateSuccessResponse($restaurants,"Restaurant Found Successfully",200);
            // }

            // // Filter city_id
            // if(\array_key_exists('city_id',$data))
            //     $where = \array_merge($where,['city_id'=> $data['city_id']]);

            // if(\array_key_exists('restaurant_manager_id',$data))
            //     $where = \array_merge($where,['admin_id'=> $data['restaurant_manager_id']]);


            // if(\array_key_exists('city_id',$data) || \array_key_exists('city_super_admin_id',$data) || \array_key_exists('restaurant_manager_id',$data))
            // {
            //     $restaurant =  $this->restaurantService->filter($where,$request->input('per_page', 25));
            //     $data = RestaurantResource::collection($restaurant);
            //     return $this->paginateSuccessResponse($data,"Restaurant Found Successfully",200);
            // }

            // $restaurant = $this->restaurantService->paginate($request->input('per_page', 25));
            // $data = RestaurantResource::collection($restaurant);
            // return $this->paginateSuccessResponse($data,"Restaurant Found Successfully",200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add Restaurant Function
    public function create(AddRequest $request)
    {
        try{
            $data = $request->validated();
            $admin = auth()->user();

            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    if($data['city_id'] != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantAddRestaurantOtherCity'));
                }
            }

            $arrRestaurant = Arr::only($request->validated(),
            ['admin_id','name_en','name_ar','name_url','facebook_url','instagram_url','whatsapp_phone','note_en','note_ar','message_bad', 'message_good','message_perfect','color','background_color','f_color_category','f_color_sub','f_color_item',"f_color_rating", 'font_id_en', 'font_id_ar','consumer_spending','local_administration','reconstruction','is_advertisement','is_news','is_rate','rate_format','is_order','is_active','is_table','is_taxes','city_id','emoji_id','menu_template_id','cover','logo','is_welcome_massege','latitude','longitude','is_sub_move','accepted_by_waiter','is_takeout','is_delivery','show_more_than_one_price','image_or_write','logo_shape','message_in_home_page','fav_lang','font_size_welcome','font_type_welcome','font_size_category','font_type_category_en','font_type_category_ar','font_size_item','font_type_item_en','font_type_item_ar','font_bold_category','font_bold_item','empty_image','home_opacity','price_km','price_type','share_item_whatsapp']);

            if($request->has('image_or_color'))
                $arrRestaurant['image_or_color'] = $request->image_or_color;

            if($request->has('rate_opacity'))
                $arrRestaurant['rate_opacity'] = $request->rate_opacity;

            if($request->has('sub_opacity'))
                $arrRestaurant['sub_opacity'] = $request->sub_opacity;

            if(!is_null($request->welcome))
                $arrRestaurant['welcome'] = $request->welcome;
            if(!is_null($request->question))
                $arrRestaurant['question'] = $request->question;
            if(!is_null($request->if_answer_no))
                $arrRestaurant['if_answer_no'] = $request->if_answer_no;

            $restaurant = $this->restaurantService->create($admin->id,$arrRestaurant);

            if($request->hasFile('background_image_home_page'))
            {
                $extension = $request->file('background_image_home_page')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('background_image_home_page')->usingFileName($randomFileName)->toMediaCollection('background_image_home_page');
            }
            if($request->hasFile('background_image_category'))
            {
                $extension = $request->file('background_image_category')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('background_image_category')->usingFileName($randomFileName)->toMediaCollection('background_image_category');
            }
            if($request->hasFile('background_image_sub'))
            {
                $extension = $request->file('background_image_sub')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('background_image_sub')->usingFileName($randomFileName)->toMediaCollection('background_image_sub');
            }
            if($request->hasFile('background_image_item'))
            {
                $extension = $request->file('background_image_item')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('background_image_item')->usingFileName($randomFileName)->toMediaCollection('background_image_item');
            }

            if ($request->hasFile('cover')) {
                $extension = $request->file('cover')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('cover')->usingFileName($randomFileName)->toMediaCollection('cover');
            }
            if ($request->hasFile('logo')) {
                $extension = $request->file('logo')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('logo')->usingFileName($randomFileName)->toMediaCollection('logo');
            }
            if ($request->hasFile('logo_home_page')) {
                $extension = $request->file('logo_home_page')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('logo_home_page')->usingFileName($randomFileName)->usingName($restaurant->name)->toMediaCollection('logo_home_page');
            }
            $arrRestaurant['restaurant_url'] = "https://menu.le.sy/".$arrRestaurant['name_url'];
            $arrRestaurant['restaurant_id'] = $restaurant['id'];
            $qr = $this->qrService->create($arrRestaurant);
            $rest = Restaurant::whereId($restaurant->id)->first();
            // $arrAdmin = Arr::only($request->validated(),
            // ['name_admin','user_name','password','mobile','fcm_token']);
            // $restaurant = $this->restaurantService->createAdmin($restaurant->id,$arrAdmin);
            // /---------------------------------------------------------------------------/
            if($request->is_takeout == 1)
            {
                // إنشاء QR code
                $appUrl = env('APP_URL');
                $restaurantUrlTakeOut = $appUrl."/qr_takeout/".$restaurant->id;

                $qrContent = "$restaurantUrlTakeOut";

                // توليد QR Code من النص المدمج
                $qrCode = FacadesQrCode::format('png')->size(200)->generate($qrContent);

                // تحديد مسار للحفظ في التخزين
                $qrCodePath = 'public/qr_takeout/' . $restaurant->id . '.png';

                // حفظ الصورة في التخزين
                Storage::put($qrCodePath, $qrCode);

                // تحديث مسار الصورة في قاعدة البيانات
                $rest->qr_takeout = $qrCodePath;
                $rest->save();
            }
            // /---------------------------------------------------------------------------/
            $data = RestaurantResource::make($rest);
            return $this->successResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add Admin to Restaurant Function
    public function createAdmin(AddAdminRequest $request)
    {
        try{
            $dataValid = $request->validated();
            $data =  Admin::whereRestaurantId($dataValid['restaurant_id'])->role('admin')->get();
            // if (\count($data) != 0)
            //     return $this->messageErrorResponse(trans('locale.theRestaurantHasAdmin'),400);

            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    $restaurant = $this->restaurantService->show($dataValid['restaurant_id']);
                    if($restaurant->city_id != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantAddAdminToRestaurantOtherCity'));
                }
            }
            $arrAdmin = Arr::only($request->validated(),
            ['restaurant_id','name','user_name','password','mobile','fcm_token','type']);
            $adminRestaurant = $this->restaurantService->createAdmin($arrAdmin);

            return $this->successResponse($adminRestaurant,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive Restaurant
     public function deactivate(IdRequest $request)
    {
        try{
            $dataValid = $request->validated();
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    $restaurant = $this->restaurantService->show($dataValid['id']);
                    if($restaurant->city_id != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantDeactivateRestaurantOtherCity'));

                }
            }
            $restaurant = $this->restaurantService->show($request->id);
            $item = $this->restaurantService->activeOrDesactive($restaurant,$admin->id);
            if($item == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

     // Update Restaurant
    public function update(UpdateRequest $request)
    {
        try{
            $data = $request->validated();
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    $restaurant = $this->restaurantService->show($data['id']);
                    if($restaurant->city_id != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantUpdateRestaurantOtherCity'));

                }
            }
            $id = auth()->user()->id;
            $data['super_admin_id'] = $id;
            // $restaurant = $this->restaurantService->update($id,$data);

            $arrRestaurant = Arr::only($request->validated(),
            ['admin_id','id','name_url','facebook_url','instagram_url','whatsapp_phone','message_bad', 'message_good','message_perfect','color','background_color','f_color_category','f_color_sub','f_color_item',"f_color_rating", 'font_id_en', 'font_id_ar','consumer_spending','local_administration','reconstruction','is_advertisement','is_news','is_rate','rate_format','is_order','is_active','is_table','is_taxes','city_id','emoji_id','menu_template_id','is_welcome_massege','latitude','longitude','is_sub_move','accepted_by_waiter','is_takeout','is_delivery','show_more_than_one_price','image_or_write','exchange_rate','logo_shape','message_in_home_page','fav_lang','font_size_welcome','font_type_welcome','font_size_category','font_type_category_en','font_type_category_ar','font_size_item','font_type_item_en','font_type_item_ar','font_bold_category','font_bold_item','empty_image','home_opacity','price_km','price_type','share_item_whatsapp']);


            if($request->has('image_or_color'))
                $arrRestaurant['image_or_color'] = $request->image_or_color;

            if($request->has('rate_opacity'))
                $arrRestaurant['rate_opacity'] = $request->rate_opacity;

            if($request->has('sub_opacity'))
                $arrRestaurant['sub_opacity'] = $request->sub_opacity;
            if(!is_null($request->welcome))
                $arrRestaurant['welcome'] = $request->welcome;
            if(!is_null($request->question))
                $arrRestaurant['question'] = $request->question;
            if(!is_null($request->if_answer_no))
                $arrRestaurant['if_answer_no'] = $request->if_answer_no;

            $arrRestaurantTranslation = Arr::only($request->validated(),
            ['id','name_en','name_ar','note_en','note_ar']);

            $restaurant_update = $this->restaurantService->update($id,$arrRestaurant,$arrRestaurantTranslation);
            $restaurant = Restaurant::whereId($data['id'])->first();

            if($request->hasFile('background_image_home_page'))
            {
                $restaurant->clearMediaCollection('background_image_home_page');
                $extension = $request->file('background_image_home_page')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('background_image_home_page')->usingFileName($randomFileName)->toMediaCollection('background_image_home_page');
            }
            if($request->hasFile('background_image_category'))
            {
                $restaurant->clearMediaCollection('background_image_category');
                $extension = $request->file('background_image_category')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('background_image_category')->usingFileName($randomFileName)->toMediaCollection('background_image_category');
            }
            if($request->hasFile('background_image_sub'))
            {
                $restaurant->clearMediaCollection('background_image_sub');
                $extension = $request->file('background_image_sub')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('background_image_sub')->usingFileName($randomFileName)->toMediaCollection('background_image_sub');
            }
            if($request->hasFile('background_image_item'))
            {
                $restaurant->clearMediaCollection('background_image_item');
                $extension = $request->file('background_image_item')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('background_image_item')->usingFileName($randomFileName)->toMediaCollection('background_image_item');
            }

            if ($request->hasFile('cover')) {
                $restaurant->clearMediaCollection('cover');
                $extension = $request->file('cover')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('cover')->usingFileName($randomFileName)->usingName($restaurant->name)->toMediaCollection('cover');
            }
            if ($request->hasFile('logo')) {
                $restaurant->clearMediaCollection('logo');
                $extension = $request->file('logo')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('logo')->usingFileName($randomFileName)->usingName($restaurant->name)->toMediaCollection('logo');
            }
            if ($request->hasFile('logo_home_page')) {
                $restaurant->clearMediaCollection('logo_home_page');
                $extension = $request->file('logo_home_page')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $restaurant->addMediaFromRequest('logo_home_page')->usingFileName($randomFileName)->usingName($restaurant->name)->toMediaCollection('logo_home_page');
            }

            if($restaurant_update == 0)
            {
                return $this->messageErrorResponse("Invalid Item",403);
            }
            if($restaurant->is_takeout != $request->is_takeout && $request->is_takeout == 1)
            {
                if(!is_null($restaurant->qr_takeout))
                  Storage::delete($restaurant->qr_takeout);
                // إنشاء QR code
                $appUrl = env('APP_URL');
                $restaurantUrlTakeOut = $appUrl."/qr_takeout/".$restaurant->id;

                $qrContent = "$restaurantUrlTakeOut";

                // توليد QR Code من النص المدمج
                $qrCode = FacadesQrCode::format('png')->size(200)->generate($qrContent);

                // تحديد مسار للحفظ في التخزين
                $qrCodePath = 'public/qr_takeout/' . $restaurant->id . '.png';

                // حفظ الصورة في التخزين
                Storage::put($qrCodePath, $qrCode);

                // تحديث مسار الصورة في قاعدة البيانات
                $restaurant->qr_takeout = $qrCodePath;
                $restaurant->save();
            }
            $ip_qr = IpQr::whereRestaurantId($restaurant->id)->first();
            if(!is_null($ip_qr))
            {
                $data_qr = Arr::only($request->validated(),
                ['facebook_url','instagram_url','whatsapp_phone']);
                $data_qr['name'] = $arrRestaurant['name_url'];
                $data_qr['restaurant_url'] = env('APP_URL_FRONT').'/'.$arrRestaurant['name_url'];
                $qr = $this->qrService->update($ip_qr->id, $ip_qr->qr_code, $data_qr);
            }


            $admin = Admin::role('admin')->where('restaurant_id', $restaurant->id)->first();
            if(!is_null($admin))
            {
                $permissions = ['category.index','category.add','category.update','category.active','category.delete','reorder','item.index','item.add','item.update','item.active','item.delete','update_restaurant_admin','order.index','order.add','order.update','order.delete','user.index','user.add','user.update','user.delete','user.active','advertisement.index','advertisement.add','advertisement.update','advertisement.delete','news.index','news.add','news.update','news.delete','rate.index','excel','notifications.index','table.index','table.add','table.update','table.delete','service.index','service.add','service.update','service.delete','delivery.index','delivery.add','delivery.update','delivery.active','delivery.delete','coupon.index','coupon.add','coupon.update','coupon.delete','coupon.active'];
                $admin->syncPermissions($permissions);
            }

            $admins = Admin::withoutRole('restaurantManager')->where('restaurant_id', $restaurant->id)->get();
            // $admins = Admin::whereDoesntHave('roles', function ($query) {
            //     $query->where('name' ,'restaurantManager');
            // })->where('restaurant_id', $restaurant->id)->get();
            for($i=0;$i< count($admins);$i++)
            {
                $firstElement = $admins->get($i);
                if ($firstElement) {
                    $query = $firstElement->permissions();
                    if ($restaurant->is_advertisement == 0) {
                        $search = 'advertisement';
                        $query->where('name','not like', "%$search%");
                    }

                    if ($restaurant->is_rate == 0) {
                        $search = 'rate';
                        $query->where('name','not like', "%$search%");
                    }
                    if ($restaurant->is_table == 0) {
                        $search = 'table';
                        $query->where('name','not like', "%$search%");
                    }
                    if ($restaurant->is_order == 0) {
                        $search = 'order';
                        $query->where('name','not like', "%$search%");
                    }
                    if ($restaurant->is_news == 0) {
                        $search = 'news';
                        $query->where('name','not like', "%$search%");
                    }
                    $permissions = $query->pluck('name');
                    $firstElement->syncPermissions($permissions);
                    //
                }
            }
            $admin = Admin::whereRestaurantId($restaurant->id)->get();
            for($i=0;$i< count($admin);$i++)
            {
                $firstElement = $admin->get($i);
                if ($firstElement) {
                    $firstElement->tokens()->delete();
                }
            }

            $data = RestaurantResource::make($restaurant);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }


    // Delete Restaurant
    public function delete(IdRequest $request)
    {
        try{
            $data = $request->validated();
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    $restaurant = $this->restaurantService->show($data['id']);
                    if($restaurant->city_id != $admin->city_id)
                    return $this->messageErrorResponse(trans('locale.youCantDeletedRestaurantOtherCity'));
                }
            }
            $done = Restaurant::whereId($request->id)->first();
            if($done->is_active == 1)
            {
                return $this->messageErrorResponse(trans('locale.youCantDeleteThisRestaurant'));
            }
            $done->clearMediaCollection('cover');
            $done->clearMediaCollection('logo');
            $restaurant = $this->restaurantService->destroy($request->id,$admin->id);
            if($restaurant == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show restaurant By Id
    public function showById(IdRequest $request)
    {
        try{
            $data = $request->validated();
            $admin = auth()->user();
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    $restaurant = $this->restaurantService->show($data['id']);
                    if($restaurant->city_id != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantShowRestaurantOtherCity'));
                }
            }
            $restaurant = $this->restaurantService->show($request->id);
            $data = RestaurantResource::make($restaurant);
            return $this->successResponse($data,trans('locale.restaurantFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // update super admin restaurant id
    public function restaurantId(IdRequest $request)
    {
        try{
            $data = $request->validated();
            $admin = auth()->user();
            $restaurant = $this->restaurantService->show($data['id']);
            if($admin->hasAnyRole(['citySuperAdmin', 'dataEntry']))
            {
                if($admin->city_id != null)
                {
                    if($restaurant->city_id != $admin->city_id)
                        return $this->messageErrorResponse(trans('locale.youCantShowRestaurantOtherCity'));
                }
            }
            $super = $this->restaurantService->updateRestaurantId($request->id,$admin->id);
            if($super == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $menuId = [
                "menu_template_id" => $restaurant->menu_template_id,
            ];
            return $this->successResponse($menuId,trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show Contracts Order By Date
    public function showContracts(PageRequest $request)
    {
        $restaurants = Restaurant::orderBy('end_date')->paginate($request->input('per_page', 25));
        if (\count($restaurants) == 0) {
            return response()->json(['status' => false,'data' => [],'message' => "Dont Have restaurant"],402);
        }
        ShowContractsResource::collection($restaurants);
        return response()->json(['status' => true,'data' => $restaurants,'message' => "restaurant Found Successfully"],200);
    }
}
