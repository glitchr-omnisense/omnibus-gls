# omnibus/gls

GLS for [omnisense/omnibus](https://github.com/glitchr-omnisense/omnibus): shipments with their
labels and cancellations through the ShipIT web API, tracking through GLS's public parcel
tracking. Prices come from configuration (`rates`): ShipIT quotes none.

```php
$gateway = (new GlsGatewayFactory($http))->create($options);   // $http: the application's HTTP client - none given, the factory makes its own; the options below
```

No framework needed: the package requires `omnisense/omnibus` and `symfony/http-client`. In a
Symfony application, the same through the bundle's configuration:

```yaml
omnibus:
    gateways:
        gls:
            factory: gls
            options:
                username: '%env(GLS_USERNAME)%'
                password: '%env(GLS_PASSWORD)%'
                contact_id: '%env(GLS_CONTACT_ID)%'
                sandbox: true
                rates:
                    - { service: PARCEL, label: 'GLS', amount: 690 }
```

The service is the product (PARCEL by default, EXPRESS, FREIGHT); a shipment with a
`pickupPoint` (a ParcelShop id) adds the ShopDelivery service; a recipient with an email gets
FlexDelivery (option `flex_delivery: false` to skip it). No ParcelShop search: GLS's finder is a
separate contract from ShipIT.

Credentials: a GLS customer account with ShipIT web API access (your GLS sales contact gives the
test login, the contact id and then the production login).

Built from GLS's published ShipIT documentation and tested on recorded answers; not yet run
against the test system: that needs the credentials above.

## Responsibility

This package is an independent, unofficial client: it is not affiliated with, endorsed by or
sponsored by GLS (General Logistics Systems B.V.). It reaches their service on behalf of whoever
runs it, and it does not redistribute their data beyond the short excerpts recorded as test
fixtures. Its MIT licence covers this package's code only and grants no right to the service or to
its data.

Whoever installs and runs this package is responsible for complying with GLS's terms (those of its
developer portal and of the contract,
[dev-portal.gls-group.net](https://dev-portal.gls-group.net/)), for obtaining whatever they require
(an account, a key, a licence or subscription, an attribution or a citation, a rate limit to
respect), and for any use made of what it returns. The authors publish it free of charge, make no
commercial use of it, and accept no liability for how it is used (see [LICENSE](LICENSE)).

License: MIT since 2026-10-09; earlier versions remain published under LGPL-3.0-or-later.
