<?php namespace professionalweb\payment;

use Illuminate\Support\ServiceProvider;
use professionalweb\payment\contracts\PayService;
use professionalweb\payment\contracts\PaymentFacade;
use professionalweb\payment\interfaces\ProdamusService;
use professionalweb\payment\drivers\prodamus\ProdamusDriver;

/**
 * Prodamus payment provider
 * @package professionalweb\payment
 */
class ProdamusProvider extends ServiceProvider
{

    public function boot(): void
    {
        app(PaymentFacade::class)->registerDriver(ProdamusService::PAYMENT_PRODAMUS, ProdamusService::class, ProdamusDriver::getOptions());
    }


    /**
     * Bind two classes
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(ProdamusService::class, function ($app) {
            return (new ProdamusDriver(config('payment.prodamus', [])));
        });
        $this->app->bind(PayService::class, function ($app) {
            return (new ProdamusDriver(config('payment.prodamus', [])));
        });
        $this->app->bind(ProdamusDriver::class, function ($app) {
            return (new ProdamusDriver(config('payment.prodamus', [])));
        });
        $this->app->bind('\professionalweb\payment\Prodamus', function ($app) {
            return (new ProdamusDriver(config('payment.prodamus', [])));
        });
    }
}