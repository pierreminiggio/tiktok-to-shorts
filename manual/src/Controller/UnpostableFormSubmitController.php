<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

use PierreMiniggio\TiktokToShorts\Repository\UnpostableTikTokVideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\VideoRepository;
use PierreMiniggioManual\TiktokToShorts\App;

class UnpostableFormSubmitController
{
    public function __construct(
        private VideoRepository $videoRepository,
        private UnpostableTikTokVideoRepository $unpostableTikTokVideoRepository
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

        $this->unpostableTikTokVideoRepository->insertVideoIfNeeded($videoId);

        App::redirect('?page=videos');
    }
}
