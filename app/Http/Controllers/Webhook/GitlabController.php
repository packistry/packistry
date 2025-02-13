<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Sources\Gitlab\Event\PushEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GitlabController extends WebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizeWebhook($request);

        return match ($request->header('X-Gitlab-Event')) {
            'Push Hook', 'Tag Push Hook' => $this->pushOrDelete(PushEvent::from($request)),
            default => response()->json([
                'event' => ['unknown event type'],
            ], 422)
        };
    }

    private function pushOrDelete(PushEvent $event): JsonResponse
    {
        if ($event->isDelete()) {
            return $this->delete($event);
        }

        return $this->push($event);
    }

    public function authorizeWebhook(Request $request): void
    {
        $secret = $request->header('X-Gitlab-Token');

        if (is_null($secret)) {
            abort(401, 'secret missing');
        }

        if ($secret !== decrypt($this->source()->secret)) {
            abort(401, 'invalid secret');
        }
    }
}
