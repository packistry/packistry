<?php

declare(strict_types=1);

namespace App\Enums;

enum MirrorStatus: string
{
    case OK = 'ok';

    /**
     * Its upstream no longer has the package. It stays with that upstream, so another
     * upstream cannot take over the name, and becomes available again when it returns.
     */
    case MISSING = 'missing';

    /**
     * Its upstream was removed from the repository, it resolves again on the next request.
     */
    case GONE = 'gone';
}
