<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

use PierreMiniggio\TiktokToShorts\Repository\LinkedChannelRepository;
use PierreMiniggio\TiktokToShorts\Repository\NonUploadedVideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\ShortsValueForTikTokVideoRepository;
use PierreMiniggio\TiktokToShorts\Service\VideoInfoBuilder;

class VideoListController
{
    public function __construct(
        private string $cacheUrl,
        private string $cacheFolder,
        private LinkedChannelRepository $linkedChannelRepository,
        private NonUploadedVideoRepository $nonUploadedVideoRepository,
        private VideoInfoBuilder $videoInfoBuilder
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

            foreach ($videosToPost as $videoToPost) {
                $videoToPostId = $videoToPost['id'];
                $videoInfos = $this->videoInfoBuilder->getVideoInfos(
                    $videoToPostId,
                    $videoToPost['legend'] ?? null,
                    $channel['description']
                );

                $legend = $videoInfos->legend;
                $title = $videoInfos->title;
                $description = $videoInfos->description;
                $tags = $videoInfos->tags;

                $sourceVideoLink = VideoInfoBuilder::getSourceVideoLink($videoToPostId);
                $videoFileHtml = <<<HTML
                    <a href="$sourceVideoLink" target="_blank">Source : $videoToPostId</a>
                    <br><br>
                HTML;

                $videoLink = $this->cacheUrl . '/' . $videoToPostId . '.mp4';
                $videoFile = $this->cacheFolder . $videoToPostId . '.mp4';

                if (file_exists($videoFile)) {
                    $videoFileHtml .= <<<HTML
                        <a href="$videoLink" target="_blank" rel="noreferrer">$videoLink</a>
                    HTML;
                } else {
                    $videoFileHtml .= <<<HTML
                        <a href="/?page=downloadFile&videoId=$videoToPostId" target="_blank">Download</a>
                    HTML;
                }

                $tagsString = implode(', ', $tags);

                $textAreaStyle = 'display: block; margin-bottom: 10px;';

                $selectedLineStyle = 'background-color: #DDFFFC;';

                $lineStyle = '';

                if ($videoInfos->valuesChanged()) {
                    $lineStyle .= $selectedLineStyle;
                }

                $selectedCellStyle = 'background-color: #BDEFEC;';

                $titleCellStyle = '';

                if ($videoInfos->titleChanged) {
                    $titleCellStyle .= $selectedCellStyle;
                }

                $descriptionCellStyle = '';

                if ($videoInfos->descriptionChanged) {
                    $descriptionCellStyle .= $selectedCellStyle;
                }

                $tagsCellStyle = '';

                if ($videoInfos->tagsChanged) {
                    $tagsCellStyle .= $selectedCellStyle;
                }

                $html .= <<<HTML
                    <tr id="video$videoToPostId" style="$lineStyle">
                        <td><a href="https://youtube.com/channel/$youtubeId" target="_blank" rel="noreferrer">$youtubeId</a></td>
                        <td>$videoFileHtml</td>
                        <td style="$titleCellStyle">
                            <form action="?page=updateValue&videoId=$videoToPostId" method="POST">
                                <textarea name="title" style="$textAreaStyle">$title</textarea>
                                <input type="submit" name="update" value="Update">
                            </form>
                        </td>
                        <td style="$descriptionCellStyle">
                            <form action="?page=updateValue&videoId=$videoToPostId" method="POST">
                                <textarea name="description" style="$textAreaStyle min-height: 300px; min-width: 400px;">$description</textarea>
                                <input type="submit" name="update" value="Update">
                            </form>
                        </td>
                        <td style="$tagsCellStyle">
                            <form action="?page=updateValue&videoId=$videoToPostId" method="POST">
                                <textarea name="tags" style="$textAreaStyle">$tagsString</textarea>
                                <input type="submit" name="update" value="Update">
                            </form>
                        </td>
                        <td>
                            <form action="" method="GET">
                                <input type="hidden" name="page" value="upload">
                                <input type="hidden" name="videoId" value="$videoToPostId">
                                <input type="hidden" name="shortsChannelId" value="$shortsChannelId">
                                <input type="text" name="youtubeVideoId" placeholder="Youtube video id">
                                <input type="submit" name="markAsUploaded" value="Mark as uploaded">
                            </form>
                            <form action="" method="GET" style="margin-top: 80px;">
                                <input type="hidden" name="page" value="unpostable">
                                <input type="hidden" name="videoId" value="$videoToPostId">
                                <input type="submit" name="markAsUnpostable" value="Mark as unpostable">
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
