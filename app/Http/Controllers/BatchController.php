<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\BatchResource;
use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class BatchController extends Controller
{
    public function __construct(private BatchRepository $batches)
    {
        //
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize(Permission::BATCH_READ);

        $bathes = $this->batches->get(
            limit: 1000,
            before: null
        );

        return BatchResource::collection($bathes);
    }

    public function destroy(): void
    {
        $this->authorize(Permission::BATCH_DELETE);

        if (! ($this->batches instanceof DatabaseBatchRepository)) {
            return;
        }

        $this->batches->prune(
            before: now()
        );

        $this->batches->pruneUnfinished(
            before: now(),
        );
    }
}
