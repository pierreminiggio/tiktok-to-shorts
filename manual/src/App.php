<?php

namespace PierreMiniggioManual\TiktokToShorts;

use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggioManual\TiktokToShorts\Controller\LoginFormController;
use PierreMiniggioManual\TiktokToShorts\Controller\LoginFormSubmitController;

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
        } elseif ($page === 'videos') {
            if (! $isLoggedIn) {
                self::redirect('?page=login');
            }
            var_dump('videos'); die;
        }

        http_response_code(404);
    }

    public static function redirect(string $pageUri): void
    {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $pageUri);
        exit;
    }
}
