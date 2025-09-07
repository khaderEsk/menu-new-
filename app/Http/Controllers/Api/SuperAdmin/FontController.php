<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Font\AddRequest;
use App\Http\Requests\Font\IdRequest;
use App\Http\Requests\Font\updateRequest;
use App\Models\Font;
use Illuminate\Http\Request;
use Throwable;

class FontController extends Controller
{
    public function showAll(Request $request)
    {
        try{
            $fonts = Font::whereLocale($request->locale)->orWhere('locale', 'both')->get();
            return $this->successResponse($fonts,trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add Emoji
    public function create(AddRequest $request)
    {
        try{
            $font = Font::create($request->validated());
            return $this->successResponse($font,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function update(updateRequest $request)
    {
        try{
            Font::whereId($request->id)->update($request->validated());
            $font = Font::findOrFail($request->id);
            return $this->successResponse($font,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Delete Emoji
    public function delete(IdRequest $request)
    {
        try{
            Font::whereId($request->id)->delete();
            return $this->messageSuccessResponse(trans('locale.deleted'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Show Emoji By Id
    public function showById(IdRequest $request)
    {
        try{
            $font = Font::whereId($request->id)->get();
            return $this->successResponse($font,trans('locale.successfully'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
