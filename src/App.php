<?php

namespace PierreMiniggio\TiktokToShorts;

use Exception;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\GoogleTokenRefresher\AccessTokenProvider;
use PierreMiniggio\GoogleTokenRefresher\AuthException;
use PierreMiniggio\GoogleTokenRefresher\GoogleClient;
use PierreMiniggio\HeropostAndYoutubeAPIBasedVideoPoster\Video;
use PierreMiniggio\HeropostAndYoutubeAPIBasedVideoPoster\VideoPosterFactory;
use PierreMiniggio\HeropostYoutubePosting\YoutubeCategoriesEnum;
use PierreMiniggio\HeropostYoutubePosting\YoutubeVideo;
use PierreMiniggio\TikTokDownloader\Downloader;
use PierreMiniggio\TikTokDownloader\DownloadFailedException;
use PierreMiniggio\TiktokToShorts\Connection\DatabaseConnectionFactory;
use PierreMiniggio\TiktokToShorts\Repository\LinkedChannelRepository;
use PierreMiniggio\TiktokToShorts\Repository\NonUploadedVideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\VideoToPostRepository;
use PierreMiniggio\YoutubeVideoUpdater\Exception\BadVideoIdException;
use PierreMiniggio\YoutubeVideoUpdater\VideoUpdater;

class App
{

