<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Emoji\AddRequest;
use App\Http\Requests\Emoji\IdRequest;
use App\Http\Requests\Emoji\ShowAllRequest;
use App\Http\Requests\Emoji\UpdateRequest;
use App\Http\Resources\DataEntryResource;
use App\Http\Resources\EmojiResource;
use App\Models\Emoji;
use App\Services\EmojiService;
use Illuminate\Support\Arr;
use Throwable;

class EmojiController extends Controller
{
    public function __construct(private EmojiService $emojiService)
    {
    }

    // Show All Emoji
    public function showAll(ShowAllRequest $request)
    {
        try{
            $data =  $this->emojiService->all();
            if (\count($data) == 0) {
                return $this->successResponse([],trans('locale.dontHaveEmoji'),200);
            }

            $data = $request->validated();
            $where = [];

            // Filter By Search
            if(\array_key_exists('search',$data))
                $where = \array_merge($where,[['name','like','%'.$data['search'].'%']]);
            // Filter Active
            if(\array_key_exists('active',$data))
                $where = \array_merge($where,['is_active'=> $data['active']]);

            if(\array_key_exists('search',$data) || \array_key_exists('active',$data))
            {
                $emoji =  $this->emojiService->search($where,$request->input('per_page', 25));
                $data = EmojiResource::collection($emoji);
                return $this->paginateSuccessResponse($data,trans('locale.emojiFound'),200);
            }

            $emoji = $this->emojiService->paginate($request->input('per_page', 25));
            $data = EmojiResource::collection($emoji);
            return $this->paginateSuccessResponse($data,trans('locale.emojiFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Add Emoji
    public function create(AddRequest $request)
    {
        try{
            $id = auth()->user()->id;
            $emoji = $this->emojiService->create($id,$request->validated());
            if ($request->hasFile('bad_image')) {
                $extension = $request->file('bad_image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $emoji->addMediaFromRequest('bad_image')->usingFileName($randomFileName)->usingName($emoji->name)->toMediaCollection('emoji_bad');
            }
            if ($request->hasFile('good_image')) {
                $extension = $request->file('good_image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $emoji->addMediaFromRequest('good_image')->usingFileName($randomFileName)->usingName($emoji->name)->toMediaCollection('emoji_good');
            }
            if ($request->hasFile('perfect_image')) {
                $extension = $request->file('perfect_image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $emoji->addMediaFromRequest('perfect_image')->usingFileName($randomFileName)->usingName($emoji->name)->toMediaCollection('emoji_perfect');
            }
            $data = EmojiResource::make($emoji);
            return $this->successResponse($data,trans('locale.created'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    public function update(UpdateRequest $request)
    {
        try{
            $id = auth()->user()->id;
            $data = $request->validated();

            $arrEmoji = Arr::only($data,
            ['id','name']);

            $emoji = $this->emojiService->update($id,$arrEmoji);
            $first_emoji = Emoji::whereId($data['id'])->first();
            if ($request->hasFile('bad_image')) {
                $first_emoji->clearMediaCollection('emoji_bad');
                $extension = $request->file('bad_image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $first_emoji->addMediaFromRequest('bad_image')->usingFileName($randomFileName)->toMediaCollection('emoji_bad');
            }
            if ($request->hasFile('good_image')) {
                $first_emoji->clearMediaCollection('emoji_good');
                $extension = $request->file('good_image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $first_emoji->addMediaFromRequest('good_image')->usingFileName($randomFileName)->toMediaCollection('emoji_good');
            }
            if ($request->hasFile('perfect_image')) {
                $first_emoji->clearMediaCollection('emoji_perfect');
                $extension = $request->file('perfect_image')->getClientOriginalExtension();
                $randomFileName = str()->random(10) . '.' . $extension;
                $first_emoji->addMediaFromRequest('perfect_image')->usingFileName($randomFileName)->toMediaCollection('emoji_perfect');
            }
            if($emoji == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
            $emoji = $this->emojiService->show($request->id);
            $data = EmojiResource::make($emoji);
            return $this->successResponse($data,trans('locale.updated'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }

    // Active Or DisActive Emoji
    public function deactivate(IdRequest $request)
    {
        try{
            $admin = auth()->user()->id;
            $emoji = $this->emojiService->show($request->id);
            $item = $this->emojiService->activeOrDesactive($emoji,$admin);
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

    // Delete Emoji
    public function delete(IdRequest $request)
    {
        try{
            $admin = auth()->user()->id;
            $done = Emoji::whereId($request->id)->first();
            $done->clearMediaCollection('emoji_bad');
            $done->clearMediaCollection('emoji_good');
            $done->clearMediaCollection('emoji_perfect');
            $emoji = $this->emojiService->destroy($request->id,$admin);
            if($emoji == -10)
            {
                return $this->messageErrorResponse(trans('locale.youCantDeleteThisEmojiBecauseItHasRestaurant'),403);
            }
            if($emoji == 0)
            {
                return $this->messageErrorResponse(trans('locale.invalidItem'),403);
            }
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
            $emoji = $this->emojiService->show($request->id);
            $data = EmojiResource::make($emoji);
            return $this->successResponse($data,trans('locale.emojiFound'),200);
        } catch(Throwable $th){
            $message = $th->getMessage();
            return $this->messageErrorResponse($message);
        }
    }
}
