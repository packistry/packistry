<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UpstreamCredentialType;
use Database\Factories\UpstreamCredentialFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $upstream_id
 * @property string $host
 * @property UpstreamCredentialType $type
 * @property string|null $username
 * @property string $secret
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Upstream $upstream
 *
 * @method static UpstreamCredentialFactory factory($count = null, $state = [])
 *
 * @mixin Eloquent
 */
class UpstreamCredential extends Model
{
    /** @use HasFactory<UpstreamCredentialFactory> */
    use HasFactory;

    protected $casts = [
        'type' => UpstreamCredentialType::class,
        'secret' => 'encrypted',
    ];

    protected $hidden = ['secret'];

    /**
     * @return BelongsTo<Upstream, $this>
     */
    public function upstream(): BelongsTo
    {
        return $this->belongsTo(Upstream::class);
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return match ($this->type) {
            UpstreamCredentialType::HTTP_BASIC => [
                'Authorization' => 'Basic '.base64_encode("$this->username:$this->secret"),
            ],
            UpstreamCredentialType::BEARER => [
                'Authorization' => "Bearer $this->secret",
            ],
            UpstreamCredentialType::HEADER => [
                (string) $this->username => $this->secret,
            ],
        };
    }
}
