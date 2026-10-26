<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mirror\MetadataCache;
use App\Models\Upstream;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RevalidateMetadata implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function __construct(
        private readonly Upstream $upstream,
        private readonly string $name,
        private readonly bool $dev,
    ) {
        //
    }

    public function uniqueId(): string
    {
        return "{$this->upstream->id}:$this->name:".($this->dev ? 'dev' : 'stable');
    }

    public function handle(MetadataCache $metadata): void
    {
        $metadata->revalidate($this->upstream, $this->name, $this->dev);
    }
}
