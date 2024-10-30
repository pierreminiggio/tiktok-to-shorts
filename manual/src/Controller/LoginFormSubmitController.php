<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

use Exception;
use PierreMiniggioManual\TiktokToShorts\App;

class LoginFormSubmitController
{
    public function __construct(private string $loginApiUrl)
    {
    }
    public function __invoke()
    {
        $email = $_GET['email'] ?? null;
        $password = $_GET['password'] ?? null;

        if (! $email || ! $password) {
            App::redirect('?page=login');
        }

        $loginCurl = curl_init($this->loginApiUrl . '/api/auth/login');
        curl_setopt_array($loginCurl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'email' => $email,
                'password' => $password
            ])
        ]);

        $loginCurlResult = curl_exec($loginCurl);
        curl_close($loginCurl);

        if ($loginCurlResult === false) {
            $curlError = curl_error($loginCurl);
            throw new Exception($curlError);
        }

        $jsonResponse = json_decode($loginCurlResult, true);

        if (! empty($jsonResponse['error'])) {
            App::redirect('?page=login&error=' . $jsonResponse['error']);
        }

        if (empty($jsonResponse['token'])) {
            throw new Exception('No token, json response : ' . json_encode($jsonResponse));
        }

        $_SESSION['token'] = $jsonResponse['token'];
        $_SESSION['email'] = $jsonResponse['email'];
        $_SESSION['name'] = $jsonResponse['name'];
        $_SESSION['first_name'] = $jsonResponse['first_name'];

        App::redirect('?page=videos');
    }
}
