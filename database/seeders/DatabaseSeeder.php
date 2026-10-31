<?php

namespace Database\Seeders;

use GazaDelivery\Foundation\Database\Seeders\AccessControlSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AccessControlSeeder::class);
    }
}
