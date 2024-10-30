<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

use PierreMiniggio\TiktokToShorts\Repository\VideoToPostRepository;
use PierreMiniggioManual\TiktokToShorts\App;

class UploadFormSubmitController
{
    public function __construct(private VideoToPostRepository $videoToPostRepository)
    {
    }
    
    public function __invoke()
    {
        $videoId = $_GET['videoId'] ?? null;
        $youtubeVideoId = $_GET['youtubeVideoId'] ?? null;
        $shortsChannelId = $_GET['shortsChannelId'] ?? null;

        if (! $videoId || ! $youtubeVideoId || ! $shortsChannelId) {
            App::redirect('?page=videos');
        }

        $this->videoToPostRepository->insertVideoIfNeeded(
            $youtubeVideoId,
            $shortsChannelId,
            $videoId
        );

        App::redirect('?page=videos');
    }
}
