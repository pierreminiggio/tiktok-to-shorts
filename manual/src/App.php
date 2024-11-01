<?php

namespace PierreMiniggioManual\TiktokToShorts;

use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\TiktokToShorts\Connection\DatabaseConnectionFactory;
use PierreMiniggio\TiktokToShorts\Repository\LinkedChannelRepository;
use PierreMiniggio\TiktokToShorts\Repository\NonUploadedVideoRepository;
use PierreMiniggio\TiktokToShorts\Repository\VideoToPostRepository;
use PierreMiniggioManual\TiktokToShorts\Controller\DownloadVideoFileController;
use PierreMiniggioManual\TiktokToShorts\Controller\LoginFormController;
use PierreMiniggioManual\TiktokToShorts\Controller\LoginFormSubmitController;
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
                new NonUploadedVideoRepository($fetcher)
            ))();
            exit;
        } elseif ($page === 'downloadFile') {
            if (! $isLoggedIn) {
                self::redirect('?page=login');
            }

            (new DownloadVideoFileController(new VideoToPostRepository($fetcher)))();
            exit;
        } elseif ($page === 'upload') {
            if (! $isLoggedIn) {
                self::redirect('?page=login');
            }

            (new UploadFormSubmitController(new VideoToPostRepository($fetcher)))();
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
