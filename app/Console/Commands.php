<?php

namespace App\Console;


use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class Commands extends Command
{
    protected $client;
    protected $jar;
    protected $username;
    protected $userid;
    protected $headers;
    protected $cookies;
    protected $result;

    public function error_die($message){
        die("{$message}\n");
    }

    public function assert($needed, $message){
        if(!strpos($this->getResult(), $needed)){
            $this->error_die("Failed: $message");
        } else {
            echo "Success: {$message}\n";
        }
    }

    public function loadUserClient($uname){

        $this->client = new Client();
        $this->jar = new FileCookieJar('storage/sessions/' . $uname . '.jar');

        $cookies = $this->jar->toArray();
        foreach($cookies as $cookie){
            $this->cookies[$cookie['Name']] = $cookie['Value'];
        }

        $this->username = $uname;
        $this->userid = isset($this->cookies['userId']) ? $this->cookies['userId'] : 0;

        $this->result = null;
    }

    public function parseHeaders(){
        foreach ($this->result->getHeaders() as $name => $values) {
            $this->headers[$name] = implode(', ', $values);
        }
    }

    public function getResult(){
        return $this->result->getBody();
    }

    public function getCsrfToken(){
        if(preg_match('/\"csrf":{"tokenName":"YII_CSRF_TOKEN","tokenValue":"([^"]+)"}/si', $this->getResult(), $matchs)) {
            return $matchs[1];
        }

        $this->error_die("CSRFToken not found!");
    }

    public function getSku($html){

        $this->error_die("CSRFToken not found!");
    }

    public function fetch($url, $data=[], $referer = 'http://www.bamilo.com', $ajax = false){

        $args = [
            'allow_redirects' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:47.0) Gecko/20100101 Firefox/47.0',
                'Accept' => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                'Referer' => $referer,
            ],
            'cookies' => $this->jar
        ];

        if(count($data)){
            $args['form_params'] = $data;
        }

        if($ajax){
            $args['headers']['X-Requested-With'] = "XMLHttpRequest";
        }

        $this->result = $this->client->request(count($data) ? 'POST' : 'GET', $url, $args);
        $this->parseHeaders();
        return $this->result->getBody();
    }
}