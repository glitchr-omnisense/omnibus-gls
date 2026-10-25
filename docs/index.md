# omnibus/gls

GLS for [omniscient/omnibus](https://github.com/glitchr-studio/omnibus): shipments with their PDF
labels and cancellations through GLS's ShipIT web API (HTTP basic authentication), tracking
through GLS's public parcel tracking (no credentials). ShipIT quotes no prices: they come from the
`rates` option. The gateway calls:

| Operation | API | Request |
|---|---|---|
| ship | ShipIT | `POST /shipments` |
| cancel | ShipIT | `POST /shipments/cancel/{trackId}` |
| track | public tracking | `GET https://gls-group.eu/app/service/open/rest/EU/{language}/rstt001?match={number}` |

ShipIT paths are on `https://shipit.gls-group.eu/backend/rs` (live) or
`https://shipit-wbm-test01.gls-group.eu:8443/backend/rs` (the test system, `sandbox: true`). The
public tracking has one address, whatever `sandbox` says.

## Installation

```sh
composer require omnibus/gls
```

Requires PHP 8.2, `omniscient/omnibus` (2.x) and `symfony/http-client` (6.4, 7 or 8); given no
HTTP client, the factory makes one with `HttpClient::create()`. In a Symfony application with
`Omnibus\Bridge\Symfony\OmnibusBundle`, the factory is registered as soon as the package is
installed.

## Credentials

- A GLS customer account with ShipIT web API access: the ShipIT **username** and **password**, and
  the customer's **contact id** at GLS. The GLS sales contact gives the test login and the contact
  id, then the production login.

Tracking needs none of them.

## Options

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `username` | string | - | yes | the ShipIT login |
| `password` | string | - | yes | its password |
| `contact_id` | string | - | yes | the shipper's contact id (`Shipper.ContactID`) |
| `sandbox` | bool | `false` | no | `true`: the ShipIT test system |
| `rates` | list | - | no | the only way this gateway rates ([the rates option](https://github.com/glitchr-studio/omnibus/blob/2.x/docs/configuration.md#the-rates-option)) |

Shipment options (`Shipment::$options`):

| Option | Default | |
|---|---|---|
| `flex_delivery` | `true` | adds the FlexDelivery service when the recipient has an email; `false` leaves it out |

## Capabilities

| Request | Supported |
|---|---|
| `Rating` | only with `rates` (configured prices): ShipIT quotes none |
| `Shipping` | yes |
| `Tracking` | yes (public tracking) |
| `Pickup` | no: ShipIT has no ParcelShop search (GLS's finder is a separate contract) |
| `GetSlip` | no |
| `Cancel` | yes |
| `Paperless` | no |

## Behaviour

**Calls.** ShipIT calls use basic authentication and the media type
`application/glsVersion1+json`, with a 20-second timeout.

**Ship.** One `POST /shipments`:

- the product is `Shipment::$service` in capitals, else `PARCEL` (GLS's products include
  `EXPRESS` and `FREIGHT`);
- the shipping date `Shipment::$shippingDate`, else today;
- the recipient is the consignee, the sender the shipper's alternative address, under the
  configured contact id;
- an address is `Name1` (the company, else the person, 40 characters), `Name2` (the person when
  there is a company), country, postcode, city, `Street` (the first street line, 40 characters),
  the email, and the phone as `MobilePhoneNumber`; when the address has a single street line
  starting with a number (`12 rue...`, `21b ...`), that number is also sent as `StreetNumber`;
- each parcel is a shipment unit: its weight in kilograms rounded to two decimals (at least 0.1),
  its `reference` when it has one; dimensions are not sent;
- the shipment's `reference` (40 characters) as `ShipmentReference`;
- services: `ShopDelivery` with the `pickupPoint` as `ParcelShopID` when the shipment has one;
  `FlexDelivery` when the recipient has an email and `flex_delivery` is not `false`;
- labels as PDF, template set `NONE`.

The tracking number is the first parcel's `TrackID` (else its `ParcelNumber`); the label the first
`PrintData` document, base64-decoded, `application/pdf`. A shipment of several parcels gives one
`Label` with the first parcel's number and document. `trackingUrl` is
`https://gls-group.eu/EU/en/parcel-tracking?match=<number>`. A response without a number is a
`CarrierException` (`GLS booked no shipment.`).

**Track.** The public tracking in French when the locale starts with `fr`, in German for `de`,
else in English. The first parcel's `history` gives the events: date and time (in PHP's default
time zone), GLS's description, `city countryName` as location; no event code is kept. Events are
sorted oldest first; the status is the latest event's, or `DELIVERED` when the progress bar's
`statusInfo` is `DELIVERED`. GLS sends no status code, so an event's status is read from its
description, in this order:

| The description contains | `TrackingStatus` |
|---|---|
| `delivered`, `livré` or `zugestellt`, with `parcelshop` or `parcel shop` | `AVAILABLE_FOR_PICKUP` |
| `delivered`, `livré` or `zugestellt` | `DELIVERED` |
| `out for delivery`, `en cours de livraison`, `in zustellung` | `OUT_FOR_DELIVERY` |
| `not delivered`, `non livré`, `nicht zugestellt`, `exception` | `EXCEPTION` |
| `return`, `retour`, `rück` | `RETURNED` |
| `data`, `données`, `daten` | `PENDING` |
| anything else | `IN_TRANSIT` |
| no description | `UNKNOWN` |

Because the rows are tried in order, a text such as "not delivered" contains `delivered` and is
read as `DELIVERED` before the `EXCEPTION` row is reached.

> [!WARNING]
> Known defect: "not delivered" reads `DELIVERED`, where it is an `EXCEPTION`.

**Cancel.** `POST /shipments/cancel/{trackId}`: `true` when GLS's `result` is `CANCELLED` or
`CANCELLATION_PENDING`, `false` for any other result.

## Example

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnibus\Gls\GlsGatewayFactory;
use Omnibus\Model\Address;
use Omnibus\Model\Parcel;
use Omnibus\Model\Shipment;
use Symfony\Component\HttpClient\HttpClient;

$gls = (new GlsGatewayFactory(HttpClient::create()))->create([
    'username' => getenv('GLS_USERNAME'),
    'password' => getenv('GLS_PASSWORD'),
    'contact_id' => getenv('GLS_CONTACT_ID'),
    'sandbox' => true,
    'rates' => [
        ['service' => 'PARCEL', 'label' => 'GLS', 'days' => 2, 'countries' => ['FR', 'BE', 'DE'],
            'bands' => [2000 => 690, 5000 => 890, 10000 => 1190]],
    ],
]);

$shipment = new Shipment(
    new Address('Glitch Art', ['12 rue de la Paix'], '75002', 'Paris', 'FR', company: 'Glitch Art'),
    new Address('Émile Zola', ['21 rue de Bruxelles'], '75009', 'Paris', 'FR', email: 'emile@example.org', phone: '+33612345678'),
    [new Parcel(800, reference: 'BOX-1')],
    reference: 'CMD-1042',
);

foreach ($gls->rate($shipment) as $rate) {
    printf("%s %s %5.2f %s\n", $rate->service, $rate->label, $rate->amount / 100, $rate->currency);
}

$label = $gls->ship($shipment);
file_put_contents($label->trackingNumber.'.pdf', $label->content);
echo $label->trackingNumber, ' ', $label->trackingUrl, "\n";

$tracking = $gls->track($label->trackingNumber, 'fr');
echo $tracking->status->value, ' ', $tracking->latest()?->description, "\n";

var_dump($gls->cancel($label->trackingNumber));
```

In Symfony:

```yaml
omnibus:
    gateways:
        gls:
            factory: gls
            options:
                username: '%env(GLS_USERNAME)%'
                password: '%env(GLS_PASSWORD)%'
                contact_id: '%env(GLS_CONTACT_ID)%'
                sandbox: '%env(bool:GLS_SANDBOX)%'
                rates:
                    - { service: PARCEL, label: 'GLS', bands: { 2000: 690, 5000: 890 } }
```

A rate needs its `bands`: an entry with an `amount` and no `bands` is never offered.

## Errors

Every failure is an `Omnibus\Exception\CarrierException` whose `carrier` is `gls`:

- a ShipIT HTTP status of 400 or more: the `message` header GLS sends, else the body's `message`,
  else its first error's `description`, else `HTTP <status>`; the `error` header in
  `carrierCode`;
- a ShipIT answer that is not JSON: `GLS answered with a body that is not JSON.`; a network error:
  `GLS request failed: ...`;
- a tracking HTTP status of 400 or more: its `message`, else `GLS tracking answered HTTP
  <status>.`; a network error or unreadable body: `GLS tracking failed: ...`;
- `GLS booked no shipment.` when no parcel number comes back.

A missing `username`, `password` or `contact_id` is an `InvalidConfigException` when the gateway
is built; `Pickup`, `GetSlip`, `Paperless`, and `Rating` without `rates`, raise
`RequestNotSupportedException` before any call.

## Verified, and not

Tested offline: `Tests/GlsGatewayTest.php` (a shipment created with its PDF label, the contact id,
product `PARCEL`, the reference, the company-less consignee, the weight in kilograms and
FlexDelivery; ShopDelivery for a ParcelShop; tracking with the progress bar's `DELIVERED`, its
location and the English path; cancellation; configured rates; no pickup points) and
`Tests/GlsContractTest.php`, the family's conformance suite on recorded answers. The example above
was run in the core's harness on those recorded answers.

Not verified: the README says the package is built from GLS's published ShipIT documentation and
was not yet run against the test system; that needs the credentials above.

## Responsibility

An independent, unofficial client, not affiliated with GLS (General Logistics Systems B.V.).
Whoever runs it complies with GLS's terms (those of its developer portal and of the contract,
[dev-portal.gls-group.net](https://dev-portal.gls-group.net/)): see the README's Responsibility
section.
