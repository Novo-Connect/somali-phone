# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-10-22

First release.

### Added

- Parsing and normalization of Somali mobile numbers in all common formats, including Arabic-Indic digits.
- Operator lookup based on the NCA national numbering plan of December 2024.
- Virtual network lookup for Somnet, Soltelco and Durdur.
- Mobile money wallet lookup and a WAAFI support check. Wallets whose WAAFI support is not officially confirmed are reported separately.
- `SomaliPhoneNumber` Laravel validation rule with operator, virtual network, wallet and WAAFI filters.
- `somali_phone` string validation rule.
- `AsSomaliPhone` Eloquent cast.
- Validation messages in English and Somali.
- Support for Laravel 12 and 13.

[1.0.0]: https://github.com/Novo-Connect/somali-phone/releases/tag/v1.0.0
