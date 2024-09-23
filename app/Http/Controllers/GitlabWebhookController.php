<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Incoming\Gitlab\Event\PushEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GitlabWebhookController extends WebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        return match ($request->header('X-Gitlab-Event')) {
            'Push Hook' => $this->pushOrDelete(PushEvent::from($request)),
            default => response()->json([
                'event' => ['unknown event type'],
            ])
        };
    }

    private function pushOrDelete(PushEvent $event): JsonResponse
    {
        if ($event->isDelete()) {
            return $this->delete($event);
        }

        return $this->push($event);
    }
}
