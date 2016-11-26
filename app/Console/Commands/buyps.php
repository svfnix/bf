<?php

namespace App\Console\Commands;

use App\Console\Commands;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;

class buyps extends Commands
{
    protected $signature = 'bml:buyps';
    protected $description = 'Buy a product';

    public function handle()
    {
        $uname = 'masoudvf@mizbanan.com';
        $sku = 'SO298CD11TOUKNAFAMZ';


        $this->loadUserClient($uname);

        $this->fetch('http://www.bamilo.com/harajome/?source=sld1');
        $this->assert($uname, 'check user logged in');

        // Load link
        $link = null; $count = 0;
        while(empty($link)) {
            $crawler = new Crawler();
            $crawler->addContent($this->getResult());
            try {
                $link = $crawler->filter('button[data-sku="'.$sku.'"]')->first()->attr('data-js-uri');
            } catch (InvalidArgumentException $e){
                echo "(" . $count++ . ") Out of stock. waiting ...\n";
                sleep(1);
                $this->fetch('http://www.bamilo.com/harajome/?source=sld1');
            }
        }

        // Add to shopping cart
        $this->fetch($link, [
            'YII_CSRF_TOKEN' => $this->getCsrfToken()
        ], 'http://www.bamilo.com/harajome/?source=sld1', 1);
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
        file_put_contents('buyps.html', $this->getResult());
        $this->assert('از خرید شما متشکریم', 'order successfully sent');

    }
}
