<?php

namespace PierreMiniggio\TiktokToShorts;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
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

        $databaseFetcher = new DatabaseFetcher((new DatabaseConnectionFactory())->makeFromConfig($config['db']));
        $channelRepository = new LinkedChannelRepository($databaseFetcher);
        $nonUploadedVideoRepository = new NonUploadedVideoRepository($databaseFetcher);
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
            var_dump($videosToCreate); die;
            echo PHP_EOL . count($videosToCreate) . ' videos to create :' . PHP_EOL;

            foreach ($videosToCreate as $videoToCreate) {
                echo PHP_EOL . 'Posting ' . $videoToCreate['title'] . ' ...';


                if (false) {
                    // $videoToCreateRepository->insertVideoIfNeeded(
                    //     $jsonResponse['id'],
                    //     $jsonResponse['url'],
                    //     $linkedChannel['s_id'],
                    //     $videoToCreate['id']
                    // );
                    echo PHP_EOL . $videoToCreate['title'] . ' posted !';
                } else {
                    echo PHP_EOL . 'Error while creating ' . $videoToCreate['title'] . ' : ' . $res;
                }
            }

            echo PHP_EOL . PHP_EOL . 'Done for channel ' . $linkedChannel['s_id'] . ' !';
        }

        return $code;
    }
}
