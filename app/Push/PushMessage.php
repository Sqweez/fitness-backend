<?php

namespace App\Push;

class PushMessage
{
    /**
     * @param array<string, string> $data Payload the app uses to open the right screen
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {
    }
}
