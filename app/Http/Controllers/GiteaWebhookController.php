<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Sources\Gitea\Event\DeleteEvent;
use App\Sources\Gitea\Event\PushEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiteaWebhookController extends WebhookController
{
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
}
