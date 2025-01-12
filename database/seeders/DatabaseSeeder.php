<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(SuperAdminTableSeeder::class);
        $this->call(DecorationTableSeeder::class);
        $this->call(DiamondPackageSeeder::class);
        $this->call(LevelsTableSeeder::class);
        $this->call(CountryTableSeeder::class);
        $this->call(TargetsTableSeeder::class);
        $this->call(VideoTargetsTableSeeder::class);
        $this->call(GlobalBoxSeeder::class);
    }
}
