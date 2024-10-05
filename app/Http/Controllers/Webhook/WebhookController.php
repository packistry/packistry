<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\VersionNotFoundException;
use App\Http\Controllers\RepositoryAwareController;
use App\Http\Resources\VersionResource;
use App\Models\Source;
use App\Sources\Deletable;
use App\Sources\Importable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class WebhookController extends RepositoryAwareController
{
    abstract public function authorizeWebhook(Request $request): void;

    protected function source(): Source
    {
        return once(function () {
            $sourceId = request()->route('sourceId');

            if (is_object($sourceId)) {
                abort(401);
            }

            return Source::query()
                ->findOrFail($sourceId);
        });
    }

    public function push(Importable $event): JsonResponse
    {
        $package = $this->source()->packages()
            ->where('provider_id', $event->id())
            ->firstOrFail();

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
        $package = $this->source()->packages()
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
