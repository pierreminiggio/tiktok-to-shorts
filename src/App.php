<?php

namespace PierreMiniggio\TiktokToShorts;

use Illuminate\Support\Str;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\TiktokToShorts\Connection\DatabaseConnectionFactory;
use PierreMiniggio\TiktokToShorts\Repository\LinkedChannelRepository;
use PierreMiniggio\TiktokToShorts\Repository\NonUploadedVideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\RepoToCreateRepository;
use PierreMiniggio\TiktokToShorts\Repository\RepoToDeleteRepository;

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
        $repoToCreateRepository = new RepoToCreateRepository($databaseFetcher);
        $repoToDeleteRepository = new RepoToDeleteRepository($databaseFetcher);

        $linkedChannels = $channelRepository->findAll();

        if (! $linkedChannels) {
            echo 'No linked channels';

            return $code;
        }

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.150 Safari/537.36 OPR/74.0.3911.160';

        foreach ($linkedChannels as $linkedChannel) {
            $curlAuthHeader = ['Authorization: token ' . $linkedChannel['api_token']];

            $this->deleteOldRepos(
                $linkedChannel,
                $repoToDeleteRepository,
                $curlAuthHeader,
                $userAgent
            );
            $this->createNewRepos(
                $linkedChannel,
                $nonUploadedVideoRepository,
                $curlAuthHeader,
                $userAgent,
                $repoToCreateRepository
            );
        }

        return $code;
    }

    protected function deleteOldRepos(
        array $linkedChannel,
        RepoToDeleteRepository $repoToDeleteRepository,
        array $curlAuthHeader,
        string $userAgent
    ): void
    {
        echo PHP_EOL . PHP_EOL . 'Deleting from account ' . $linkedChannel['g_id'] . '...';

        $reposToDelete = $repoToDeleteRepository->findByDeletable();

        echo PHP_EOL . count($reposToDelete) . ' repos to delete :' . PHP_EOL;

        foreach ($reposToDelete as $repoToDelete) {
            echo PHP_EOL . 'Deleting ' . $repoToDelete['url'] . ' ...';
            $curl = curl_init($repoToDelete['url']);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => $curlAuthHeader,
                CURLOPT_USERAGENT => $userAgent
            ]);
            curl_exec($curl);
            curl_close($curl);
            $repoToDeleteRepository->delete($repoToDelete['id']);
            echo PHP_EOL . $repoToDelete['url'] . ' deleted !';
        }

        echo PHP_EOL . PHP_EOL . 'Done deleting for account ' . $linkedChannel['g_id'] . ' !';
    }

    protected function createNewRepos(
        array $linkedChannel,
        NonUploadedVideoRepository $nonUploadedVideoRepository,
        array $curlAuthHeader,
        string $userAgent,
        RepoToCreateRepository $repoToCreateRepository
    ): void
    {
        echo PHP_EOL . PHP_EOL . 'Checking account ' . $linkedChannel['g_id'] . '...';

        $reposToCreate = $nonUploadedVideoRepository->findByGithubAndYoutubeChannelIds(
            $linkedChannel['g_id'],
            $linkedChannel['y_id']
        );
        echo PHP_EOL . count($reposToCreate) . ' repos to create :' . PHP_EOL;

        foreach ($reposToCreate as $repoToCreate) {
            echo PHP_EOL . 'Posting ' . $repoToCreate['title'] . ' ...';

            $sluggedTitle = substr(Str::slug($repoToCreate['title']), 0, 100);

            $curl = curl_init('https://api.github.com/user/repos');
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => json_encode([
                    'name' => $sluggedTitle,
                    'description' => 'Nouvelle video ' . $repoToCreate['url'],
                    'auto_init' => false,
                    'private' => false
                ]),
                CURLOPT_HTTPHEADER => $curlAuthHeader,
                CURLOPT_USERAGENT => $userAgent
            ]);

            $res = curl_exec($curl);
            $jsonResponse = json_decode($res, true);
            curl_close($curl);

            if (! empty($res) && ! empty($jsonResponse) && ! empty($jsonResponse['id'])) {
                $repoToCreateRepository->insertRepoIfNeeded(
                    $jsonResponse['id'],
                    $jsonResponse['url'],
                    $linkedChannel['g_id'],
                    $repoToCreate['id']
                );
                echo PHP_EOL . $repoToCreate['title'] . ' posted !';
            } else {
                echo PHP_EOL . 'Error while creating ' . $repoToCreate['title'] . ' : ' . $res;
            }
        }

        echo PHP_EOL . PHP_EOL . 'Done for account ' . $linkedChannel['g_id'] . ' !';
    }
}
