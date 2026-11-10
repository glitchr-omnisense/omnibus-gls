<?php

namespace Omnibus\Gls;

use Omnibus\Config;
use Omnibus\GatewayFactory;
use Omnibus\Gls\Action\CancelAction;
use Omnibus\Gls\Action\ShippingAction;
use Omnibus\Gls\Action\TrackingAction;
use Symfony\Component\HttpClient\HttpClient;

/**
 *   options:
 *     username: '%env(GLS_USERNAME)%'       # the ShipIT web API login
 *     password: '%env(GLS_PASSWORD)%'
 *     contact_id: '%env(GLS_CONTACT_ID)%'   # the customer's contact id at GLS
 *     sandbox: true
 *     rates: [...]                          # prices from configuration: ShipIT quotes none
 *
 * Pickup points (ParcelShops) are not offered by ShipIT: GLS's finder is a separate contract.
 */
final class GlsGatewayFactory extends GatewayFactory
{
    protected function populateConfig(Config $config): void
    {
        $config->defaults([
            'omnibus.factory_name' => 'gls',
            'omnibus.factory_title' => 'GLS',
            'omnibus.policy' => __DIR__.'/policy.json',
            'omnibus.required_options' => ['username', 'password', 'contact_id'],
            'sandbox' => false,
            'omnibus.api' => function (Config $c) {
                $http = $this->http ?? HttpClient::create();

                return new Api($http, (string) $c['username'], (string) $c['password'], (string) $c['contact_id'], (bool) $c['sandbox'], backoff: $c->backoff());
            },
            'omnibus.action.shipping' => new ShippingAction(),
            'omnibus.action.tracking' => new TrackingAction(),
            'omnibus.action.cancel' => new CancelAction(),
        ]);
    }
}
