<?php

namespace PierreMiniggio\TiktokToShorts\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class LinkedChannelRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function findAll(): array
    {
        return $this->fetcher->query(
            $this->fetcher
                ->createQuery('github_account_youtube_channel as gayc')
                ->leftJoin(
                    'github_account as g',
                    'g.id = gayc.github_id'
                )
                ->select('
                    gayc.youtube_id as y_id,
                    g.id as g_id,
                    g.api_token
                ')
        );
    }
}
