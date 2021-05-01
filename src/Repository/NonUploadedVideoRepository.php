<?php

namespace PierreMiniggio\TiktokToShorts\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class NonUploadedVideoRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function findByGithubAndYoutubeChannelIds(int $githubAccountId, int $youtubeChannelId): array
    {
        $postedGithubPostIds = $this->fetcher->query(
            $this->fetcher
                ->createQuery('github_repo_youtube_video as gryv')
                ->leftJoin('github_repo as g', 'g.id = gryv.github_id')
                ->select('g.id')
                ->where('g.account_id = :account_id')
            ,
            ['account_id' => $githubAccountId]
        );
        $postedGithubPostIds = array_map(fn ($entry) => (int) $entry['id'], $postedGithubPostIds);

        $query = $this->fetcher
            ->createQuery('youtube_video as y')
            ->select('y.id, y.title, y.url')
            ->where('y.channel_id = :channel_id' . (
                $postedGithubPostIds ? ' AND gryv.id IS NULL' : ''
            ))
        ;

        if ($postedGithubPostIds) {
            $query->leftJoin(
                'github_repo_youtube_video as gryv',
                'y.id = gryv.youtube_id AND gryv.github_id IN (' . implode(', ', $postedGithubPostIds) . ')'
            );
        }
        $postsToPost = $this->fetcher->query($query, ['channel_id' => $youtubeChannelId]);
        
        return $postsToPost;
    }
}
