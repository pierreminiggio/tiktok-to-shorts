<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

use PierreMiniggioManual\TiktokToShorts\App;

class LoginFormSubmitController
{
    public function __invoke()
    {
        $email = $_GET['email'] ?? null;
        $password = $_GET['password'] ?? null;

        if (! $email || ! $password) {
            App::redirect('?page=login');
        }

        var_dump($email); var_dump($password);

        echo <<<HTML
            Login form submit controller
        HTML;
    }
}
