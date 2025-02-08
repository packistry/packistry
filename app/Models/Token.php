<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TokenType;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use Override;
use RuntimeException;

/**
 * @property int $id
 * @property string $tokenable_type
 * @property int $tokenable_id
 * @property string $name
 * @property string $token
 * @property string[]|null $abilities
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|Eloquent $tokenable
 *
 * @method static Builder|Token newModelQuery()
 * @method static Builder|Token newQuery()
 * @method static Builder|Token query()
 *
 * @mixin Eloquent
 */
class Token extends PersonalAccessToken
{
    use SoftDeletes;

    public function type(): TokenType
    {
        return match ($this->tokenable_type) {
            User::class => TokenType::PERSONAL_ACCESS,
            DeployToken::class => TokenType::DEPLOY,
            default => throw new RuntimeException("No type for $this->tokenable_type")
        };
    }

    /**
     * @param  string  $token
     */
    #[Override]
    public static function findToken($token): ?PersonalAccessToken
    {
        [, $token] = explode('-', $token, 2);

        return parent::findToken($token);
    }
}
