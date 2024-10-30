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

        if (empty($_SESSION['token'])) {
            $this->redirect('?page=login');
        }

        var_dump($loginApiUrl);
        echo 'test';
    }

    protected function redirect(string $pageUri): void
    {
        header('Location: https://' . $this->host . $pageUri);
        exit;
    }
}
