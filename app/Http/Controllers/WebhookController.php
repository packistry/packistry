<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\VersionNotFoundException;
use App\Import;
use App\Models\Package;
use App\Sources\Deletable;
use App\Sources\Importable;
use Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;

abstract class WebhookController extends Controller
{
    public function __construct(private readonly Import $import) {}

    public function push(Importable $event): JsonResponse
    {
        /** @var Package $package */
        $package = $this->repository()
            ->packages()
            ->where('name', $event->name())
            ->firstOrFail();

        $client = $package->source?->client() ?? Http::withHeaders([]);

        try {
            $version = $this->import->import(
                repository: $this->repository(),
                importable: $event,
                client: $client
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

        return response()->json($version);
    }

    public function delete(Deletable $event): JsonResponse
    {
        $package = $this
            ->repository()
            ->packages()
            ->where('name', $event->name())
            ->firstOrFail();

        $version = $package
            ->versions()
            ->where('name', $event->version())
            ->firstOrFail();

        $version->delete();

        return response()->json($version);
    }
}
