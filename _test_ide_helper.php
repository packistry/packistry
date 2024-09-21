<?php

// @formatter:off
// phpcs:ignoreFile

namespace Illuminate\Testing {
    class TestResponse
    {
        public function assertJsonContent(mixed $data): self
        {
            return $this;
        }
    }
}
