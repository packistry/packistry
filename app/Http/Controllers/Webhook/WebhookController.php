<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\VersionNotFoundException;
use App\Http\Controllers\RepositoryAwareController;
use App\Http\Resources\VersionResource;
use App\Models\Package;
use App\Normalizer;
use App\Sources\Deletable;
use App\Sources\Importable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;

abstract class WebhookController extends RepositoryAwareController
{
    public function push(Importable $event): JsonResponse
    {
        $repository = $this->repository();

        /** @var Package $package */
        $package = $repository
            ->packages()
            ->whereHas('source', function (Builder $query) use ($event): void {
                $query->where('url', Normalizer::url($event->url()));
            })
            ->where('provider_id', $event->id())
            ->firstOrFail();

        $package->setRelation('repository', $repository);

        $client = $package->source?->client();

        if (is_null($client)) {
            return response()->json([
                'archive' => ['Failed to resolve client for package'],
            ], 422);
        }

        try {
            $version = $client->import(
                $package,
                importable: $event,
            );
        } catch (ArchiveInvalidContentTypeException) {
            return response()->json([
                'archive' => ['Invalid content type'],
            ], 422);
        } catch (FailedToFetchArchiveException $e) {
            return response()->json([
                'archive' => ['failed to fetch archive', $e->getMessage()],
            ], 422);
        } catch (ComposerJsonNotFoundException) {
            return response()->json([
                'file' => ['composer.json not found in archive'],
            ], 422);
        } catch (VersionNotFoundException) {
            return response()->json([
                'version' => ['no version provided'],
            ], 422);
        } catch (ConnectionException $e) {
            return response()->json([
                'archive' => ['connection failed', $e->getMessage()],
            ], 422);
        }

        return response()->json(
            new VersionResource($version),
            201
        );
    }

    public function delete(Deletable $event): JsonResponse
    {
        $package = $this->repository()
            ->packages()
            ->whereHas('source', function (Builder $query) use ($event): void {
                $query->where('url', Normalizer::url($event->url()));
            })
            ->where('provider_id', $event->id())
            ->firstOrFail();

        $version = $package
            ->versions()
            ->where('name', $event->version())
            ->firstOrFail();

        $version->delete();

        return response()->json($version);
    }
}
