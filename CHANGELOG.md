# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.6.3-test.3](https://github.com/akira-io/laravel-sisp/compare/v0.6.3-test.2...v0.6.3-test.3) (2026-05-22)

### Other

- **release:** Configure git-cliff remote links ([4e25d06](https://github.com/akira-io/laravel-sisp/commit/4e25d06f2059f68c771b83e9a9c59395017acd04))

## [0.6.3-test.2](https://github.com/akira-io/laravel-sisp/compare/v0.6.3-test.1...v0.6.3-test.2) (2026-05-22)

### Other

- **release:** Fix git-cliff link template ([02adc2b](https://github.com/akira-io/laravel-sisp/commit/02adc2bb7fbdc6fc03d8273091330c053f748c29))

## [0.6.3-test.1](https://github.com/akira-io/laravel-sisp/compare/v0.6.2...v0.6.3-test.1) (2026-05-22)

### Other

- **release:** Split discord notification workflow ([68f6413](https://github.com/akira-io/laravel-sisp/commit/68f6413a110ddd886bf2fe9359b98e45c3af4548))
- **release:** Use git-cliff release workflow ([b8c6146](https://github.com/akira-io/laravel-sisp/commit/b8c6146116ee489febf1c6532a58e66e24df8c46))

## [0.6.2](https://github.com/akira-io/laravel-sisp/compare/0.6.1...v0.6.2) (2026-05-22)

### Bug Fixes

- **callback:** Move duplicate check into controller after signature validation ([96b5046](https://github.com/akira-io/laravel-sisp/commit/96b50460a9bb8777bb41342baa33df175a289fd8))
- **refund:** Add authorization check to refund transaction endpoint ([2aa6537](https://github.com/akira-io/laravel-sisp/commit/2aa6537df8d9a1ea74d0d5477bd6e92ad65ffa17))
- **invoice:** Handle nullable due_date in PDF generation ([be8c6b7](https://github.com/akira-io/laravel-sisp/commit/be8c6b7304a22129428aa0ab9808fa14ab551b69))
- **tests:** Use explicit window_seconds variable in rate limit reset test ([1cda992](https://github.com/akira-io/laravel-sisp/commit/1cda992ae24e4c5902d4094128d474893fa10027))
- **3ds:** Add retry payment validation and improve response handling ([12feb51](https://github.com/akira-io/laravel-sisp/commit/12feb511c3d6bb42f981aec4b6cee311eae6789c))
- **3ds:** Bail early in withValidator when transaction_id already has errors ([964320a](https://github.com/akira-io/laravel-sisp/commit/964320abb7e0744aa2c207939398d42f807d8512))
- **3ds:** Use property access instead of getRawOriginal for customer fields ([2ea5cb4](https://github.com/akira-io/laravel-sisp/commit/2ea5cb441d3668e78debec1a01d471e6eca0ff87))
- **core:** Resolve payload encryption conflict and rate limiter type error ([a7df9ef](https://github.com/akira-io/laravel-sisp/commit/a7df9eff2c084a7fefffe7e3030622c52104d1bc))
- **middleware:** Catch only expected exceptions to prevent invalid HTTP status codes ([b792842](https://github.com/akira-io/laravel-sisp/commit/b792842a38d12441f7aac80b72970f21273876da))
- **middleware:** Catch only expected exceptions to prevent invalid HTTP status codes ([65cc71a](https://github.com/akira-io/laravel-sisp/commit/65cc71aa24c3779612fcde64530f088ca3d2852f))
- **dependencies:** Update PHP and Laravel version constraints in composer.json and run-tests.yml ([0756944](https://github.com/akira-io/laravel-sisp/commit/07569448a65c34bd8e686da82621bb907019c3dc))
- **rector:** Add skip for FillablePropertyToFillableAttributeRector and TablePropertyToTableAttributeRector ([f968d2f](https://github.com/akira-io/laravel-sisp/commit/f968d2f079feebdb4048cd5d611245e6cc353972))
- **rector:** Skip entire Laravel 13 set instead of individual rules ([b849d92](https://github.com/akira-io/laravel-sisp/commit/b849d9215063be213ef0304ecdc7dfba0b3b045c))
- **rector:** Remove withComposerBased to keep behavior deterministic ([e0555c7](https://github.com/akira-io/laravel-sisp/commit/e0555c747ddc659c9b885892720d75ffd551d1ca))
- **cancel:** Resolve transaction from request instead of route model binding ([d1e38e0](https://github.com/akira-io/laravel-sisp/commit/d1e38e089407d30d111aa6fc4101d23faf08de74))
- **cancel:** Query by transaction_id column instead of primary key ([d53089f](https://github.com/akira-io/laravel-sisp/commit/d53089f1ccba434139015e4a6c49f7f7ebef2601))
- **cancel:** Use package translation namespace for error messages ([62709a0](https://github.com/akira-io/laravel-sisp/commit/62709a0d27c04607e59e326ded59f1012a55fb0a))
- **sandbox:** Block callback generation in production ([464357b](https://github.com/akira-io/laravel-sisp/commit/464357b958d01ae569d1caa922728038f084f46c))
- **cancel:** Require signed cancellation requests ([3ddefea](https://github.com/akira-io/laravel-sisp/commit/3ddefeaedc862d0000ee2918af9f0c214472bd7b))
- **callback:** Reconcile signed transaction details ([3815482](https://github.com/akira-io/laravel-sisp/commit/3815482d6e855ca28120ebbd8a0880070051e817))
- **callback:** Address reconciliation review feedback ([f4fd25b](https://github.com/akira-io/laravel-sisp/commit/f4fd25bd885aae549219fbbb252428a1338cd266))
- **refund:** Remove email fallback authorization ([07ce356](https://github.com/akira-io/laravel-sisp/commit/07ce35635ea968c06c328b78249082275082a859))
- **retry:** Require postal code for 3ds retries ([670aa95](https://github.com/akira-io/laravel-sisp/commit/670aa952d9c3e8fe7d4e0f0919898b7406d742b4))
- **retry:** Use postal fallback for 3ds retries ([76d2c40](https://github.com/akira-io/laravel-sisp/commit/76d2c40b398970c72b3902d4b4252bbe840d44f9))
- **rate-limit:** Allow configured threshold ([8501b3e](https://github.com/akira-io/laravel-sisp/commit/8501b3ed4ab81a2b553211b8ed1c86a236c884c2))
- **composer:** Guard testbench autoload hook ([e50d2aa](https://github.com/akira-io/laravel-sisp/commit/e50d2aa43c35db3c6080cf0664f6e4ab74f5ad31))


### Features

- **payment:** Enhance UI with branding and countdown animations, add developer credit ([fe31493](https://github.com/akira-io/laravel-sisp/commit/fe31493d70bf18efb4203d08cebc1821d84dd64a))


### Other

- Merge pull request #81 from akira-io/fix-issue-68-sisp ([2597887](https://github.com/akira-io/laravel-sisp/commit/259788769fbff62d89d61e6f1da18f55b8ee9b66))
- Merge pull request #82 from akira-io/fix-issue-70-search ([e4d7ff2](https://github.com/akira-io/laravel-sisp/commit/e4d7ff21efca84e8994450d2c54c40f932f5efc4))
- Merge pull request #87 from akira-io/fix-issue-76

fix(invoice): handle nullable due_date in PDF generation ([e48e582](https://github.com/akira-io/laravel-sisp/commit/e48e5826c4583c4a3e446c34c67a4b8f8fa55001))
- Merge pull request #88 from akira-io/fix-77-livewire-skip

fix(tests): use explicit window_seconds variable in rate limit test ([c4e59f9](https://github.com/akira-io/laravel-sisp/commit/c4e59f954ab28d714bb422842cd142a46ab81274))
- Merge pull request #91 from akira-io/update-ui

feat(payment): enhance UI with branding and countdown animations, add… ([1ed01ff](https://github.com/akira-io/laravel-sisp/commit/1ed01ffe1197cc6c5cb669bd000d21cf1ab6c4da))
- Merge pull request #90 from akira-io/fix-3ds-retry-data

fix(3ds): prevent retry when required customer data is missing ([7a439cd](https://github.com/akira-io/laravel-sisp/commit/7a439cd776228920165084bab0a892ace72e0da8))
- Merge pull request #89 from akira-io/fix-issue-72-behavior

Fix payload encryption conflict and rate limiter type error ([c8ae3cc](https://github.com/akira-io/laravel-sisp/commit/c8ae3ccb91334d3dd342a75e4d3991db1946904a))
- Merge pull request #93 from akira-io/dependabot/github_actions/dependabot/fetch-metadata-3.0.0

chore(deps): bump dependabot/fetch-metadata from 2.5.0 to 3.0.0 ([860ed5d](https://github.com/akira-io/laravel-sisp/commit/860ed5d70b87b2c46c22a83274fb3476dc740caa))
- Merge pull request #96 from akira-io/dependabot/github_actions/SethCohen/github-releases-to-discord-1.20.0

chore(deps): bump SethCohen/github-releases-to-discord from 1.19.0 to 1.20.0 ([94bf975](https://github.com/akira-io/laravel-sisp/commit/94bf975f3c1403e738db75d646468fed6afc77f5))
- Merge pull request #98 from akira-io/dependabot/github_actions/dependabot/fetch-metadata-3.1.0

chore(deps): bump dependabot/fetch-metadata from 3.0.0 to 3.1.0 ([5cff4cd](https://github.com/akira-io/laravel-sisp/commit/5cff4cdd70999f2b03deb32a6adb142d470baf47))
- Merge pull request #100 from akira-io/chore/php-8.5-laravel-13-matrix

chore(ci): add PHP 8.5 and Laravel 13 to test matrix ([05be631](https://github.com/akira-io/laravel-sisp/commit/05be63151e4968754f0fe9e6595799812744401c))
- Merge pull request #99 from akira-io/dependabot/composer/inertiajs/inertia-laravel-tw-3.1

chore(deps-dev): update inertiajs/inertia-laravel requirement from ^2.0 to ^3.1 ([f9fb7f7](https://github.com/akira-io/laravel-sisp/commit/f9fb7f7d27230533a9b5f43e5a5ad515faacd517))
- Merge pull request #85 from akira-io/fix-issue-71

fix(cancel): resolve transaction from request instead of route model binding ([310f791](https://github.com/akira-io/laravel-sisp/commit/310f791d1f2005c2c50e4e1031f3d8290b38523c))
- Merge pull request #129 from akira-io/codex/issue-125-disable-sandbox-production

fix(sandbox): block callback generation in production ([c1273cc](https://github.com/akira-io/laravel-sisp/commit/c1273ccae1f870457d902823d25b2257633fc8a9))
- Merge pull request #130 from akira-io/codex/issue-126-signed-cancellation

fix(cancel): require signed cancellation requests ([6ba0132](https://github.com/akira-io/laravel-sisp/commit/6ba013263c279a6e6e80638d15b34926cb02e24a))
- Merge pull request #131 from akira-io/codex/issue-127-reconcile-callback-details

fix(callback): reconcile signed transaction details ([3bc3cda](https://github.com/akira-io/laravel-sisp/commit/3bc3cda310426c42d60e545164a8062a0f996ce4))
- Merge pull request #132 from akira-io/codex/issue-128-refund-email-fallback

fix(refund): remove email fallback authorization ([6f16795](https://github.com/akira-io/laravel-sisp/commit/6f167950403ee0d93f863812081a336fd107b160))
- Merge pull request #133 from akira-io/codex/issue-109-retry-3ds-postal-code

fix(retry): require postal code for 3ds retries ([5abc17b](https://github.com/akira-io/laravel-sisp/commit/5abc17b4c6fafa5132d13ca1c4a284fab2a647ec))
- Merge pull request #134 from akira-io/codex/issue-113-rate-limit-threshold

fix(rate-limit): allow configured threshold ([8906384](https://github.com/akira-io/laravel-sisp/commit/89063843c436b584fcb97d4dbe183e54731db23d))
- Merge pull request #135 from akira-io/codex/issue-102-dev-autoload-hook

fix(composer): guard testbench autoload hook ([90829f3](https://github.com/akira-io/laravel-sisp/commit/90829f39bcd6f74ad9578d9f18a199149454597e))
- **release:** Add tag driven release workflow ([20c22b9](https://github.com/akira-io/laravel-sisp/commit/20c22b9b1028472d729f1e57a5e3304d1fceb445))
- Merge pull request #136 from akira-io/codex/release-setup

ci(release): add tag driven release workflow ([5a1bee0](https://github.com/akira-io/laravel-sisp/commit/5a1bee0b72d75a97f0ded67a28540e6fca19246f))

## [0.6.1](https://github.com/akira-io/laravel-sisp/compare/0.6.0...0.6.1) (2026-03-07)

### Bug Fixes

- **callback:** Handle invalid signatures by dispatching PaymentFailed event and updating transaction status ([7a79222](https://github.com/akira-io/laravel-sisp/commit/7a792228f474acf1503026a9e6faa3c100e6a1f1))

## [0.6.0](https://github.com/akira-io/laravel-sisp/compare/0.5.0...0.6.0) (2026-03-07)

### Bug Fixes

- **retry:** Regenerate merchant session and timestamp ([6b124b3](https://github.com/akira-io/laravel-sisp/commit/6b124b3fdb447105403571164477e62b084654c7))
- **ci:** Add type to retry postal code constant ([3aea0ea](https://github.com/akira-io/laravel-sisp/commit/3aea0eaaa1b9f4aa54cc0894599b49757f79cf73))
- **ci:** Remove stale phpstan baseline ignores ([f4c14fb](https://github.com/akira-io/laravel-sisp/commit/f4c14fbe7189109b5174ce4af9b00729bd69725a))
- **credentials-resolve:** Refactor constructor to use parameter instead of class property for SispCredentialsResolver ([22bff26](https://github.com/akira-io/laravel-sisp/commit/22bff2636957a0b9206dc6a50f4c5d85d1fe584b))
- Add type hint to setRawAttributes in EncryptsAttributes trait ([42429a1](https://github.com/akira-io/laravel-sisp/commit/42429a1ae58cb04d31073b2336d70c52e9288156))


### Code Refactoring

- **retry:** Document fallback postal code ([7ebfe3c](https://github.com/akira-io/laravel-sisp/commit/7ebfe3c0e7fc511b3baa7225d62ceab27a2bfd78))


### Features

- **perf:** Optimize getTransactions to return Builder for scalability ([ca954dd](https://github.com/akira-io/laravel-sisp/commit/ca954dd30c86288fdbef767e5259dafba21b5f0c))


### Other

- Merge pull request #67 from akira-io/fix/issue-66

fix(retry): regenerate merchant session and timestamp ([73cb477](https://github.com/akira-io/laravel-sisp/commit/73cb477930e1ea5c092e6083f05ee88e04a075d0))
- Merge pull request #52 from akira-io/perf/optimize-countries-static-allocation-7995443177645210496

⚡ Optimize Countries static data allocation ([9cec5af](https://github.com/akira-io/laravel-sisp/commit/9cec5afd9d341c46c5a96ec584063d10697a9d2d))
- Merge pull request #53 from akira-io/perf/optimize-get-transactions-14480899926776353667

⚡ Optimize getTransactions to return Builder ([1c6b6a3](https://github.com/akira-io/laravel-sisp/commit/1c6b6a324113bc68676a5d57b5004b85f2ceabe0))
- Merge pull request #54 from akira-io/performance-optimize-regenerate-pdfs-command-1817417619515749408

⚡ Optimize PDF regeneration command query performance ([8e216a2](https://github.com/akira-io/laravel-sisp/commit/8e216a2cc93f8e37ed33e12dd7a8e6c3741d9a5c))
- Merge pull request #61 from akira-io/bolt/optimize-postautcode-hashing-2093977724464835653

⚡ Bolt: Optimize PostAutCode hashing ([f4c085b](https://github.com/akira-io/laravel-sisp/commit/f4c085bf90df7272cc7bdafd78b9c3d7b0074687))
- Merge pull request #65 from akira-io/bolt-cache-encrypted-attributes-13918391392561715151

⚡ Bolt: Cache decrypted attributes in EncryptsAttributes trait ([76f2f69](https://github.com/akira-io/laravel-sisp/commit/76f2f69791bb12b623cddd988c4863bf4399f90e))


### Performance Improvements

- Cache Countries data array in static property ([2f18202](https://github.com/akira-io/laravel-sisp/commit/2f18202374cd345b39ddf498e68759305309cadf))
- **commands:** Optimize regenerate pdfs command query count ([6ae24da](https://github.com/akira-io/laravel-sisp/commit/6ae24dab54216c4492d076e9d361d814fb52deb3))
- Optimize PostAutCode hashing ([a32adfd](https://github.com/akira-io/laravel-sisp/commit/a32adfdde95c3b53097ebced9c6482815dc34180))
- Cache decrypted attributes in EncryptsAttributes trait ([e765b46](https://github.com/akira-io/laravel-sisp/commit/e765b4616b2be5c3f30b77b9e639e0b47b9501e7))
- **encryption:** Optimize EncryptsAttributes decryption fallback and clean up unused legacy docs ([7257221](https://github.com/akira-io/laravel-sisp/commit/72572218b7c1e7f32e3d7a7b82c9367a3e8133ef))

## [0.5.0](https://github.com/akira-io/laravel-sisp/compare/0.4.3...0.5.0) (2026-02-02)

### Bug Fixes

- Update doctor command output to reference correct regenerate-pdfs artisan command ([5ac069a](https://github.com/akira-io/laravel-sisp/commit/5ac069aed9c307b3f6cf905735cf837dd883b376))
- Fixed callback validation race condition ([5a7081a](https://github.com/akira-io/laravel-sisp/commit/5a7081a2e5c55112e078c1bb6ec263bcb0f57306))
- Fixed callback validation race condition by validating signature before database access. (#21) ([b5ad57b](https://github.com/akira-io/laravel-sisp/commit/b5ad57b4ec1044301397272894dd5c2632a0a761))
- Correct transaction status output in doctor command and add missing properties to Invoice model ([1c23702](https://github.com/akira-io/laravel-sisp/commit/1c23702d8fd5d858d60b32039c099ebef95546eb))
-  fix(security): validate callback before db access in HandleCallbackAction

* fix(security): validate callback before db access in HandleCallbackAction

- Move `Sisp::validateCallback` to the start of `HandleCallbackAction::handle`
- Throw `InvalidSignatureException` (403) instead of dispatching `PaymentFailed`
- Prevent unvalidated input from triggering database lookups/creation
- Add `InvalidSignatureException` class

Co-authored-by: kidiatoliny <48266788+kidiatoliny@users.noreply.github.com>

* fix(security): validate callback before db access in HandleCallbackAction

- Validate callback signature immediately in `HandleCallbackAction::handle`.
- Throw `InvalidSignatureException` (403) instead of dispatching `PaymentFailed`.
- Prevent unvalidated input from triggering database lookups.
- Fix Rector violations in `HandleCallbackAction` (`throw_unless`).
- Fix Rector violations in `DoctorCommand` (use `Model::query()`).

Co-authored-by: kidiatoliny <48266788+kidiatoliny@users.noreply.github.com>

* fix(security): validate callback before db access in HandleCallbackAction

- Validate callback signature immediately in `HandleCallbackAction::handle`.
- Throw `InvalidSignatureException` (403) instead of dispatching `PaymentFailed`.
- Prevent unvalidated input from triggering database lookups.
- Fix Rector violations in `HandleCallbackAction` (`throw_unless`).
- Fix Rector violations in `DoctorCommand` (use `Model::with()`).
- Fix PHPStan errors in `DoctorCommand` and `Invoice` model.
- Allow 'pdfs' in `peck.json` typo checker.

Co-authored-by: kidiatoliny <48266788+kidiatoliny@users.noreply.github.com>

* fix: enhance encryption validation and improve loading indicators in payment forms

* test: add unit tests for doctor and regenerate-pdfs commands to validate invoice processing

* feat: add file handling utilities for command execution and backup management

---------

Co-authored-by: google-labs-jules[bot] <161369871+google-labs-jules[bot]@users.noreply.github.com> ([3695cd4](https://github.com/akira-io/laravel-sisp/commit/3695cd4a4b7031f1ca5ae0e8f2c211a813d4281d))
- **security:** Secure refund endpoint with configurable middleware (#40) ([83c8920](https://github.com/akira-io/laravel-sisp/commit/83c892061619024f886bfef8948f322bb3119353))


### Features

- Add doctor and regenerate-missing-pdfs artisan commands for invoice PDF diagnostics and recovery ([3105e88](https://github.com/akira-io/laravel-sisp/commit/3105e8845aeae3bf029e2a81186b92d1a12861e3))
- Improve payment redirection UX and accessibility (#16) ([253485e](https://github.com/akira-io/laravel-sisp/commit/253485ea12165c8711d756dbf404449fcdc39560))
- Add keyboard focus to redirect button (#18) ([226eb13](https://github.com/akira-io/laravel-sisp/commit/226eb13df232dc2b1024716014d6859d32f7fa0c))
- **ui:** Improve payment request form accessibility and fallback ([03465b0](https://github.com/akira-io/laravel-sisp/commit/03465b044fc369c17d984ff67b4f6266ae0c518a))
- **ui:** Add button variants and improve accessibility in payment views (#44) ([0b3fac8](https://github.com/akira-io/laravel-sisp/commit/0b3fac84fddec1f713c2a298b828a635574489b5))
- Add multi-merchant configuration and runtime credential support ([f17546a](https://github.com/akira-io/laravel-sisp/commit/f17546a0d2de86df41739ed53f1678e16344b60f))


### Other

- Refactor LoadConfig to use constructor injection for Config Repository ([2f1ab50](https://github.com/akira-io/laravel-sisp/commit/2f1ab50f6e85fb3c08b1fce71d953bbf40681590))
- Merge pull request #15 from kidiatoliny/bolt/optimize-load-config-injection-1450897854352250527

⚡ Bolt: Optimize LoadConfig dependency injection ([e1fb48a](https://github.com/akira-io/laravel-sisp/commit/e1fb48af7232df0aa285718f112172fea8add3b3))
- Merge branch '1.x' into palette/enhance-payment-request-form-6912318465234931433 ([c2b64c2](https://github.com/akira-io/laravel-sisp/commit/c2b64c245b9227ddfd65049c8e0505b9f8b3e9ce))
- Merge pull request #23 from kidiatoliny/palette/enhance-payment-request-form-6912318465234931433

🎨 Palette: Improve accessibility and robustness of payment request redirection ([cb194bd](https://github.com/akira-io/laravel-sisp/commit/cb194bd6096430e7f1d932da8f109d3664b4973f))
- Merge pull request #31 from kidiatoliny/bolt/optimize-encryption-check-9070145360404617647

⚡ Bolt: Optimize encryption check in EncryptsAttributes ([5d56240](https://github.com/akira-io/laravel-sisp/commit/5d56240deb735c91e6aa35ac3fcded5d10ab532c))
- Merge pull request #46 from kidiatoliny/multi-merchant

feat: add multi-merchant configuration and runtime credential support ([7d83e8c](https://github.com/akira-io/laravel-sisp/commit/7d83e8c8e1307154014e307cf55e4d3201231715))


### Performance Improvements

- Optimize isEncrypted check to avoid exceptions (#19) ([c76ef41](https://github.com/akira-io/laravel-sisp/commit/c76ef41b2886f1192813d4206820f9c1c76f10b4))
- Optimize isEncrypted check in EncryptsAttributes ([3f632de](https://github.com/akira-io/laravel-sisp/commit/3f632de3b5ed926821bf50fe5a3d14cf8f15b01f))
- Optimize isEncrypted check in EncryptsAttributes ([76849f1](https://github.com/akira-io/laravel-sisp/commit/76849f14d4c8130e81059b8d1e78a55407759e51))
- Optimize isEncrypted check in EncryptsAttributes ([c3bf7d0](https://github.com/akira-io/laravel-sisp/commit/c3bf7d0db811ee609bd31feedce3cdb1fd91f7dc))
- Optimize isEncrypted check in EncryptsAttributes ([f240129](https://github.com/akira-io/laravel-sisp/commit/f240129f277ebd943d6e44dadb76085a4e9b0639))
- Increase threshold for encrypted value check in EncryptsAttributes ([68d1a6d](https://github.com/akira-io/laravel-sisp/commit/68d1a6d9d29925ad00d8407205d1e075fd3fa99c))

## [0.4.3](https://github.com/akira-io/laravel-sisp/compare/0.4.2...0.4.3) (2026-01-22)

### Bug Fixes

- Move invoice PDF generation to UpdateInvoiceStatusAction on transaction completion ([ace52f1](https://github.com/akira-io/laravel-sisp/commit/ace52f14959054da35018d5252365e3dc58d4757))
- Update Invoice property annotation and remove obsolete phpstan baseline entry for pdf_path ([fff634e](https://github.com/akira-io/laravel-sisp/commit/fff634e0027c2708eee67c2a41715a6b116e53e2))

## [0.4.2](https://github.com/akira-io/laravel-sisp/compare/0.4.1...0.4.2) (2026-01-20)

### Bug Fixes

- Register additional migration for missing customer fields in sisp transactions ([f008e8a](https://github.com/akira-io/laravel-sisp/commit/f008e8a924e0117370b5f51442ddb5d78e97c063))
- Fixed initial migration for sisp tables and update migration registration in service provider ([ef5f6d3](https://github.com/akira-io/laravel-sisp/commit/ef5f6d328733fc4ac905b6a3e3784156d459d875))

## [0.4.1](https://github.com/akira-io/laravel-sisp/compare/0.4.0...0.4.1) (2026-01-20)

### Bug Fixes

- Add customer_postal_code attribute to Transaction fillable fields ([2988d8f](https://github.com/akira-io/laravel-sisp/commit/2988d8f6dd66673b61d2ecc02bd45b8b5501bf6a))
- Move locale and customer_postal_code fields to new migration for sisp transactions ([2c1db3a](https://github.com/akira-io/laravel-sisp/commit/2c1db3a689946a17d27a55bcaf1ffde3fc947a07))

## [0.4.0](https://github.com/akira-io/laravel-sisp/compare/0.3.0...0.4.0) (2026-01-20)

### Features

- Add 3D Secure purchaseRequest support with customer data validation ([c7b0943](https://github.com/akira-io/laravel-sisp/commit/c7b0943ad391f96c0e0bfa68f30e246a284e98f3))
- Add Countries utility with ISO codes, names, and flag support ([354c97e](https://github.com/akira-io/laravel-sisp/commit/354c97ede060c418e7a223be8ecd390efd87cc35))
- Add CountriesController and route to provide country data via API ([26787f5](https://github.com/akira-io/laravel-sisp/commit/26787f501ccffc74f92dbb36f7d86f8b213df912))


### Other

- Merge pull request #12 from kidiatoliny/dependabot/github_actions/dependabot/fetch-metadata-2.5.0

chore(deps): bump dependabot/fetch-metadata from 2.4.0 to 2.5.0 ([303bf79](https://github.com/akira-io/laravel-sisp/commit/303bf79e85d1c37b631110f5dbbf088933274953))

## [0.3.0](https://github.com/akira-io/laravel-sisp/compare/0.2.0...0.3.0) (2026-01-07)

### Bug Fixes

- Fixed invoice loading to use type hints for query builder ([9bbbbd8](https://github.com/akira-io/laravel-sisp/commit/9bbbbd866144040e992530e04dc37ddd4171fb32))


### Features

- Add withoutEmail method to TransactionFactory for optional customer email ([1691240](https://github.com/akira-io/laravel-sisp/commit/1691240cd68f251c84f90663e74d407d7e028344))
- Add 'roadmap' to the list of technologies in peck.json ([ce296fa](https://github.com/akira-io/laravel-sisp/commit/ce296faf3beb00e9753be1d3bb0bdeb723b1226d))

## [0.2.0](https://github.com/akira-io/laravel-sisp/compare/0.1.0...0.2.0) (2025-12-05)

### Code Refactoring

- Update InvoiceData to use CarbonInterface for improved type consistency ([3749f8c](https://github.com/akira-io/laravel-sisp/commit/3749f8c942cb65130070b095316f1ab34261aaa4))
- Update method signatures to use type hints and improve readability ([7520e54](https://github.com/akira-io/laravel-sisp/commit/7520e543dbac7b8eb09c237fc4b5e0314fa18ff3))


### Features

- Add locale field to transaction model and database schema ([aa0a97c](https://github.com/akira-io/laravel-sisp/commit/aa0a97c6f81979413a300e84bd9bf0dc2f2169c5))
- Add locale property to Transaction model and update GenerateInvoicePdfAction to use it ([76835a0](https://github.com/akira-io/laravel-sisp/commit/76835a05c8f030e1e503697d148518f5f9622b1d))
- Add locale support to transaction processing and related actions ([e9155fc](https://github.com/akira-io/laravel-sisp/commit/e9155fc85589b1a9fe8db6546e4958cae0438073))
- Add French localization for error messages and payment responses ([ce99a4f](https://github.com/akira-io/laravel-sisp/commit/ce99a4f4164463eea01aab8f6fdc336eaa617e23))
- Add translation support for payment form and response components ([0b3dab6](https://github.com/akira-io/laravel-sisp/commit/0b3dab6b329850ebb5cb73520ba4629011439a45))
- Add localization support for payment messages in French and Portuguese ([f4dd578](https://github.com/akira-io/laravel-sisp/commit/f4dd578ac4f63a8e7f0545ba8e39d824f2b83c10))
- Enhance payment form localization by passing locale to render methods ([a407ff4](https://github.com/akira-io/laravel-sisp/commit/a407ff45411a625d5b2b2355d56c8abec06d89e2))
- Enhance attribute encryption and decryption handling for arrays ([7b1e9e6](https://github.com/akira-io/laravel-sisp/commit/7b1e9e6b5c73ef5174bad1613086f59c6858474f))
- Enhance Laravel SISP installation command with additional publishing options and error handling ([9cbd5fd](https://github.com/akira-io/laravel-sisp/commit/9cbd5fd03c5496adc0117737444b710df90634b1))
- Improve transaction cancellation logic and streamline rate limit identifier assignment ([09632ba](https://github.com/akira-io/laravel-sisp/commit/09632ba237a05dfd32618ca15cf5637fa2369600))
- Enhance transaction cancellation process and improve installation command prompts ([b596d11](https://github.com/akira-io/laravel-sisp/commit/b596d116ef32a13f2041c90771fa8463762e4172))


### Other

- Simplify test workflow and update PHP version to 8.4 ([9987c59](https://github.com/akira-io/laravel-sisp/commit/9987c597214bd6d05db03139ea1f1312320e8efd))
- Simplify test workflow and update PHP version to 8.4 ([441094e](https://github.com/akira-io/laravel-sisp/commit/441094e879c4c4470584c16c953c46f0be24fa0e))
- Merge pull request #11 from akira-io/dependabot/github_actions/actions/checkout-6

chore(deps): bump actions/checkout from 5 to 6 ([d759322](https://github.com/akira-io/laravel-sisp/commit/d759322d6704a768197c5cbe3cf7d09c12a557a7))

## [0.1.0](https://github.com/akira-io/laravel-sisp/compare/...0.1.0) (2025-12-02)

### Bug Fixes

- Correct SISP sandbox functionality ([931649e](https://github.com/akira-io/laravel-sisp/commit/931649e9bf67d077297deeb739332445ae256b8f))
- Allow GET requests to sandbox endpoint ([a4d2125](https://github.com/akira-io/laravel-sisp/commit/a4d212594babe359475e2e4e373b9d623c74f903))
- Support POST form data in sandbox controller ([8965ec2](https://github.com/akira-io/laravel-sisp/commit/8965ec2edc045502f568310f831e22e5be6add99))
- Remove posID from payment request fields in RenderPaymentFormAction ([41eb81d](https://github.com/akira-io/laravel-sisp/commit/41eb81d345d748983d32a07d2e47e6133e3fda2b))
- Add exception documentation for payment response handling methods ([2124936](https://github.com/akira-io/laravel-sisp/commit/2124936041412a5a7813e281632b3a39f8f1cd25))


### Code Refactoring

- Replace SispCommand with LaravelSispInstallCommand ([2ef816f](https://github.com/akira-io/laravel-sisp/commit/2ef816f861921c7052d3c93b6a763c4a3d0f2cea))
- Remove unused pipelines ([12fd2cf](https://github.com/akira-io/laravel-sisp/commit/12fd2cfee920d69c98eaddf3325d4d45aaae7a18))
- Add documentation for Generator interface method ([b5267e3](https://github.com/akira-io/laravel-sisp/commit/b5267e3a82e181f50589f49bd9c4fe57bec4dc61))
- Streamline event handling and enhance model methods for clarity and consistency ([b148372](https://github.com/akira-io/laravel-sisp/commit/b148372045a4ed0bd47db689ecbbb8b3727697f1))


### Features

- Payment request ([4e77e07](https://github.com/akira-io/laravel-sisp/commit/4e77e07ee989627cddb55b404b28f90b77fa1bd7))
- Handle user cancellation in payment response ([97f99b0](https://github.com/akira-io/laravel-sisp/commit/97f99b0b9aaaa6d6cd2e4061e3edd53ef880bbb8))
- Add new layout and icons components ([6e43f64](https://github.com/akira-io/laravel-sisp/commit/6e43f64cb059be2673be5295ac96bf78b2308e73))
- Enhance payment processing with strict types and improved request handling ([34af376](https://github.com/akira-io/laravel-sisp/commit/34af3766fd38eba77df18de78810815a87adc3fc))
- Add configuration management and event handling for SISP transactions ([03235bb](https://github.com/akira-io/laravel-sisp/commit/03235bbd4bece79dbc63c790adce40298e18144f))
- Implement Generator interface in merchant and timestamp generator actions ([6e4d3b0](https://github.com/akira-io/laravel-sisp/commit/6e4d3b01ac138aec190c5ab0604aa9a489497636))
- Add GitHub Actions workflow for releasing notifications to Discord ([a70205a](https://github.com/akira-io/laravel-sisp/commit/a70205a649dfd8cf1577cc7236f083916fb5b470))
- Add configuration methods for purchase views and update transaction handling ([cd0667f](https://github.com/akira-io/laravel-sisp/commit/cd0667f84d15fb3ce649ee2bb0bddf608cd089d5))
- Add enums for InvoiceStatus and TransactionStatus, and implement transaction handling actions ([fbffa14](https://github.com/akira-io/laravel-sisp/commit/fbffa1428794f943d5c4c8cf4ae060922ae46c5e))
- Implement blacklist and rate limiting features with associated models and actions ([5275ccb](https://github.com/akira-io/laravel-sisp/commit/5275ccbd916315f6929813c1eb15884e7b9d66bf))
- Add stack detection for Inertia and Blade, and update view publishing logic ([0b85518](https://github.com/akira-io/laravel-sisp/commit/0b8551875e5c9e50da6d235bd2189a3a47233a4b))
- Enhance Inertia support with customizable components and add middleware for transaction protection ([ccb0147](https://github.com/akira-io/laravel-sisp/commit/ccb0147e9df9c8f2e7789fece97eaac3fae58a33))
- Implement GET and POST handling in CallbackController and enhance payment response views ([8e5b53b](https://github.com/akira-io/laravel-sisp/commit/8e5b53b057a7149360ad91f35993b4fae46e9433))
- Denormalize customer data to invoice and use in PDF generation ([28b9c71](https://github.com/akira-io/laravel-sisp/commit/28b9c711b66cb9516d892c14f33e473275a94386))
- Update CallbackController and related actions for improved transaction handling and metadata storage ([5f5fc66](https://github.com/akira-io/laravel-sisp/commit/5f5fc66950b5107e2a906ff4fb8368fd54c19513))
- Implement GET and POST handling in CallbackController and enhance payment response views ([bce5ef2](https://github.com/akira-io/laravel-sisp/commit/bce5ef2fe4928f168267780ffb7b41bb210b0f46))
- Expand error and success message types with comprehensive labels and remove posID from payment fields ([04b446f](https://github.com/akira-io/laravel-sisp/commit/04b446f50846ef6eeddc544d463b5022c6cf4006))
- Implement fingerprint validation in payment callback and streamline error message handling ([21ca238](https://github.com/akira-io/laravel-sisp/commit/21ca238b8b6c2da38d36b35059fea078ce62ddfd))
- Refactor payment response handling and introduce new validation actions ([cd4ace7](https://github.com/akira-io/laravel-sisp/commit/cd4ace748f02e14b962fb039d097982706c97749))
- Enhance payment response handling with structured error responses and fingerprint generation ([ccd9331](https://github.com/akira-io/laravel-sisp/commit/ccd93319fa84f749408339e55f6801751515bb56))
- Implement structured translations for payment response messages and add retry functionality ([1d44326](https://github.com/akira-io/laravel-sisp/commit/1d44326cdd97621dd004e9a84dfc9079c6e5f3d1))
- Enhance sandbox payload generation with random identifiers and improve test case structure ([16e1efa](https://github.com/akira-io/laravel-sisp/commit/16e1efabb719a208bde16d13405bb420f765b6d1))
- Add new identifiers to peck.json for enhanced functionality ([d1f572c](https://github.com/akira-io/laravel-sisp/commit/d1f572c38aa8e5a51ce4656c1b188b234ea50754))
- Enhance invoice generation with configurable company details and PDF URL retrieval ([c4037a9](https://github.com/akira-io/laravel-sisp/commit/c4037a9550199c1534d18204efcc5c91dd30bdf7))
- Add publicly accessible PDF URL to invoice generation ([054e2df](https://github.com/akira-io/laravel-sisp/commit/054e2df0f81e339fb7dfc8cba2ba8b06ecd8b7d1))
- Add strict types declaration and improve code consistency across multiple files ([8761f0f](https://github.com/akira-io/laravel-sisp/commit/8761f0f82afeb8084dce853d79b7d24f72e60787))
- Update transaction cancellation flow and improve routing for user cancellations ([6f41aba](https://github.com/akira-io/laravel-sisp/commit/6f41aba05717cce9a7c7ee8a082b77494ff68e64))
- Add release script and update dependencies for improved release management ([ef1ab86](https://github.com/akira-io/laravel-sisp/commit/ef1ab86714187662591132f0755417e0c2c9b7bd))
- Add return types to various methods for improved type safety and clarity ([94ebdec](https://github.com/akira-io/laravel-sisp/commit/94ebdec712b5046ce29f18a6e42c7f268cc26c3c))
- Update transaction cancellation logic and adjust test coverage settings ([ac4653d](https://github.com/akira-io/laravel-sisp/commit/ac4653d8b362603ee3128b8e9898807f2197b4c5))
- Add driftingly/rector-laravel dependency and improve type hints for clarity ([0035295](https://github.com/akira-io/laravel-sisp/commit/0035295f7875efe632b00f54cbb05dad941bc744))
- Refactor various methods for improved clarity and consistency, including exception handling and query usage ([9eb0099](https://github.com/akira-io/laravel-sisp/commit/9eb00999c27c632f2361c969a3f2efc4d228db95))


### Other

- Initial commit ([0c1c1c7](https://github.com/akira-io/laravel-sisp/commit/0c1c1c7dc5907bbc8f2c7d0c77c7110a18fbe272))
- Merge pull request #3 from akira-io/dependabot/github_actions/dependabot/fetch-metadata-2.3.0

chore(deps): bump dependabot/fetch-metadata from 2.2.0 to 2.3.0 ([771a2d0](https://github.com/akira-io/laravel-sisp/commit/771a2d0f4c7dd424fc7994003d8194ef41d2055e))
- Update issue templates ([d19ab3f](https://github.com/akira-io/laravel-sisp/commit/d19ab3feb872ae008c62e29b08d4750ab8171842))
- Delete .github/ISSUE_TEMPLATE/bug.yml ([135a1eb](https://github.com/akira-io/laravel-sisp/commit/135a1eb2c78bf27f3da6a5357f7b5126892d95ce))
- Delete .github/ISSUE_TEMPLATE/config.yml ([57d7af3](https://github.com/akira-io/laravel-sisp/commit/57d7af3bbe0cea496a31274b32d50adddf2e3942))
- Wip ([be907fd](https://github.com/akira-io/laravel-sisp/commit/be907fd703a572a3648c9f3ef1727d6f16b97f17))
- Wip ([9c7c1dd](https://github.com/akira-io/laravel-sisp/commit/9c7c1dd5af2f7216579e85311672a255b66526f5))
- Merge pull request #9 from akira-io/dependabot/github_actions/dependabot/fetch-metadata-2.4.0

chore(deps): bump dependabot/fetch-metadata from 2.3.0 to 2.4.0 ([80ad181](https://github.com/akira-io/laravel-sisp/commit/80ad1811b828d2463dafcb63d067b8227ea682cf))
- Merge pull request #10 from akira-io/dependabot/github_actions/SethCohen/github-releases-to-discord-1.19.0

chore(deps): bump SethCohen/github-releases-to-discord from 1.16.2 to 1.19.0 ([0591594](https://github.com/akira-io/laravel-sisp/commit/05915942cb7f639e50cf21b037d36d11b989f768))
- Merge pull request #8 from akira-io/dependabot/github_actions/actions/checkout-5

chore(deps): bump actions/checkout from 4 to 5 ([50b952f](https://github.com/akira-io/laravel-sisp/commit/50b952fe241cb7520aef92bb1b2fe374c1b0abcc))

