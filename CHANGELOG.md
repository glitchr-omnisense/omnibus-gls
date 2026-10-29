# Changelog

## [Unreleased]

### Fixed
- A `CarrierException` no longer carries the password. The message quoted the HTTP client's (the
  whole URL), the previous was the client's own exception, and every trace of the chain kept the
  arguments of the calls under it (the request's options, the `Api` with its credentials) when
  `zend.exception_ignore_args` is off: a log of the whole chain wrote them. The message masks them
  as `***`, the previous is a copy of the client's exception (the same class and code), and no
  exception `Api` throws keeps its trace's arguments (`Omnibus\Http\Redaction`).
- A failed delivery is read `EXCEPTION`, not `DELIVERED`: GLS's texts for it ("The parcel could
  not be delivered...", "Not delivered", "Das Paket konnte nicht zugestellt werden...", "Le colis
  n'a pas pu être livré...") contain the words of a delivery, which were looked for first. The
  failed deliveries are now read before the deliveries.
- The README's YAML rate example offers a price: it had an `amount` and no `bands`, an entry the
  rates option never offers.
