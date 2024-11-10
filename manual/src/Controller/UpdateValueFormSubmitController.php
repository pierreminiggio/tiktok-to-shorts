<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

use PierreMiniggio\TiktokToShorts\Repository\VideoRepository;
use PierreMiniggioManual\TiktokToShorts\App;

class UpdateValueFormSubmitController
{
    public function __construct(
        private VideoRepository $videoRepository
    )
    {
    }
    
    public function __invoke()
    {
        $videoId = $_GET['videoId'] ?? null;

        if (! $videoId) {
            App::redirect('?page=videos');
        }

        $title = $_POST['title'] ?? null;
        $description = $_POST['description'] ?? null;
        $tags = $_POST['tags'] ?? null;

        if (! $title && ! $description && ! $tags) {
            http_response_code(404);
            echo json_encode(['message' => 'You can either update title, description, or tags']);

            return;
        }

        $video = $this->videoRepository->find($videoId);

        if (! $video) {
            http_response_code(404);
            echo json_encode(['message' => 'No video for id ' . $videoId]);

            return;
        }

        echo '<pre>';
        var_dump($_GET);
        var_dump($_POST);
        echo '</pre>';
        die;

        App::redirect('?page=videos');
    }
}
