<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TokenAbility;
use App\Models\Contracts\Tokenable;
use App\Models\Package;
use App\Models\Repository;
use Generator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class RepositoryAwareController
{
    private const int ARCHIVE_CHUNK_SIZE = 1024 * 1024;

    protected function token(): ?Tokenable
    {
        $token = Auth::guard('sanctum')->user();

        if ($token !== null && ! $token instanceof Tokenable) {
            return throw new RuntimeException('Authenticatable class must implement '.Tokenable::class);
        }

        return $token;
    }

    protected function repository(): Repository
    {
        return once(function () {
            $path = request()->route('repository');

            if (is_object($path)) {
                abort(401);
            }

            return Repository::query()
                ->queryByPath($path)
                ->tokenScoped()
                ->firstOrFail();
        });
    }

    protected function tokenCanAccessPackage(Package $package): bool
    {
        return Package::query()
            ->tokenScoped()
            ->whereKey($package->id)
            ->exists();
    }

    protected function authorize(TokenAbility $ability): void
    {
        $token = $this->token();
        $repository = $this->repository();

        if (in_array($ability, TokenAbility::readAbilities(), true) && $repository->public) {
            return;
        }

        if (is_null($token) || ! $token->tokenCan($ability->value) || $token->currentAccessToken()->isExpired()) {
            abort(401);
        }
    }

    /**
     * Stream an archive from storage in chunks. Under Octane the Generator callback is handed to RoadRunner
     * as is, any other callback makes it buffer the whole archive in worker memory.
     */
    protected function streamArchive(string $path): StreamedResponse
    {
        $name = basename($path);

        return response()->stream(
            function () use ($path): Generator {
                $stream = Storage::readStream($path);

                if ($stream === null) {
                    throw new RuntimeException("Failed to open archive $path");
                }

                try {
                    while (! feof($stream)) {
                        $chunk = fread($stream, self::ARCHIVE_CHUNK_SIZE);

                        if ($chunk === false) {
                            break;
                        }

                        yield $chunk;
                    }
                } finally {
                    fclose($stream);
                }
            },
            headers: [
                'Content-Type' => Storage::mimeType($path),
                'Content-Length' => Storage::size($path),
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    $name,
                    str_replace('%', '', Str::ascii($name)),
                ),
            ],
        );
    }
}
