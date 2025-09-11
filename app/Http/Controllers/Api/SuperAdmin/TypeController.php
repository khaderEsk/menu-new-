<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Type\IdRequest;
use App\Models\Admin;
use App\Models\Restaurant;
use App\Models\Type;
use Illuminate\Http\Request;
use Throwable;

class TypeController extends Controller
{
    // Show All Type
    public function showAll()
    {
        try{
            $types = Type::where('id','>',2)->get();
            $translatedRoles = $types->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => trans('type.' . $t->name),
                ];
            });
            return $this->successResponse($translatedRoles,trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add Type
     public function create(Request $request)
     {
         try{
            $type = Type::create([
                'name' => $request->name,
            ]);
            return $this->successResponse($type,trans('locale.created'),200);
         } catch(Throwable $th){
             $message = $th->getMessage();
             return $this->messageErrorResponse($message);
         }
     }

    public function delete(IdRequest $request)
    {
        try{
            $admin = Admin::whereTypeId($request->id)->orWhere('type_id',$request->id)->count();
            if($admin != 0)
                return $this->messageErrorResponse("you can't delete the typy");

            // Type::whereId($request->id)->delete();
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
