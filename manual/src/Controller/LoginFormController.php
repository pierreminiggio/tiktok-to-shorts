<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

class LoginFormController
{
    public function __invoke()
    {
        $error = $_GET['error'] ?? null;

        $htmlError = $error ? <<<HTML
            <span style="color: red;">$error</span>
        HTML : '';

        echo <<<HTML
            $htmlError
            <form action="" method="GET">
                <input type="hidden" name="page" value="loginFormSubmit">
                <input type="text" name="email" placeholder="Email">
                <input type="password" name="password" placeholder="Password">
                <input type="submit" name="login" value="Login">
            </form>
        HTML;
    }
}
