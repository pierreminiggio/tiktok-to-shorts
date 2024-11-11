<?php

namespace PierreMiniggio\TiktokToShorts\Entity;

class VideoInfo
{
    public function __construct(
        public string $legend,
        public string $title,
        public string $description,
        public array $tags,
        public bool $titleChanged,
        public bool $descriptionChanged,
        public bool $tagsChanged,
    )
    {
    }

    public function valuesChanged(): bool
    {
        return $this->titleChanged || $this->descriptionChanged || $this->tagsChanged;
    }
}
