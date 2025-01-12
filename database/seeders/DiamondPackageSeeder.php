<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiamondPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('diamond_packages')->insert([
            'quantity' => 5000,
            'price' => 2.5,
            'cover' => 'images/rooms/room-covers/WWTEORDHBpB6WXCqC9E1hU1UjH7aqfb79Gj8wvGh.png',
        ]);
    }
}
