<?php
declare(strict_types=1);

namespace App\Messaging;

final class CleanProducer
{
    public function __construct(
        public \App\UseCase\Command\CleanCommandHandler $a,
    ) {
    }
}
