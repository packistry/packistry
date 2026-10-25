<?php

declare(strict_types=1);

namespace App\Actions\Upstreams;

use App\Actions\Upstreams\Inputs\UpstreamCredentialInput;
use App\Models\Upstream;
use App\Models\UpstreamCredential;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SyncUpstreamCredentials
{
    /**
     * Replace the credentials of the upstream. Existing credentials (by id) keep their
     * secret when no new secret is given.
     *
     * @param  UpstreamCredentialInput[]  $inputs
     */
    public function handle(Upstream $upstream, array $inputs): void
    {
        $existing = $upstream->credentials()->get()->keyBy('id');
        $hosts = [];
        $keep = [];

        foreach ($inputs as $index => $input) {
            $host = Str::lower(trim($input->host));

            if ($host === '' || in_array($host, $hosts, true)) {
                throw ValidationException::withMessages([
                    "credentials.$index.host" => 'Each credential needs a unique host.',
                ]);
            }

            $hosts[] = $host;

            if ($input->type->requiresUsername() && ($input->username === null || $input->username === '')) {
                throw ValidationException::withMessages([
                    "credentials.$index.username" => 'A username (or header name) is required for this credential type.',
                ]);
            }

            /** @var UpstreamCredential|null $credential */
            $credential = $input->id === null ? null : $existing->get($input->id);

            $hasSecret = $input->secret !== null && $input->secret !== '';

            if ($credential === null && ! $hasSecret) {
                throw ValidationException::withMessages([
                    "credentials.$index.secret" => 'A secret is required for new credentials.',
                ]);
            }

            // a secret was given for one host, it is never sent to another one without entering it again
            if ($credential !== null && $credential->host !== $host && ! $hasSecret) {
                throw ValidationException::withMessages([
                    "credentials.$index.secret" => 'Enter the secret again when changing the host of a credential.',
                ]);
            }

            $credential ??= new UpstreamCredential;
            $credential->upstream_id = $upstream->id;
            $credential->host = $host;
            $credential->type = $input->type;
            $credential->username = $input->type->requiresUsername() ? $input->username : null;

            if ($hasSecret) {
                $credential->secret = $input->secret;
            }

            $credential->save();

            $keep[] = $credential->id;
        }

        $upstream->credentials()->whereNotIn('id', $keep)->delete();

        $upstream->load('credentials');
    }
}
