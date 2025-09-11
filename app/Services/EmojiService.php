<?php

namespace App\Services;

use App\Models\Emoji;
use App\Models\Restaurant;

class EmojiService
{
    // to show all emoji active
    public function all()
    {
        $Emojis = Emoji::latest()->get();
        return $Emojis;
    }

    // to show paginate emoji active
    public function paginate($num)
    {
        $Emojis = Emoji::latest()->paginate($num);
        return $Emojis;
    }

    // to create emoji
    public function create($id,$data)
    {
        $data['super_admin_id'] = $id;
        $Emoji = Emoji::create($data);
        return $Emoji;
    }

    // to update  emoji
    public function update($id,$data)
    {
        $Emoji = Emoji::whereId($data['id'])->update($data);
        return $Emoji;
    }

    // to show a Emoji
    public function show(string $id)
    {
        $Emoji = Emoji::findOrFail($id);
        return $Emoji;
    }

    // to delete a Emoji
    public function destroy(string $id,$admin)
    {
        $restaurant = count(Restaurant::where('emoji_id',$id)->get());
        if($restaurant != 0)
        {
            return -10;
        }
        return Emoji::whereId($id)->forceDelete();
    }

    public function activeOrDesactive($data,$admin)
    {
        if($data['is_active'] == 1)
        {
            $Emoji = Emoji::whereId($data['id'])->update([
                'is_active' => 0,
            ]);
        }
        else
        {
             $Emoji = Emoji::whereId($data['id'])->update([
                'is_active' => 1,
            ]);
        }
        return $Emoji;
    }

    public function search($where,$num)
    {
        $Emojis = Emoji::where($where)->latest()->paginate($num);
        return $Emojis;
    }
}
