<?php
declare(strict_types=1);

namespace Kununu\CodeGenerator\Domain\Service;

interface ManualOperationCollectorInterface
{
    public function collectOperationDetails(): array;
}
