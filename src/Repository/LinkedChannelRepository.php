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
                ->createQuery('shorts_channel_tiktok_account as scta')
                ->join(
                    'shorts_channel as s',
                    's.id = scta.shorts_id'
                )
                ->join(
                    'youtube_channel as y',
                    's.youtube_id = y.id'
                )
                ->select('
                    scta.tiktok_id as t_id,
                    s.id as s_id,
                    y.youtube_id,
                    s.heropost_login,
                    s.heropost_password,
                    s.google_client_id,
                    s.google_client_secret,
                    s.google_refresh_token,
                    s.description
                ')
        );
    }
}
