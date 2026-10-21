<?php

use App\Helpers\Database\DatabaseSeederHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('product_specification')->insert([
            [
                'creator_id' => 2,
                'company_id' => 1,
                'name' => DatabaseSeederHelper::localized('Specification 1'),
                'description' => DatabaseSeederHelper::localized('Description 1'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 2,
                'company_id' => 1,
                'name' => DatabaseSeederHelper::localized('Specification 2'),
                'description' => DatabaseSeederHelper::localized('Description 2'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