    public function run(): int
    {

        $postStrategy = UploadStrategyEnum::SCRAPING;

        $code = 0;

        $config = require(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php');

        if (empty($config['db'])) {
            echo 'No DB config';

            return $code;
        }

        $cacheDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'cache';
        if (! file_exists($cacheDir)) {
            mkdir($cacheDir);
        }

        $apiConfig = $config['api'];
        $apiUrl = $apiConfig['url'];
        $apiToken = $apiConfig['token'];
        
        $cacheUrl = $config['cache_url'];
        
        $spinnerApiConfig = $config['spinner_api'];
        $spinnerApiUrl = $spinnerApiConfig !== null ? $spinnerApiConfig['url'] : null;
        $spinnerApiToken = $spinnerApiConfig !== null ? $spinnerApiConfig['token'] : null;

        $databaseFetcher = new DatabaseFetcher((new DatabaseConnectionFactory())->makeFromConfig($config['db']));
        $channelRepository = new LinkedChannelRepository($databaseFetcher);
        $nonUploadedVideoRepository = new NonUploadedVideoRepository($databaseFetcher);
        $downloader = new Downloader();
        $youtubeMaxTitleLength = 100;
        $videoToPostRepository = new VideoToPostRepository($databaseFetcher);

        $linkedChannels = $channelRepository->findAll();

        if (! $linkedChannels) {
            echo 'No linked channels';

            return $code;
        }

        foreach ($linkedChannels as $linkedChannel) {

            $shortsChannelId = $linkedChannel['s_id'];
            echo PHP_EOL . PHP_EOL . 'Checking channel ' . $shortsChannelId . '...';

            $videosToPost = $nonUploadedVideoRepository->findByShortsAndTiktokChannelIds(
                $shortsChannelId,
                $linkedChannel['t_id']
            );
            echo PHP_EOL . count($videosToPost) . ' videos to post :' . PHP_EOL;

            foreach ($videosToPost as $videoToPost) {
                $legend = $videoToPost['legend'];
                echo PHP_EOL . 'Posting ' . $legend . ' ...';

                $videoToPostId = $videoToPost['id'];
                $videoFile = $cacheDir . DIRECTORY_SEPARATOR . $videoToPostId . '.mp4';
                $videoToPostUrl = $videoToPost['url'];

                if (strlen($legend) <= $youtubeMaxTitleLength) {
                    $title = $legend;
                } else {
                    $legendWords = explode(' ', $legend);
                    if (count($legendWords) === 1) {
                        $title = substr($legend, 0, $youtubeMaxTitleLength);
                    } else {
                        $title = 'Youtube Shorts video';
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
                    $linkedChannel['description']
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

                if ($postStrategy === UploadStrategyEnum::HEROPOST) {
                    $poster = (new VideoPosterFactory())->make(new Logger());

                    try {
                        $this->downloadVideoFileIfNeeded($downloader, $videoToPostUrl, $videoFile, $legend);
                    } catch (Exception) {
                        break;
                    }
                    
                    try {
                        $youtubeId = $poster->post(
                            $linkedChannel['heropost_login'],
                            $linkedChannel['heropost_password'],
                            $linkedChannel['youtube_id'],
                            new Video(
                                new YoutubeVideo(
                                    $title,
                                    $description,
                                    YoutubeCategoriesEnum::EDUCATION
                                ),
                                $tags,
                                false,
                                $videoFile
                            ),
                            new GoogleClient(
                                $linkedChannel['google_client_id'],
                                $linkedChannel['google_client_secret'],
                                $linkedChannel['google_refresh_token']
                            )
                        );
                    } catch (Exception $e) {
                        echo
                            PHP_EOL
                            . 'Error while uploading '
                            . $legend
                            . ' : '
                            . $e->getMessage()
                        ;
                        break;
                    }
                } elseif ($postStrategy === UploadStrategyEnum::SCRAPING) {
                    $videoUrl = $this->getRenderedVideoUrl($videoToPostUrl, $spinnerApiUrl, $spinnerApiToken);
                    
                    if ($videoUrl === null) {
                        try {
                            $this->downloadVideoFileIfNeeded($downloader, $videoToPostUrl, $videoFile, $legend);
                        } catch (Exception) {
                            break;
                        }

                        $explodedVideoFilePath = explode(DIRECTORY_SEPARATOR, $videoFile);
                        $fileName = $explodedVideoFilePath[count($explodedVideoFilePath) - 1];
                        $videoUrl = $cacheUrl . '/' . $fileName;
                    }

                    $curl = curl_init($apiUrl . '/' . $linkedChannel['youtube_id']);

                    $authHeader = ['Content-Type: application/json' , 'Authorization: Bearer ' . $apiToken];
                    curl_setopt_array($curl, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => $authHeader,
                        CURLOPT_POST => 1,
                        CURLOPT_POSTFIELDS => json_encode([
                            'video_url' => $videoUrl,
                            'title' => $title,
                            'description' => $description
                        ])
                    ]);

                    $response = curl_exec($curl);
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);

                    if ($httpCode !== 200) {
                        echo 'Posting failed : ' . $response;
                        continue;
                    }

                    if (! $response) {
                        echo 'API returned an empty response';
                        continue;
                    }

                    $jsonResponse = json_decode($response, true);

                    if (! $jsonResponse) {
                        echo 'API returned a bad json response : ' . $response;
                        continue;
                    }

                    if (empty($jsonResponse['id'])) {
                        echo 'API returned a bad json response, "id" missing : ' . $jsonResponse;
                        continue;
                    }

                    $youtubeId = $jsonResponse['id'];

                    $tokenProvider = new AccessTokenProvider();
                    try {
                        $accessToken = $tokenProvider->get(
                            $linkedChannel['google_client_id'],
                            $linkedChannel['google_client_secret'],
                            $linkedChannel['google_refresh_token']
                        );
                    } catch (AuthException $e) {
                        echo $e->getMessage();
                        // what ever
                    }

                    if (isset($accessToken)) {
                        $videoUpdater = new VideoUpdater();
                        try {
                            $videoUpdater->update(
                                $accessToken,
                                $youtubeId,
                                $title,
                                $description,
                                $tags,
                                YoutubeCategoriesEnum::EDUCATION,
                                false
                            );
                        } catch (BadVideoIdException $e) {
                            echo $e->getMessage();
                            // what ever
                        }
                    }
                }

                $videoToPostRepository->insertVideoIfNeeded(
                    $youtubeId,
                    $shortsChannelId,
                    $videoToPostId
                );
                
                if (file_exists($videoFile)) {
                    unlink($videoFile);
                }

                echo PHP_EOL . $legend . ' posted !';
            }

            echo PHP_EOL . PHP_EOL . 'Done for channel ' . $shortsChannelId . ' !';
        }

        return $code;
    }
    
    /**
     * @throws Exception
     */
    protected function downloadVideoFileIfNeeded(Downloader $downloader, string $videoToPostUrl, string $videoFile, string $legend): void
    {
        if (! file_exists($videoFile)) {
            try {
                $downloader->downloadWithoutWatermark(
                    $videoToPostUrl,
                    $videoFile
                );
            } catch (DownloadFailedException $e) {
                echo PHP_EOL . 'Error while downloading ' . $legend . ' : ' . $e->getMessage();
                throw new Exception('Download failed');
            }
        }
    }
    
    protected function getRenderedVideoUrl(string $videoToPostUrl, ?string $spinnerApiUrl, ?string $spinnerApiToken): ?string
    {
        if ($spinnerApiUrl === null || $spinnerApiToken === null) {
            return null;
        }
        
        $curl = curl_init($spinnerApiUrl . '/tiktok-video-file');

        $authHeader = ['Content-Type: application/json' , 'Authorization: Bearer ' . $spinnerApiToken];
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $authHeader,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $videoToPostUrl
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode !== 200) {
            return null;
        }

        if (! $response) {
            return null;
        }

        $jsonResponse = json_decode($response, true);

        if (! $jsonResponse) {
            return null;
        }

        if (! isset($jsonResponse['id']) || ! isset($jsonResponse['hasRenderedFile'])) {
            return null;
        }
        
        if ($jsonResponse['hasRenderedFile'] === false) {
            return null;
        }
        
        return $spinnerApiUrl . '/render/' . $jsonResponse['id'];
    }
}
