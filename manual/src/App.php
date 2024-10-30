<?php

namespace PierreMiniggioManual\TiktokToShorts;

use PierreMiniggio\ConfigProvider\ConfigProvider;

class App
{
    function __construct(private string $host)
    {
    }

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
            if ($isLoggedIn) {
                $this->redirect('?page=videos');
            }
            $this->redirect('?page=login');
        }

        if ($page === 'login') {
            var_dump('loginform'); die;
        } elseif ($page === 'videos') {
            var_dump('videos'); die;
        }

        http_response_code(404);
    }

    protected function redirect(string $pageUri): void
    {
        header('Location: https://' . $this->host . $pageUri);
        exit;
    }
}
