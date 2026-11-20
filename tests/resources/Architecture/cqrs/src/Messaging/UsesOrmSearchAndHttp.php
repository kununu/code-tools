<?php
declare(strict_types=1);

namespace App\Messaging;

final class UsesOrmSearchAndHttp
{
    public function __construct(
        public \Doctrine\ORM\ArchitectureFixture $a,
        public \Kununu\Doctrine\ArchitectureFixture $b,
        public \Elasticsearch\ArchitectureFixture $c,
        public \Elastic\Elasticsearch\ArchitectureFixture $d,
        public \OpenSearch\ArchitectureFixture $e,
        public \Kununu\Elasticsearch\ArchitectureFixture $f,
        public \GuzzleHttp\ArchitectureFixture $g,
        public \Symfony\Component\HttpFoundation\ArchitectureFixture $h,
        public \Symfony\Component\HttpKernel\ArchitectureFixture $i,
        public \App\Request\CleanRequest $j,
        public \App\Validator\CleanValidator $k,
    ) {
    }
}
