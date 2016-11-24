<?php

namespace App\Console\Commands;

use App\Console\Commands;
use Symfony\Component\DomCrawler\Crawler;

class buy extends Commands
{
    protected $signature = 'bml:buy {user} {link}';
    protected $description = 'Buy a product';

    public function handle()
    {
        $this->loadUserClient($this->argument('user'));
        $this->fetch($this->argument('link'));
        $this->assert($this->argument('user'), 'check user logged in');

        $crawler = new Crawler();
        $crawler->addContent($this->getResult());
        $url = $crawler->filter('.sku-detail .details-wrapper .details-footer .js-add_cart_tracking')->first()->attr('data-js-uri');
        $sku = $crawler->filter('.sku-detail .details-wrapper .details-footer .js-add_cart_tracking')->first()->attr('data-sku');

        $this->fetch($url, [
            'YII_CSRF_TOKEN' => $this->getCsrfToken()
        ], $this->argument('link'), 1);
        $this->assert('success', 'adding to cart');

        $this->fetch('http://www.bamilo.com/ajax/cart/getpagedata/action/overlay/');
        $this->fetch('http://www.bamilo.com/cart/');

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

        $this->fetch('http://www.bamilo.com/onepagecheckout/shipping/save/', [
            'YII_CSRF_TOKEN' => $this->getCsrfToken(),
            'ShippingMethodForm[shipping_method]' => "UniversalShippingMatrix"
        ], 'http://www.bamilo.com/onepagecheckout/address/index/');
        $this->assert('مامور پست', 'delivery type choosed');

        $this->fetch('http://www.bamilo.com/onepagecheckout/shipping/save/', [
            'YII_CSRF_TOKEN' => $this->getCsrfToken(),
            'PaymentOptionsForm[payment_option]' => "1",
            'couponcode' => ""
        ], 'http://www.bamilo.com/onepagecheckout/address/index/');
        die($this->getResult());

        $this->assert('shippingAddress', 'delivery type choosed');
        die('order successfully sent');

    }
}
