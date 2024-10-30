<?php

namespace PierreMiniggioManual\TiktokToShorts\Controller;

class LoginFormController
{
    public function __invoke()
    {
        echo <<<HTML
            <form action="" method="GET">
                <input type="hidden" name="page" value="loginFormSubmit">
                <input type="text" name="email" placeholder="Email">
                <input type="password" name="password" placeholder="Password">
                <input type="submit" name="login" value="Login">
            </form>
        HTML;
    }
}
