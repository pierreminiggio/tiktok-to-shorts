<?php

namespace PierreMiniggio\TiktokToShorts\Entity;

class VideoInfo
{
    public function __construct(
        public string $legend,
        public string $title,
        public string $description,
        public array $tags
    )
    {
    }
}
