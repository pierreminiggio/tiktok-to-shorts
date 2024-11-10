<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

use PierreMiniggio\TiktokToShorts\Repository\LinkedChannelRepository;
use PierreMiniggio\TiktokToShorts\Repository\NonUploadedVideoRepository;
use PierreMiniggio\TiktokToShorts\Service\VideoInfoBuilder;

class VideoListController
{
    public function __construct(
        private string $cacheUrl,
        private string $cacheFolder,
        private LinkedChannelRepository $linkedChannelRepository,
        private NonUploadedVideoRepository $nonUploadedVideoRepository
    )
    {
    }

    public function __invoke()
    {
        $channels = $this->linkedChannelRepository->findAll();

        $html = <<<HTML
            <head>
                <style>
                    td {
                        border: 1px black solid;
                        padding: 20px;
                    }
                </style>
            </head>
            <body>
                <form action="" method="GET">
                    <input type="hidden" name="page" value="logout">
                    <input type="submit" name="logout" value="Logout">
                </form>
                <table>
                    <tr>
                        <th>Channel</th>
                        <th>Video link</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Tags</th>
                        <th>Action</th>
                    </tr>
        HTML;

        foreach ($channels as $channel) {
            $shortsChannelId = $channel['s_id'];
            $youtubeId = $channel['youtube_id'];

            $videosToPost = $this->nonUploadedVideoRepository->findByShortsAndTiktokChannelIds(
                $shortsChannelId,
                $channel['t_id'],
                10
            );

            $videoInfoBuilder = new VideoInfoBuilder();
            foreach ($videosToPost as $videoToPost) {
                $videoToPostId = $videoToPost['id'];
                $videoInfos = $videoInfoBuilder->getVideoInfos(
                    $videoToPostId,
                    $videoToPost['legend'] ?? null,
                    $channel['description']
                );

                $title = $videoInfos->title;
                $description = $videoInfos->description;
                $tags = $videoInfos->tags;

                $videoLink = $this->cacheUrl . '/' . $videoToPostId . '.mp4';
                $videoFile = $this->cacheFolder . $videoToPostId . '.mp4';

                if (file_exists($videoFile)) {
                    $videoFileHtml = <<<HTML
                        <a href="$videoLink" target="_blank" rel="noreferrer">$videoLink</a>
                    HTML;
                } else {
                    $videoFileHtml = <<<HTML
                        <a href="/?page=downloadFile&videoId=$videoToPostId" target="_blank">Download</a>
                    HTML;
                }

                $tagsString = implode(', ', $tags);

                $html .= <<<HTML
                    <tr>
                        <td><a href="https://youtube.com/channel/$youtubeId" target="_blank" rel="noreferrer">$youtubeId</a></td>
                        <td>$videoFileHtml</td>
                        <td>
                            <form action="" method="GET">
                                <input type="hidden" name="page" value="updateText">
                                <input type="hidden" name="videoId" value="$videoToPostId">
                                <textarea name="title">$title</textarea>
                                <input type="submit" name="update" value="Update">
                            </form>
                        </td>
                        <td><pre>$description</pre></td>
                        <td>$tagsString</td>
                        <td>
                            <form action="" method="GET">
                                <input type="hidden" name="page" value="upload">
                                <input type="hidden" name="videoId" value="$videoToPostId">
                                <input type="hidden" name="shortsChannelId" value="$shortsChannelId">
                                <input type="text" name="youtubeVideoId" placeholder="Youtube video id">
                                <input type="submit" name="markAsUploaded" value="Mark as uploaded">
                            </form>
                        </td>
                    </tr>
                HTML;
            }
        }

        $html .= <<<HTML
                </table>
            </body>
        HTML;

        echo $html;
    }
}
