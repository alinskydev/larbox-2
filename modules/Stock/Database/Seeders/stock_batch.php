<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('stock_batch')->insert([
            [
                'company_id' => 1,
                'stock_id' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'company_id' => 1,
                'stock_id' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
