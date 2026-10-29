# Changelog

## [Unreleased]

### Fixed
- A failed delivery is read `EXCEPTION`, not `DELIVERED`: GLS's texts for it ("The parcel could
  not be delivered...", "Not delivered", "Das Paket konnte nicht zugestellt werden...", "Le colis
  n'a pas pu être livré...") contain the words of a delivery, which were looked for first. The
  failed deliveries are now read before the deliveries.
