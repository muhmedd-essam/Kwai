<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LevelsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i = 1; $i <= 99; $i++){
            DB::table('levels')->insert([
                'number' => $i,
    
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

    }
}
