<?php

namespace PierreMiniggio\TiktokToShorts\Repository;

use DateTime;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class RepoToDeleteRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function findByDeletable(): array
    {
        $reposToDelete = $this->fetcher->query(
            $this->fetcher
                ->createQuery('github_repo')
                ->select('id, url')
                ->where('deleted_at IS NULL AND created_at < :date')
            ,
            ['date' => (new DateTime())->modify('-3 days')->format('Y-m-d H:i:s')]
        );
        
        return $reposToDelete;
    }

    public function delete(int $id): void
    {
        $this->fetcher->exec(
            $this->fetcher
                ->createQuery('github_repo')
                ->update('deleted_at = NOW()')
                ->where('id = :id')
            ,
            ['id' => $id]
        );
    }
}
