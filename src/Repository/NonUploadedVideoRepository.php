<?php

namespace PierreMiniggio\TiktokToShorts\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class NonUploadedVideoRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function findByShortsAndTiktokChannelIds(
        int $shortsChannelId,
        int $tiktokChannelId
    ): array
    {
        $postedShortsVideoIds = $this->fetcher->query(
            $this->fetcher
                ->createQuery('shorts_video_tiktok_video as svtv')
                ->join('shorts_video as g', 'g.id = svtv.shorts_id')
                ->select('g.id')
                ->where('g.channel_id = :channel_id')
            ,
            ['channel_id' => $shortsChannelId]
        );
        $postedShortsVideoIds = array_map(fn ($entry) => (int) $entry['id'], $postedShortsVideoIds);

        $query = $this->fetcher
            ->createQuery('tiktok_video as t')
            ->select('t.id, t.legend, t.tiktok_url as url')
            ->where('t.account_id = :channel_id' . (
                $postedShortsVideoIds ? ' AND svtv.id IS NULL' : ''
            ))
            ->limit(1)
        ;

        if ($postedShortsVideoIds) {
            $query->join(
                'shorts_video_tiktok_video as svtv',
                't.id = svtv.tiktok_id AND svtv.shorts_id IN (' . implode(', ', $postedShortsVideoIds) . ')'
            );
        }
        $videosToPost = $this->fetcher->query($query, ['channel_id' => $tiktokChannelId]);
        
        return $videosToPost;
    }
}
