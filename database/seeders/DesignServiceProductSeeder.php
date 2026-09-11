<?php

namespace Database\Seeders;

use App\Support\DesignServiceProduct;
use Illuminate\Database\Seeder;

class DesignServiceProductSeeder extends Seeder
{
    public function run(): void
    {
        DesignServiceProduct::resolve();
    }
}
