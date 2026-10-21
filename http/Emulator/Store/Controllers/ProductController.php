<?php

namespace Http\Emulator\Store\Controllers;

use App\Controllers\Controller;
use App\Helpers\StringHelper;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function list(): JsonResponse
    {
        return response()->json(
            array_map(
                fn($index) => [
                    'id' => $index,
                    'name' => "Product $index",
                ],
                range(1, 2),
            ),
        );
    }

    public function create(): JsonResponse
    {
        $productId = StringHelper::uuid();

        return response()->json([
            'id' => $productId,
            'name' => "Product $productId",
        ]);
    }

    public function update(int $id): JsonResponse
    {
        return response()->json([
            'id' => $id,
            'name' => "Product $id",
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        return $this->successResponse();
    }
}
