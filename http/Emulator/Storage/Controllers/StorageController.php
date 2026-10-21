<?php

namespace Http\Emulator\Storage\Controllers;

use App\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StorageController extends Controller
{
    public function getImages(): JsonResponse
    {
        $files = $this->processFiles(request()->post('files', []));
        return response()->json(['files' => $files]);
    }

    public function upload(): JsonResponse
    {
        $files = $this->processFiles(request()->files->get('files', []));
        return response()->json(['files' => $files]);
    }

    private function processFiles(array $files): array
    {
        return array_map(fn($value) => ['original' => ['raw' => 'test_data/images/1.png']], $files);
    }
}
