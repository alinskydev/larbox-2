<?php

namespace Http\Common\Record\Tests\Index;

class ForCompanyGroupTest extends _TestCase
{
    public int $userId = 2;

    public function test_type___product(): void
    {
        $this->sendRequest(
            path: 'product',
            query: [
                'filter' => [
                    'id' => [1, 2],
                    'value' => 'product',
                    'barcode' => '0000000000001',
                    'full_name_or_barcode' => 'product',
                    'category_id' => 2,
                ],
            ],
        );
    }

    public function test_type___product___products(): void
    {
        $this->sendRequest(
            path: 'product',
            query: [
                'filter' => [
                    'parent_id' => 'none',
                ],
            ],
        );
    }

    public function test_type___product___variations(): void
    {
        $this->sendRequest(
            path: 'product',
            query: [
                'filter' => [
                    'parent_id' => 1,
                ],
            ],
        );
    }

    public function test_type___product_brand(): void
    {
        $this->sendRequest(
            path: 'product_brand',
            query: [
                'filter' => [
                    'id' => [1, 2],
                    'value' => 'brand',
                ],
            ],
        );
    }

    public function test_type___product_category_list(): void
    {
        $this->sendRequest(
            path: 'product_category_list',
            query: [
                'filter' => [
                    'id' => [1, 2],
                    'value' => 'category',
                ],
            ],
        );
    }

    public function test_type___product_category_tree(): void
    {
        $this->sendRequest(
            path: 'product_category_tree',
        );
    }

    public function test_type___product_option(): void
    {
        $this->sendRequest(
            path: 'product_option',
            query: [
                'filter' => [
                    'id' => [1, 2],
                    'value' => 'option',
                    'company_id' => [1, 2],
                    'specification_id' => 1,
                ],
            ],
        );
    }

    public function test_type___product_specification(): void
    {
        $this->sendRequest(
            path: 'product_specification',
            query: [
                'filter' => [
                    'id' => [1, 2],
                    'value' => 'specification',
                ],
            ],
        );
    }
}
