<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TestResponse::macro('assertJsonContent', function (mixed $data): TestResponse {
            expect(json_encode(json_decode($this->content()), JSON_PRETTY_PRINT))
                ->toBe(json_encode($data, JSON_PRETTY_PRINT));

            return $this;
        });
    }
}
