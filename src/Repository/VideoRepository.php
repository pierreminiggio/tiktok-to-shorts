<?php

namespace PierreMiniggio\TiktokToShorts\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class VideoRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function find(int $id): ?array
    {
        $query = $this->fetcher->createQuery(
            'tiktok_video as t'
        )->select(
            't.id, t.legend, t.tiktok_url as url'
        )->where(
            't.id = :id'
        );
        $videos = $this->fetcher->query($query, ['id' => $id]);

        $numberOfVideos = count($videos);

        if ($numberOfVideos === 0) {
            return null;
        }

        if ($numberOfVideos > 1) {
            var_dump('Warning: More than one video under the id ' . $id);
        }

        return $videos[0];
    }
}
