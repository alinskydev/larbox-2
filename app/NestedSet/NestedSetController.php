<?php

namespace App\NestedSet;

use App\Controllers\ResourceController;
use App\Helpers\Database\DatabaseTransactionHelper;
use Symfony\Component\HttpFoundation\Response;

class NestedSetController extends ResourceController
{
    public function tree(): Response
    {
        $rootId = $this->model->query()->whereNull('parent_id')->firstOrFail()->id;

        $response = $this->model->query()
            ->withTrashed()
            ->get()
            ->toTree($rootId)
            ->toArray();

        return response()->json($response);
    }

    public function move(NestedSetMoveRequest $request): Response
    {
        $data = $request->validatedData;

        $this->model->safelyDBProcess(function () use ($data) {
            $companyId = user()->company->parent_id ?: user()->company_id;
            DatabaseTransactionHelper::lock("{$this->model->getTable()}:tree:$companyId");

            $sourceModel = $this->model->query()->findOrFail($data['source_id']);
            $destinationModel = $this->model->query()->findOrFail($data['destination_id']);

            match ($data['position']) {
                'before' => $sourceModel->insertBeforeNode($destinationModel),
                'after' => $sourceModel->insertAfterNode($destinationModel),
                'into' => $destinationModel->appendNode($sourceModel),
            };
        });

        return $this->successResponse();
    }
}
