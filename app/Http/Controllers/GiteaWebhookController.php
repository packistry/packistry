<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\CreateFromZip;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\VersionNotFoundException;
use App\Incoming\Gitea\Event\DeleteEvent;
use App\Incoming\Gitea\Event\PushEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiteaWebhookController extends Controller
{
    public function __construct(private readonly CreateFromZip $createFromZip) {}

    public function __invoke(Request $request): JsonResponse
    {
        return match ($request->header('X-Gitea-Event')) {
            'push' => $this->push(PushEvent::from($request)),
            'delete' => $this->delete(DeleteEvent::from($request)),
            default => response()->json([
                'event' => ['unknown event type'],
            ])
        };
    }

    public function push(PushEvent $event): JsonResponse
    {
        $temp = tmpfile();
        $path = stream_get_meta_data($temp)['uri'];

        file_put_contents($path, file_get_contents($event->archiveUrl()));

        try {
            $version = $this->createFromZip->create(
                repository: $this->repository(),
                path: $path,
                name: $event->repository->fullName,
                subDirectory: "{$event->repository->name}/",
                version: $event->isTag() ? $event->shortRef() : 'dev-'.$event->shortRef(),
            );
        } catch (ComposerJsonNotFoundException) {
            return response()->json([
                'file' => ['composer.json not found in archive'],
            ], 422);
        } catch (VersionNotFoundException) {
            return response()->json([
                'version' => ['no version provided'],
            ], 422);
        } finally {
            fclose($temp);
        }

        return response()->json($version);
    }

    public function delete(DeleteEvent $event): JsonResponse
    {
        $package = $this
            ->repository()
            ->packages()
            ->where('name', $event->repository->fullName)
            ->firstOrFail();

        $versionName = $event->refType === 'branch' ? "dev-$event->ref" : $event->ref;

        $version = $package
            ->versions()
            ->where('name', $versionName)
            ->firstOrFail();

        $version->delete();

        return response()->json($version);
    }
}
