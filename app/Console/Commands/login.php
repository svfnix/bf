<?php

namespace App\Console\Commands;

use App\Console\Commands;

class login extends Commands
{

    protected $signature = 'bml:login {user} {pass}';
    protected $description = 'Login into account';

    public function handle()
    {
        $this->loadUserClient($this->argument('user'));

        $this->fetch('http://www.bamilo.com/customer/account/logout/');
        $this->fetch('http://www.bamilo.com/customer/account/login/');
        $this->fetch('http://www.bamilo.com/customer/account/login/', [
            'YII_CSRF_TOKEN' => $this->getCsrfToken(),
            'LoginForm[email]' => $this->argument('user'),
            'LoginForm[password]' => $this->argument('pass'),
            'LoginForm[remember]' => "1"
        ], 'http://www.bamilo.com/customer/account/login/');

        $this->assert('مدیریت حساب کاربری', 'login to account');
        $this->error_die('Successfully Logged in :x  (uid: '.$this->userid.')');
    }
}
