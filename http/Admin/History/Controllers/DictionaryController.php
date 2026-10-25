<?php

namespace Http\Admin\History\Controllers;

use App\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\History\Search\StoreHttpLogSearch;

class DictionaryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $response = [
            'enums' => [
                'request_paths' => [
                    'store' => StoreHttpLogSearch::REQUEST_PATHS,
                ],
            ],
        ];

        return response()->json($response);
    }
}
