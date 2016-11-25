<?php

namespace App\Console\Commands;

use App\Console\Commands;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;

class buy extends Commands
{
    protected $signature = 'bml:buy {user} {link}';
    protected $description = 'Buy a product';

    public function handle()
    {
        $this->loadUserClient($this->argument('user'));

        // Load link
        $this->fetch($this->argument('link'));
        $this->assert($this->argument('user'), 'check user logged in');

        // Add to shopping cart
        $url = null; $count = 0;
        while(empty($url)) {
            $crawler = new Crawler();
            $crawler->addContent($this->getResult());
            try {
                $url = $crawler->filter('.sku-detail .details-wrapper .details-footer .js-add_cart_tracking')->first()->attr('data-js-uri');
            } catch (InvalidArgumentException $e){
                echo "(" . $count++ . ") Out of stock. waiting ...\n";
                sleep(1);
                $this->fetch($this->argument('link'));
            }
        }

        $this->fetch($url, [
            'YII_CSRF_TOKEN' => $this->getCsrfToken()
        ], $this->argument('link'), 1);
        $this->assert('success', 'adding to cart');

        // Set address
        $this->fetch('http://www.bamilo.com/onepagecheckout/address/index/');

        $crawler = new Crawler();
        $crawler->addContent($this->getResult());

        $address_id = $crawler->filter('.ft-use-address-button')->first()->attr('data-id');
        $this->fetch('http://www.bamilo.com/onepagecheckout/address/save/', [
            'YII_CSRF_TOKEN' => $this->getCsrfToken(),
            'billingAsShipping' => "1",
            'selectedIdBillingAddress' => $address_id,
            'selectedIdShippingAddress' => $address_id,
            'newShippingAddress' => "false",
            'newBillingAddress' => "false"
        ], 'http://www.bamilo.com/onepagecheckout/address/index/');
        $this->assert('جزئیات تحویل سفارش', 'address choosed');

        // Set shipping type
        $this->fetch('http://www.bamilo.com/onepagecheckout/shipping/save/', [
            'YII_CSRF_TOKEN' => $this->getCsrfToken(),
            'ShippingMethodForm[shipping_method]' => "UniversalShippingMatrix"
        ], 'http://www.bamilo.com/onepagecheckout/address/index/');
        $this->assert('پرداخت در محل', 'delivery type choosed');

        // Set payment type
        /*$this->fetch('http://www.bamilo.com/onepagecheckout/payment/save/', [
            'YII_CSRF_TOKEN' => $this->getCsrfToken(),
            'PaymentOptionsForm[payment_option]' => "1",
            'couponcode' => ""
        ], 'http://www.bamilo.com/onepagecheckout/shipping/save/');*/

        // Log order
        file_put_contents('index.html', $this->getResult());
        $this->assert('از خرید شما متشکریم', 'order successfully sent');

    }
}
