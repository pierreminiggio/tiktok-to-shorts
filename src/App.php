<?php

namespace PierreMiniggio\TiktokToShorts;

use Exception;
use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\GoogleTokenRefresher\AccessTokenProvider;
use PierreMiniggio\GoogleTokenRefresher\AuthException;
use PierreMiniggio\GoogleTokenRefresher\GoogleClient;
use PierreMiniggio\HeropostAndYoutubeAPIBasedVideoPoster\Video;
use PierreMiniggio\HeropostAndYoutubeAPIBasedVideoPoster\VideoPosterFactory;
use PierreMiniggio\HeropostYoutubePosting\YoutubeCategoriesEnum;
use PierreMiniggio\HeropostYoutubePosting\YoutubeVideo;
use PierreMiniggio\MultiSourcesTiktokDownloader\MultiSourcesTiktokDownloader;
use PierreMiniggio\MultiSourcesTiktokDownloader\Repository;
use PierreMiniggio\TiktokToShorts\Connection\DatabaseConnectionFactory;
use PierreMiniggio\TiktokToShorts\Repository\LinkedChannelRepository;
use PierreMiniggio\TiktokToShorts\Repository\NonUploadedVideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\ShortsValueForTikTokVideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\VideoToPostRepository;
use PierreMiniggio\TiktokToShorts\Service\VideoDownloader;
use PierreMiniggio\TiktokToShorts\Service\VideoInfoBuilder;
use PierreMiniggio\VideoRenderForTiktokVideoChecker\VideoRenderForTiktokVideoChecker;
use PierreMiniggio\YoutubeVideoUpdater\Exception\BadVideoIdException;
use PierreMiniggio\YoutubeVideoUpdater\VideoUpdater;

class App
{

