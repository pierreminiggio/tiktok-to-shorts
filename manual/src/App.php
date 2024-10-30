<?php

namespace PierreMiniggioManual\TiktokToShorts;

use PierreMiniggio\ConfigProvider\ConfigProvider;

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

        if (empty($_SESSION['token'])) {
            var_dump($_SERVER['REQUEST_URI']);
        }

        var_dump($loginApiUrl);
        echo 'test';
    }
}
