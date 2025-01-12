<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('country')->insert([
            'countrycode' => 'EGY',
            'countryname' => 'EGPYPT',
            'code' => 'EG',
        ]);
    }
}