    public function run(): int
    {
        $code = 0;

        $projectDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

        $configProvider = new ConfigProvider($projectDirectory);
        $config = $configProvider->get();

        if (empty($config['db'])) {
            echo 'No DB config';

            return $code;
        }

        $cacheDir = $projectDirectory . 'cache';
        if (! file_exists($cacheDir)) {
            mkdir($cacheDir);
        }

        $apiConfig = $config['api'];
        $apiUrl = $apiConfig['url'];
        $apiToken = $apiConfig['token'];
        $cacheUrl = $config['cache_url'];
        $snapTikApiActionConfig = $config['snap_tik_api_action'] ?? null;

        $downloader = MultiSourcesTiktokDownloader::buildSelf(
            $snapTikApiActionConfig ? new Repository(...$snapTikApiActionConfig) : null
        );
        
        $spinnerApiConfig = $config['spinner_api'];
        $spinnerApiUrl = $spinnerApiConfig !== null ? $spinnerApiConfig['url'] : null;
        $spinnerApiToken = $spinnerApiConfig !== null ? $spinnerApiConfig['token'] : null;

        $databaseFetcher = new DatabaseFetcher((new DatabaseConnectionFactory())->makeFromConfig($config['db']));
        $channelRepository = new LinkedChannelRepository($databaseFetcher);
        $nonUploadedVideoRepository = new NonUploadedVideoRepository($databaseFetcher);
        $videoToPostRepository = new VideoToPostRepository($databaseFetcher);

        $linkedChannels = $channelRepository->findAll();

        if (! $linkedChannels) {
            echo 'No linked channels';

            return $code;
        }

        /** @var string[] */
        $alreadyPostedChannelIds = [];

        foreach ($linkedChannels as $linkedChannel) {
            $shortsChannelId = $linkedChannel['s_id'];

            echo PHP_EOL . PHP_EOL . 'Checking channel ' . $shortsChannelId . '...';

            if (in_array($shortsChannelId, $alreadyPostedChannelIds)) {
                echo ' Already posted to this channel right now !';
                continue;
            }

            $videosToPost = $nonUploadedVideoRepository->findByShortsAndTiktokChannelIds(
                $shortsChannelId,
                $linkedChannel['t_id'],
                1
            );
            echo PHP_EOL . count($videosToPost) . ' videos to post :' . PHP_EOL;

            $videoInfoBuilder = new VideoInfoBuilder(new ShortsValueForTikTokVideoRepository($databaseFetcher));
            foreach ($videosToPost as $videoToPost) {
                $videoToPostId = $videoToPost['id'];
                $videoInfos = $videoInfoBuilder->getVideoInfos(
                    $videoToPostId,
                    $videoToPost['legend'] ?? null,
                    $linkedChannel['description']
                );
                $legend = $videoInfos->legend;
                $title = $videoInfos->title;
                $description = $videoInfos->description;
                $tags = $videoInfos->tags;

                echo PHP_EOL . 'Posting TikTok ' . $videoToPostId . ' : ' . $legend . ' ...';

                $videoFile = $cacheDir . DIRECTORY_SEPARATOR . $videoToPostId . '.mp4';
                $videoToPostUrl = $videoToPost['url'];

                $posted = false;

                // Try heropost first
                $poster = (new VideoPosterFactory())->make(new Logger());

                try {
                    $this->downloadVideoFileIfNeeded($downloader, $videoToPostUrl, $videoFile);
                } catch (Exception $e) {
                    echo PHP_EOL . PHP_EOL . 'Error : Downloading file failed : ' . $e->getMessage();
                    break;
                }

                if (filesize($videoFile) === 0) {
                    echo PHP_EOL . PHP_EOL . 'Error : Video file is empty !';
                    continue;
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
                    $posted = true;
                } catch (Exception $e) {
                    echo
                        PHP_EOL
                        . 'Error while uploading '
                        . $legend
                        . ' through Heropost : '
                        . $e->getMessage()
                    ;
                }
                
                // if failed, try Scraping
                if (! $posted) {
                    echo PHP_EOL . 'Trying Scraping...';
                    $videoUrl = $spinnerApiUrl === null || $spinnerApiToken === null
                        ? null
                        : (new VideoRenderForTiktokVideoChecker($spinnerApiUrl, $spinnerApiToken))
                            ->getRenderedVideoUrl($videoToPostUrl)
                    ;
                    
                    if ($videoUrl === null) {
                        try {
                            $this->downloadVideoFileIfNeeded($downloader, $videoToPostUrl, $videoFile);
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
                            'title' => 'Shorts video ' . $videoToPostId,
                            'description' => 'Super Shorts Description'
                        ])
                    ]);

                    $response = curl_exec($curl);
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);

                    if ($httpCode !== 200) {
                        $errorMessage = $response;
                        
                        if ($httpCode === 503) {
                            $errorMessage = 'Feature not available at the moment, please try again later';
                        }
                        
                        echo 'Posting failed : ' . $errorMessage;
                        
                        if (str_contains($response, 'TimeoutError')) {
                            $alreadyPostedChannelIds[] = $shortsChannelId;
                        }
                            
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
                }

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

                $videoToPostRepository->insertVideoIfNeeded(
                    $youtubeId,
                    $shortsChannelId,
                    $videoToPostId
                );
                
                if (file_exists($videoFile)) {
                    unlink($videoFile);
                }

                echo PHP_EOL . $legend . ' posted !';
                $alreadyPostedChannelIds[] = $shortsChannelId;
                break;
            }

            echo PHP_EOL . PHP_EOL . 'Done for channel ' . $shortsChannelId . ' !';
        }

        return $code;
    }
    
    /**
     * @throws Exception
     */
    protected function downloadVideoFileIfNeeded(
        MultiSourcesTiktokDownloader $downloader,
        string $videoToPostUrl,
        string $videoFile
    ): void
    {
        if (file_exists($videoFile)) {
            return;
        }

        $videoDownloader = new VideoDownloader($downloader);
        $videoDownloader->download($videoFile, $videoToPostUrl);
    }
}
