<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

use PierreMiniggio\TiktokToShorts\Repository\VideoToPostRepository;
use PierreMiniggioManual\TiktokToShorts\App;

class UploadFormSubmitController
{
    public function __construct(
        private string $cacheFolder,
        private VideoToPostRepository $videoToPostRepository
    )
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

        $videoFile = $this->cacheFolder . $videoId . '.mp4';

        if (file_exists($videoFile)) {
            unlink($videoFile);
        }

        App::redirect('?page=videos');
    }
}
