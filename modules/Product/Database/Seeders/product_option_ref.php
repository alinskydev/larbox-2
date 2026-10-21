<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('product_option_ref')->insert([
            [
                'product_company_id' => 1,
                'product_id' => 1,
                'option_id' => 1,
            ],
            [
                'product_company_id' => 1,
                'product_id' => 1,
                'option_id' => 2,
            ],
        ]);
    }
};
