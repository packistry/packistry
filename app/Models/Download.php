<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DownloadFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $package_id
 * @property int $version_id
 * @property int|null $token_id
 * @property string $version_name
 * @property string|null $ip
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Package $package
 * @property-read Version $version
 *
 * @method static DownloadFactory factory($count = null, $state = [])
 * @method static Builder|Download newModelQuery()
 * @method static Builder|Download newQuery()
 * @method static Builder|Download query()
 *
 * @mixin Eloquent
 */
class Download extends Model
{
    /** @use HasFactory<DownloadFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return BelongsTo<Version, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    /**
     * @param  int|int[]  $packageIds
     * @return array{date: string, downloads: int}[]
     */
    public static function perDayForPackages(int $days, int|array|null $packageIds = []): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $query = self::query()
            ->whereBetween('created_date', [$startDate, $endDate]);

        if ($packageIds !== null) {
            $query
                ->whereIn('package_id', is_array($packageIds) ? $packageIds : [$packageIds]);
        }

        $query
            ->selectRaw('created_date, COUNT(*) as count')
            ->groupBy('created_date');

        $downloads = $query->pluck('count', 'created_date');

        $dates = [];

        $currentDate = $startDate->copy();

        for ($i = 0; $i < round($startDate->diffInDays($endDate)); $i++) {
            $dateKey = $currentDate->format('Y-m-d');

            $dates[] = [
                'date' => $dateKey,
                'downloads' => $downloads->get($dateKey, 0),
            ];

            $currentDate->addDay();
        }

        return $dates;
    }
}
