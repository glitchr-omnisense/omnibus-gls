<?php

namespace Omnibus\Gls\Tests;

use Omnibus\GatewayFactoryInterface;
use Omnibus\Gls\GlsGatewayFactory;
use Omnibus\Testing\GatewayContractTests;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** The conformance suite on GLS's recorded answers (ShipIT test, public tracking); no rating API, so no rate without the "rates" option. */
final class GlsContractTest extends TestCase
{
    use GatewayContractTests;

    protected function gatewayFactory(HttpClientInterface $http): GatewayFactoryInterface
    {
        return new GlsGatewayFactory($http);
    }

    protected function gatewayOptions(): array
    {
        return ['username' => 'user', 'password' => 'pass', 'contact_id' => '276a1234', 'sandbox' => true];
    }

    protected function contractTrackingNumber(): string
    {
        return '78712345678';
    }

    protected function recordedAnswers(): array
    {
        return [
            '/shipments/cancel/' => ['TrackID' => 'ZSXJ4MK1', 'result' => 'CANCELLED'],
            'shipit-wbm-test01' => ['CreatedShipment' => ['ParcelData' => [['TrackID' => 'ZSXJ4MK1', 'ParcelNumber' => '78712345678']], 'PrintData' => [['Data' => base64_encode('%PDF-1.4 gls'), 'LabelFormat' => 'PDF']]]],
            'gls-group.eu/app/service' => ['tuStatus' => [['tuNo' => '78712345678', 'progressBar' => ['statusInfo' => 'DELIVERED'], 'history' => [
                ['date' => '2026-10-02', 'time' => '11:20:00', 'evtDscr' => 'The parcel has been delivered.', 'address' => ['city' => 'Paris', 'countryName' => 'France']],
                ['date' => '2026-10-01', 'time' => '18:05:00', 'evtDscr' => 'The parcel has reached the parcel center.', 'address' => ['city' => 'Toussieu', 'countryName' => 'France']],
            ]]]],
        ];
    }
}
