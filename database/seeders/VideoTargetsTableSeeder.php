<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VideoTargetsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('hosting_agency_video_targets')->insert([
            'target_no' => 1,
            'diamonds_required' => 0,
            'hours_required' => 0,
            'salary' => 0,
            'owner_salary' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
