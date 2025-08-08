<?php

namespace Database\Seeders;

use App\Models\Emoji;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmojiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $Emoji = Emoji::factory()->count(5)->create();
        foreach (Emoji::all() as $Emoji) {
            $url = 'https://picsum.photos/200/300';
            $Emoji->addMediaFromUrl($url)->toMediaCollection("emoji_bad");
            $Emoji->addMediaFromUrl($url)->toMediaCollection("emoji_good");
            $Emoji->addMediaFromUrl($url)->toMediaCollection("emoji_perfect");
        }

    }
}
