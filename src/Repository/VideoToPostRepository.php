<?php

namespace PierreMiniggio\TiktokToShorts\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class VideoToPostRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function insertVideoIfNeeded(
        string $shortsId,
        int $shortsChannelId,
        int $tiktokVideoId
    ): void
    {
        $postQueryParams = [
            'channel_id' => $shortsChannelId,
            'shorts_id' => $shortsId
        ];
        $findPostIdQuery = [
            $this->fetcher
                ->createQuery('shorts_video')
                ->select('id')
                ->where('channel_id = :channel_id AND shorts_id = :shorts_id')
            ,
            $postQueryParams
        ];
        $queriedIds = $this->fetcher->query(...$findPostIdQuery);
        
        if (! $queriedIds) {
            $this->fetcher->exec(
                $this->fetcher
                    ->createQuery('shorts_video')
                    ->insertInto(
                        'channel_id, shorts_id',
                        ':channel_id, :shorts_id'
                    )
                ,
                $postQueryParams
            );
            $queriedIds = $this->fetcher->query(...$findPostIdQuery);
        }

        $postId = (int) $queriedIds[0]['id'];
        
        $pivotQueryParams = [
            'shorts_id' => $postId,
            'tiktok_id' => $tiktokVideoId
        ];

        $queriedPivotIds = $this->fetcher->query(
            $this->fetcher
                ->createQuery('shorts_video_tiktok_video')
                ->select('id')
                ->where('shorts_id = :shorts_id AND tiktok_id = :tiktok_id')
            ,
            $pivotQueryParams
        );
        
        if (! $queriedPivotIds) {
            $this->fetcher->exec(
                $this->fetcher
                    ->createQuery('shorts_video_tiktok_video')
                    ->insertInto('shorts_id, tiktok_id', ':shorts_id, :tiktok_id')
                ,
                $pivotQueryParams
            );
        }
    }
}
