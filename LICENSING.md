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

The versions, license modes, copyright notices, and bundled license files for
the dependencies shipped with a FlatCMS release are listed in
[THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

## Trademarks

Code licenses do not grant trademark rights. Brand usage is governed
separately by [TRADEMARK.md](TRADEMARK.md).

## Premium Product Activation Keys

Premium FlatCMS components use product activation keys independently of the
source-code licenses described above. The canonical display format is:

```text
XXXXX-XXXXX-XXXXX-XXXXX-XXXXX
```

Keys must be generated from a cryptographically secure random source and use
an unambiguous uppercase alphabet. Characters that are easily confused, such
as `I`, `O`, `0`, and `1`, should be excluded.

The activation model associates a key with an authorized account and one or
more permitted domains. It is conceptually similar to a multi-activation key,
but FlatCMS does not use Microsoft MAK or KMS terminology and does not claim
compatibility with those systems.

A product activation key grants access only to the component and usage scope
defined by its commercial offer. It does not change the copyright license of
FlatCMS Core or of any third-party dependency.

## Contributions

Contribution terms are described in [CLA.md](CLA.md).
