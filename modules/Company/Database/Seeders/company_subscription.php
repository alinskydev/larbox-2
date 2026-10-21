<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Company\Enums\SubscriptionRecordLimitEnum;

return new class() extends Seeder
{
    public function run()
    {
        $recordLimits = $this->recordLimits();

        DB::table('company_subscription')->insert([
            [
                'creator_id' => 1,
                'name' => 'Subscription 1',
                'description' => 'Description 1',
                'record_limits' => json_encode($recordLimits['filled']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 1,
                'name' => 'Subscription 2',
                'description' => 'Description 2',
                'record_limits' => json_encode($recordLimits['defaults']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    private function recordLimits(): array
    {
        $defaults = SubscriptionRecordLimitEnum::defaults();
        $filled = array_map(fn($value) => 1000000, $defaults);

        return [
            'defaults' => $defaults,
            'filled' => $filled,
        ];
    }
};
