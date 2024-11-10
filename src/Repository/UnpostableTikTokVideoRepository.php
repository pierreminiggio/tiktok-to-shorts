<?php

namespace PierreMiniggio\TiktokToShorts\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class UnpostableTikTokVideoRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function insertVideoIfNeeded(
        int $tiktokVideoId
    ): void
    {
        $unpostableQueryParams = [
            'tiktok_id' => $tiktokVideoId,
        ];

        $findUnpostableQuery = [
            $this->fetcher
                ->createQuery('tiktok_video_unpostable_on_shorts')
                ->select('id')
                ->where('tiktok_id = :tiktok_id')
            ,
            $unpostableQueryParams
        ];
        $queriedIds = $this->fetcher->query(...$findUnpostableQuery);

        if ($queriedIds) {
            return;
        }
        
        $insertParams = $unpostableQueryParams;

        $this->fetcher->exec(
            $this->fetcher
                ->createQuery('tiktok_video_unpostable_on_shorts')
                ->insertInto(
                    'tiktok_id',
                    ':tiktok_id'
                )
            ,
            $insertParams
        );
    }
}
