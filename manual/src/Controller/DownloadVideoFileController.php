<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

use PierreMiniggio\TiktokToShorts\Repository\VideoToPostRepository;
use PierreMiniggioManual\TiktokToShorts\App;

class DownloadVideoFileController
{
    public function __construct(private VideoToPostRepository $videoToPostRepository)
    {
    }
    
    public function __invoke()
    {
        $videoId = $_GET['videoId'] ?? null;

        if (! $videoId) {
            App::redirect('?page=videos');
        }

        var_dump($videoId);
    }
}
