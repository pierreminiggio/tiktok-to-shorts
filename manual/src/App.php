<?php

namespace PierreMiniggioManual\TiktokToShorts;

use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\MultiSourcesTiktokDownloader\MultiSourcesTiktokDownloader;
use PierreMiniggio\MultiSourcesTiktokDownloader\Repository;
use PierreMiniggio\TiktokToShorts\Connection\DatabaseConnectionFactory;
use PierreMiniggio\TiktokToShorts\Repository\LinkedChannelRepository;
use PierreMiniggio\TiktokToShorts\Repository\NonUploadedVideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\ShortsValueForTikTokVideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\UnpostableTikTokVideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\VideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\VideoToPostRepository;
use PierreMiniggio\TiktokToShorts\Service\VideoDownloader;
use PierreMiniggio\TiktokToShorts\Service\VideoInfoBuilder;
use PierreMiniggioManual\TiktokToShorts\Controller\DownloadVideoFileController;
use PierreMiniggioManual\TiktokToShorts\Controller\LoginFormController;
use PierreMiniggioManual\TiktokToShorts\Controller\LoginFormSubmitController;
use PierreMiniggioManual\TiktokToShorts\Controller\UnpostableFormSubmitController;
use PierreMiniggioManual\TiktokToShorts\Controller\UpdateValueFormSubmitController;
use PierreMiniggioManual\TiktokToShorts\Controller\UploadFormSubmitController;
use PierreMiniggioManual\TiktokToShorts\Controller\VideoListController;

class App
{

    public function run(): void
    {
        $projectFolder = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
        $configProvider = new ConfigProvider($projectFolder);
        $config =  $configProvider->get();

        $loginApiUrl = $config['login_api_url'] ?? null;

        if (! $loginApiUrl) {
            http_response_code(500);
            echo json_encode(['message' => 'Missing Login API URL']);

            return;
        }

        $cacheUrl = $config['cache_url'] ?? null;

        if (! $cacheUrl) {
            http_response_code(500);
            echo json_encode(['message' => 'Missing cache URL']);

            return;
        }

        $cacheFolder = $projectFolder . 'cache' . DIRECTORY_SEPARATOR;

        $fetcher = new DatabaseFetcher((new DatabaseConnectionFactory())->makeFromConfig($config['db']));

        $page = $_GET['page'] ?? null;

        $isLoggedIn = ! empty($_SESSION['token']);

        if (! $page) {
            self::redirect('?page=videos');
        }

        if ($page === 'login') {
            if ($isLoggedIn) {
                self::redirect('?page=videos');
            }
            (new LoginFormController())();
            exit;
        } elseif ($page === 'loginFormSubmit') {
            if ($isLoggedIn) {
                self::redirect('?page=videos');
            }
            (new LoginFormSubmitController($loginApiUrl))();
            exit;
        } elseif ($page === 'logout') {
            if (! $isLoggedIn) {
                self::redirect('?page=login');
            }

            unset($_SESSION['token']);
            unset($_SESSION['email']);
            unset($_SESSION['name']);
            unset($_SESSION['first_name']);
            self::redirect('?page=login');
        } elseif ($page === 'videos') {
            if (! $isLoggedIn) {
                self::redirect('?page=login');
            }
            (new VideoListController(
                $cacheUrl,
                $cacheFolder,
                new LinkedChannelRepository($fetcher),
                new NonUploadedVideoRepository($fetcher),
                new VideoInfoBuilder(new ShortsValueForTikTokVideoRepository($fetcher))
            ))();
            exit;
        } elseif ($page === 'updateValue') {
            if (! $isLoggedIn) {
                self::redirect('?page=login');
            }
            
            (new UpdateValueFormSubmitController(
                new VideoRepository($fetcher),
                new ShortsValueForTikTokVideoRepository($fetcher)
            ))();
            exit;
        } elseif ($page === 'downloadFile') {
            if (! $isLoggedIn) {
                self::redirect('?page=login');
            }

            $snapTikApiActionConfig = $config['snap_tik_api_action'] ?? null;

            $downloader = MultiSourcesTiktokDownloader::buildSelf(
                $snapTikApiActionConfig ? new Repository(...$snapTikApiActionConfig) : null
            );

            (new DownloadVideoFileController(
                $cacheFolder,
                new VideoRepository($fetcher),
                new VideoDownloader($downloader)
            ))();
            exit;
        } elseif ($page === 'upload') {
            if (! $isLoggedIn) {
                self::redirect('?page=login');
            }
            
            (new UploadFormSubmitController(
                $cacheFolder,
                new VideoToPostRepository($fetcher)
            ))();
            exit;
        } elseif ($page === 'unpostable') {
            if (! $isLoggedIn) {
                self::redirect('?page=login');
            }

            (new UnpostableFormSubmitController(
                new VideoRepository($fetcher),
                new UnpostableTikTokVideoRepository($fetcher)
            ))();
            exit;
        }

        http_response_code(404);
    }

    public static function redirect(string $pageUri): void
    {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $pageUri);
        exit;
    }
}
