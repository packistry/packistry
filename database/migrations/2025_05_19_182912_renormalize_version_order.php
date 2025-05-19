<?php

declare(strict_types=1);

use App\Jobs\NormalizeVersionOrder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        dispatch(new NormalizeVersionOrder);
    }
};
