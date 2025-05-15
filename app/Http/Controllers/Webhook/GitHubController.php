<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Exceptions\VersionNotFoundException;
use App\Http\Controllers\Webhook\Traits\AuthorizeHubSignatureEvent;
use App\Sources\GitHub\Event\DeleteEvent;
use App\Sources\GitHub\Event\PushEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GitHubController extends WebhookController
{
    use AuthorizeHubSignatureEvent;

    /**
     * @throws VersionNotFoundException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizeWebhook($request);

        return match ($request->header('X-GitHub-Event')) {
            'push' => $this->push(PushEvent::from($request)),
            'delete' => $this->delete(DeleteEvent::from($request)),
            'ping' => response()->json(status: 204),
            default => response()->json([
                'event' => ['unknown event type'],
            ], 422)
        };
    }
}
