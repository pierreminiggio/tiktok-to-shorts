<?php

namespace PierreMiniggio\TiktokToShorts;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\TikTokDownloader\Downloader;
use PierreMiniggio\TikTokDownloader\DownloadFailedException;
use PierreMiniggio\TiktokToShorts\Connection\DatabaseConnectionFactory;
use PierreMiniggio\TiktokToShorts\Repository\LinkedChannelRepository;
use PierreMiniggio\TiktokToShorts\Repository\NonUploadedVideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\VideoToCreateRepository;

class App
{

    public function run(): int
    {

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

        $databaseFetcher = new DatabaseFetcher((new DatabaseConnectionFactory())->makeFromConfig($config['db']));
        $channelRepository = new LinkedChannelRepository($databaseFetcher);
        $nonUploadedVideoRepository = new NonUploadedVideoRepository($databaseFetcher);
        $downloader = new Downloader();
        $youtubeMaxTitleLength = 100;
        //$videoToCreateRepository = new VideoToCreateRepository($databaseFetcher);

        $linkedChannels = $channelRepository->findAll();

        if (! $linkedChannels) {
            echo 'No linked channels';

            return $code;
        }

        foreach ($linkedChannels as $linkedChannel) {

            echo PHP_EOL . PHP_EOL . 'Checking channel ' . $linkedChannel['s_id'] . '...';

            $videosToCreate = $nonUploadedVideoRepository->findByShortsAndTiktokChannelIds(
                $linkedChannel['s_id'],
                $linkedChannel['t_id']
            );
            echo PHP_EOL . count($videosToCreate) . ' videos to create :' . PHP_EOL;

            foreach ($videosToCreate as $videoToCreate) {
                $legend = $videoToCreate['legend'];
                echo PHP_EOL . 'Posting ' . $legend . ' ...';

                $videoToCreateId = $videoToCreate['id'];

                $videoFile = $cacheDir . DIRECTORY_SEPARATOR . $videoToCreateId . '.mp4';

                if (! file_exists($videoFile)) {
                    try {
                        $downloader->downloadWithoutWatermark(
                            $videoToCreate['url'],
                            $videoFile
                        );
                    } catch (DownloadFailedException $e) {
                        echo PHP_EOL . 'Error while downloading ' . $legend . ' : ' . $e->getMessage();
                        break;
                    }
                    
                }

                if (strlen($legend) <= 100) {
                    $title = $legend;
                } else {
                    $legendWords = explode(' ', $legend);
                    if (count($legendWords) === 1) {
                        $title = substr($legend, 0, 100);
                    } else {
                        $title = 'Youtube Shorts video';
                        $wipTitle = '';

                        foreach ($legendWords as $legendWordIndex => $legendWord) {
                            $newWipTitle = $wipTitle;

                            if ($legendWordIndex > 0) {
                                $newWipTitle .= ' ';
                            }

                            $newWipTitle .= $legendWord;

                            if (strlen($newWipTitle) > 100) {
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
                var_dump($description);
                die;

                if (false) {
                    // $videoToCreateRepository->insertVideoIfNeeded(
                    //     $jsonResponse['id'],
                    //     $jsonResponse['url'],
                    //     $linkedChannel['s_id'],
                    //     $videoToCreate['id']
                    // );
                    echo PHP_EOL . $legend . ' posted !';
                } else {
                    echo PHP_EOL . 'Error while creating ' . $legend . ' : ' . $res;
                }
            }

            echo PHP_EOL . PHP_EOL . 'Done for channel ' . $linkedChannel['s_id'] . ' !';
        }

        return $code;
    }
}
