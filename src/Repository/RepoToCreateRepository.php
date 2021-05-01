<?php

namespace PierreMiniggio\TiktokToShorts\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class RepoToCreateRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function insertRepoIfNeeded(
        string $githubId,
        string $url,
        int $githubAccountId,
        int $youtubeVideoId
    ): void
    {
        $postQueryParams = [
            'account_id' => $githubAccountId,
            'github_id' => $githubId
        ];
        $findPostIdQuery = [
            $this->fetcher
                ->createQuery('github_repo')
                ->select('id')
                ->where('account_id = :account_id AND github_id = :github_id')
            ,
            $postQueryParams
        ];
        $queriedIds = $this->fetcher->query(...$findPostIdQuery);
        
        if (! $queriedIds) {
            $this->fetcher->exec(
                $this->fetcher
                    ->createQuery('github_repo')
                    ->insertInto(
                        'account_id, github_id, url',
                        ':account_id, :github_id, :url'
                    )
                ,
                array_merge($postQueryParams, ['url' => $url])
            );
            $queriedIds = $this->fetcher->query(...$findPostIdQuery);
        }

        $postId = (int) $queriedIds[0]['id'];
        
        $pivotQueryParams = [
            'github_id' => $postId,
            'youtube_id' => $youtubeVideoId
        ];

        $queriedPivotIds = $this->fetcher->query(
            $this->fetcher
                ->createQuery('github_repo_youtube_video')
                ->select('id')
                ->where('github_id = :github_id AND youtube_id = :youtube_id')
            ,
            $pivotQueryParams
        );
        
        if (! $queriedPivotIds) {
            $this->fetcher->exec(
                $this->fetcher
                    ->createQuery('github_repo_youtube_video')
                    ->insertInto('github_id, youtube_id', ':github_id, :youtube_id')
                ,
                $pivotQueryParams
            );
        }
    }
}
