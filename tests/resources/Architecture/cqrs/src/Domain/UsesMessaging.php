<?php
declare(strict_types=1);

namespace App\Domain;

final class UsesMessaging
{
    public function __construct(
        public \App\Messaging\CleanProducer $a,
    ) {
    }
}
