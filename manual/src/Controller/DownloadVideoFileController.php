<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

use PierreMiniggio\TiktokToShorts\Repository\VideoRepository;
use PierreMiniggioManual\TiktokToShorts\App;
use VideoDownloader;

class DownloadVideoFileController
{
    public function __construct(
        private string $cacheFolder,
        private VideoRepository $videoRepository,
        private VideoDownloader $downloader
    )
    {
    }
    
    public function __invoke()
    {
        $videoId = $_GET['videoId'] ?? null;

        if (! $videoId) {
            App::redirect('?page=videos');
        }

        $video = $this->videoRepository->find($videoId);

        if (! $video) {
            http_response_code(404);
            echo json_encode(['message' => 'No video for id ' . $videoId]);

            return;
        }

        $videoUrl = $video['url'] ?? null;

        if (! $videoUrl) {
            http_response_code(500);
            echo json_encode(['message' => 'No TikTok video url for id ' . $videoId]);

            return;
        }

        $videoFilePath = $this->cacheFolder . $videoId . '.mp4.';

        if (file_exists($videoFilePath)) {
            http_response_code(409);
            echo json_encode(['message' => 'File already downloaded for video id ' . $videoId]);

            return;
        }

        $this->downloader->download($videoFilePath, $videoUrl);
    }
}
