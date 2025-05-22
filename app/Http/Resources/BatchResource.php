<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Batch
 */
class BatchResource extends JsonResource
{
    /**
     * @return array<array-key, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            $this->mergeWhen($this->options['package'] ?? false, fn () => [
                'package' => new PackageResource($this->options['package']),
            ]),
            'total_jobs' => $this->totalJobs,
            'pending_jobs' => $this->pendingJobs,
            'processed_jobs' => $this->processedJobs(),
            'progress' => $this->progress(),
            'failed_jobs' => $this->failedJobs,
            'created_at' => $this->createdAt,
            'cancelled_at' => $this->cancelledAt,
            'finished_at' => $this->finishedAt,
        ];
    }
}
