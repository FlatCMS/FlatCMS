# FlatCMS Licensing

FlatCMS uses a split-license model across the broader product line.

## Default Rule

Unless a source file states otherwise in its header, first-party FlatCMS source
code is licensed under the GNU Affero General Public License v3.0 or later:

- `SPDX-License-Identifier: AGPL-3.0-or-later`

The full text of that license is available in [LICENSE](LICENSE).

## This Repository

`FlatCMS LTS Core` is intended to ship the stable open-source core line.

At the time of writing, this repository does not intentionally carry premium
code directories as part of its supported runtime scope.

If a file header and this document ever differ, the file header is
authoritative.

## Third-Party Dependencies

Third-party libraries, bundled assets, and vendor code keep their own
licenses. This includes, for example:

- `app/ThirdParty/**`
- `public/assets/dists/**`
- any `vendor/**` tree shipped by a dependency

Those parts are not relicensed by FlatCMS.

## Trademarks

Code licenses do not grant trademark rights. Brand usage is governed
separately by [TRADEMARK.md](TRADEMARK.md).

## Contributions

Contribution terms are described in [CLA.md](CLA.md).
