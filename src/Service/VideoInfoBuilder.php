<?php

namespace PierreMiniggio\TiktokToShorts\Service;

use PierreMiniggio\TiktokToShorts\Entity\VideoInfo;
use PierreMiniggio\TiktokToShorts\Repository\ShortsValueForTikTokVideoRepository;

class VideoInfoBuilder
{

    public function __construct(
        private ShortsValueForTikTokVideoRepository $shortsValueForTikTokVideoRepository
    )
    {
    }
    
    public static function getYoutubeMaxTitleLength(): int
    {
        return 100;
    }

    public static function getSourceVideoLink(int $videoToPostId): string
    {
        return 'https://tiktok.ggio.fr/' . $videoToPostId;
    }

    public function getVideoInfos(
        int $videoToPostId,
        ?string $legend,
        string $channelDescription
    ): VideoInfo
    {
        $youtubeMaxTitleLength = self::getYoutubeMaxTitleLength();

        $defaultLegend = 'Most Awesome Shorts Video Ever #bestshorts';
        $legend = $legend ? $legend : $defaultLegend;
        $legend = str_replace(['<', '>'], ' ', $legend); // Remove unallowed chars on Youtube

        if (strlen($legend) <= $youtubeMaxTitleLength) {
            $title = $legend;
        } else {
            $legendWords = explode(' ', $legend);
            if (count($legendWords) === 1) {
                $title = substr($legend, 0, $youtubeMaxTitleLength);
            } else {
                $title = $defaultLegend;
                $wipTitle = '';

                foreach ($legendWords as $legendWordIndex => $legendWord) {
                    $newWipTitle = $wipTitle;

                    if ($legendWordIndex > 0) {
                        $newWipTitle .= ' ';
                    }

                    $newWipTitle .= $legendWord;

                    if (strlen($newWipTitle) > $youtubeMaxTitleLength) {
                        break;
                    }

                    $wipTitle = $newWipTitle;
                }

                if ($wipTitle !== '') {
                    $title = $wipTitle;
                }
            }
        }
        
        $description = str_replace(
            '[tiktok_description]',
            $legend,
            $channelDescription
        );

        $description = str_replace(
            '[tiktok_url]',
            self::getSourceVideoLink($videoToPostId),
            $description
        );
        
        $tags = [];
        $explodedOnHashTags = explode('#', $legend);
        if (count($explodedOnHashTags) > 1) {
            foreach ($explodedOnHashTags as $tagStartSplitIndex => $tagStartSplitElt) {
                if ($tagStartSplitIndex === 0) {
                    continue;
                }
                
                $tag = trim(explode(' ', $tagStartSplitElt)[0]);
                $tags[] = $tag;
            }
        }

        $alteredFields = $this->shortsValueForTikTokVideoRepository->findForVideo($videoToPostId);

        $titleChanged = ! empty($alteredFields['title']);
        if ($titleChanged) {
            $title = $alteredFields['title'];
        }

        $descriptionChanged = ! empty($alteredFields['description']);
        if ($descriptionChanged) {
            $description = $alteredFields['description'];
        }

        $tagsChanged = ! empty($alteredFields['tags']);
        if ($tagsChanged) {
            $tags = explode(', ', $alteredFields['tags']);
        }

        return new VideoInfo($legend, $title, $description, $tags, $titleChanged, $descriptionChanged, $tagsChanged);
    }
}
