<?php

declare(strict_types=1);

namespace App\Actions\Upstreams;

use App\Actions\Upstreams\Inputs\StoreUpstreamInput;
use App\Models\Upstream;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class StoreUpstream
{
    public function __construct(private SyncUpstreamCredentials $syncCredentials)
    {
        //
    }

    /**
     * @throws Throwable
     */
    public function handle(StoreUpstreamInput $input): Upstream
    {
        return DB::transaction(function () use ($input): Upstream {
            $upstream = new Upstream;

            $upstream->name = $input->name;
            $upstream->url = rtrim($input->url, '/');
            $upstream->only = self::patterns($input->only);
            $upstream->exclude = self::patterns($input->exclude);

            $upstream->save();

            $this->syncCredentials->handle($upstream, $input->credentials);

            return $upstream;
        });
    }

    /**
     * @param  array<int, string|null>|null  $patterns
     * @return string[]|null
     */
    public static function patterns(?array $patterns): ?array
    {
        $patterns = array_values(array_filter(
            array_map(fn (?string $pattern): string => trim((string) $pattern), $patterns ?? []),
            fn (string $pattern): bool => $pattern !== ''
        ));

        return $patterns === [] ? null : $patterns;
    }
}
