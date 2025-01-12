<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DecorationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('decorations')->insert([
            'name' => 'frame-1',
            'type' => 'frame',
            'cover' => '/images/decorations/frame-1.png',
            'svga' => '/images/decorations/frame-1.svga',
            'is_free' => 0,
            'price' => 150,
            'currency_type' => 'diamond',
            'valid_days' => 200,
        ]);

        DB::table('decorations')->insert([
            'name' => 'entry-1',
            'type' => 'entry',
            'cover' => '/images/decorations/entry-1-cover.png',
            'svga' => '/images/decorations/entry-1.svga',
            'is_free' => 0,
            'price' => 300,
            'currency_type' => 'diamond',
            'valid_days' => 200,
        ]);

        DB::table('decorations')->insert([
            'name' => 'room_background-1',
            'type' => 'room_background',
            'cover' => '/images/decorations/room_background-1-cover.png',
            'is_free' => 0,
            'price' => 500,
            'currency_type' => 'diamond',
            'valid_days' => 200,
        ]);

        DB::table('gifts')->insert([
            'name' => 'rocket',
            'cover' => 'images/gifts/rocket.png',
            'svga' => 'svgas/gifts/rocket.svga',
            'price' => 10000,
            'type' => 'main',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
