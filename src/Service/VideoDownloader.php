<?php

namespace PierreMiniggio\TiktokToShorts\Service;

use Exception;
use PierreMiniggio\MultiSourcesTiktokDownloader\MultiSourcesTiktokDownloader;

class VideoDownloader
{
    public function __construct(
        private MultiSourcesTiktokDownloader $downloader
    )
    {
    }

    public function download(string $videoFile, string $tikTokUrl): void
    {
        $temporaryVideoFile = $this->downloader->download($tikTokUrl);

        if (! file_exists($temporaryVideoFile)) {
            throw new Exception('Download failed, no ' . $temporaryVideoFile . ' file');
        }

        rename($temporaryVideoFile, $videoFile);

        if (! file_exists($videoFile)) {
            throw new Exception('Missing video file');
        }
    }
}
