<?php
declare(strict_types=1);

namespace App\Controller;

final class UsesClientLibrariesAndReadModelsController
{
    public function __construct(
        public \Kununu\Doctrine\ArchitectureFixture $a,
        public \Elasticsearch\ArchitectureFixture $b,
        public \Elastic\Elasticsearch\ArchitectureFixture $c,
        public \OpenSearch\ArchitectureFixture $d,
        public \Kununu\Elasticsearch\ArchitectureFixture $e,
        public \GuzzleHttp\ArchitectureFixture $f,
        public \Symfony\Component\Console\ArchitectureFixture $g,
        public \App\Projection\CleanProjection $h,
        public \App\Provider\CleanProvider $i,
    ) {
    }
}
