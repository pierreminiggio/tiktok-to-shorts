<?php

namespace PierreMiniggio\TiktokToShorts\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class NonUploadedVideoRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function findByShortsAndTiktokChannelIds(
        int $shortsChannelId,
        int $tiktokChannelId,
        int $limit
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

        $unpostableTikTokVideoIds = $this->fetcher->query(
            $this->fetcher->createQuery('tiktok_video_unpostable_on_shorts')->select('tiktok_id')
        );
        $unpostableTikTokVideoIds = array_map(fn ($entry) => (int) $entry['tiktok_id'], $unpostableTikTokVideoIds);

        $query = $this->fetcher
            ->createQuery('tiktok_video as t')
            ->select('t.id, t.legend, t.tiktok_url as url')
            ->where('t.account_id = :channel_id' . (
                $postedShortsVideoIds ? ' AND svtv.id IS NULL' : ''
            ) . (
                    $unpostableTikTokVideoIds ? (' AND t.id NOT IN  (' . implode(', ', $unpostableTikTokVideoIds) . ')') : ''
                )
            )
            ->limit($limit)
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
