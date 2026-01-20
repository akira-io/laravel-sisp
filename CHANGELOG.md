# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


# [0.4.0](https://github.com/akira-io/laravel-sisp/compare/0.3.0...0.4.0) (2026-01-20)


### Features

* add 3D Secure purchaseRequest support with customer data validation ([2498acc](https://github.com/akira-io/laravel-sisp/commit/2498acc0864bfcf1a0838d2c1bfb526a684771cd))
* add Countries utility with ISO codes, names, and flag support ([90b4d5b](https://github.com/akira-io/laravel-sisp/commit/90b4d5b12435f433dc264ebc24f44c5b9f17f00a))
* add CountriesController and route to provide country data via API ([eeebbf9](https://github.com/akira-io/laravel-sisp/commit/eeebbf906d2380c86f7a355193541b7aa5db8a62))

# [0.3.0](https://github.com/akira-io/laravel-sisp/compare/0.2.0...0.3.0) (2026-01-07)


### Bug Fixes

* fixed invoice loading to use type hints for query builder ([74de11c](https://github.com/akira-io/laravel-sisp/commit/74de11c04e3f813a9f30b0d784b56802fac13b0d))


### Features

* add 'roadmap' to the list of technologies in peck.json ([6df9371](https://github.com/akira-io/laravel-sisp/commit/6df9371f65f5bfaad1e087c7739b90b17846da17))
* add withoutEmail method to TransactionFactory for optional customer email ([052b272](https://github.com/akira-io/laravel-sisp/commit/052b2720a0a1b8a688f0df0d6dedbb5a8890aaf8))

# [0.2.0](https://github.com/akira-io/laravel-sisp/compare/0.1.0...0.2.0) (2025-12-05)

### Features

* add French localization for error messages and payment
  responses ([245e687](https://github.com/akira-io/laravel-sisp/commit/245e68700ff703b41c51edd0fc829a099a19e3ee))
* add locale field to transaction model and database
  schema ([016c1ff](https://github.com/akira-io/laravel-sisp/commit/016c1ffcb179384f8734b9f11e954e700cdb8189))
* add locale property to Transaction model and update GenerateInvoicePdfAction to use
  it ([abfb9e0](https://github.com/akira-io/laravel-sisp/commit/abfb9e036509662af0b1b4a6c0dc783b062bf2bb))
* add locale support to transaction processing and related
  actions ([1ef4212](https://github.com/akira-io/laravel-sisp/commit/1ef4212b59d75b1420cd1853f30e9f7c2f67d285))
* add localization support for payment messages in French and
  Portuguese ([8dbe68b](https://github.com/akira-io/laravel-sisp/commit/8dbe68b401cb3094784f6e8ca4345ac86e98d23d))
* add translation support for payment form and response
  components ([aaa1b9f](https://github.com/akira-io/laravel-sisp/commit/aaa1b9fcbc88a284a6f41bda9eee9b71953e51a3))
* enhance attribute encryption and decryption handling for
  arrays ([5250608](https://github.com/akira-io/laravel-sisp/commit/5250608f1f746c9591bbdca28b39b8b07bbf739b))
* enhance Laravel SISP installation command with additional publishing options and error
  handling ([15f3809](https://github.com/akira-io/laravel-sisp/commit/15f38094b30e9401002c9bf2943b35241581a661))
* enhance payment form localization by passing locale to render
  methods ([76bbd52](https://github.com/akira-io/laravel-sisp/commit/76bbd5288440cc33578296587a14569631d1604c))
* enhance transaction cancellation process and improve installation command
  prompts ([7a95936](https://github.com/akira-io/laravel-sisp/commit/7a959366944b09cc65baad712830895a462940b0))
* improve transaction cancellation logic and streamline rate limit identifier
  assignment ([850bd0b](https://github.com/akira-io/laravel-sisp/commit/850bd0bd6a7b9317d30af0401f70bda34b2d8dd3))

# 0.1.0 (2025-12-02)

### Bug Fixes

* add exception documentation for payment response handling
  methods ([7c6222a](https://github.com/akira-io/laravel-sisp/commit/7c6222af483d6e27d47f01d1acbd669d5876f2dc))
* allow GET requests to sandbox
  endpoint ([4fea13b](https://github.com/akira-io/laravel-sisp/commit/4fea13b527e40c46c7f3a586b8bd3bfd7503db7f))
* correct SISP sandbox
  functionality ([51f044c](https://github.com/akira-io/laravel-sisp/commit/51f044c13b47626f0c6421191e93953808bcbd31))
* remove posID from payment request fields in
  RenderPaymentFormAction ([adb9fcb](https://github.com/akira-io/laravel-sisp/commit/adb9fcb63d8b1010fd93b6f105e349f8484b46f1))
* support POST form data in sandbox
  controller ([7f216e3](https://github.com/akira-io/laravel-sisp/commit/7f216e3cf04f6fc778c983f8ec981e1c4de12d7a))

### Features

* add configuration management and event handling for SISP
  transactions ([a02ff95](https://github.com/akira-io/laravel-sisp/commit/a02ff95d2031e27a6d60937916bc875fcceafc21))
* add configuration methods for purchase views and update transaction
  handling ([a8b2c6a](https://github.com/akira-io/laravel-sisp/commit/a8b2c6a63a8e7076edc64bd8c98616933215ecfd))
* add driftingly/rector-laravel dependency and improve type hints for
  clarity ([f8209ba](https://github.com/akira-io/laravel-sisp/commit/f8209ba7631008d1f4d07bd08c7ca28ccbbad45d))
* add GitHub Actions workflow for releasing notifications to
  Discord ([5d550fa](https://github.com/akira-io/laravel-sisp/commit/5d550fa213db85f6afd3d5e529ae2086347357ed))
* add new identifiers to peck.json for enhanced
  functionality ([efe3501](https://github.com/akira-io/laravel-sisp/commit/efe35015b4d42e47065595e7d6b383d11663c4a0))
* add new layout and icons
  components ([4616ae1](https://github.com/akira-io/laravel-sisp/commit/4616ae1fecb8bd8dda5dee47e8fd01e0fe6a87cc))
* add publicly accessible PDF URL to invoice
  generation ([b697b13](https://github.com/akira-io/laravel-sisp/commit/b697b13dd934dda119ab19e38321792e417ccf5b))
* add release script and update dependencies for improved release
  management ([79dc134](https://github.com/akira-io/laravel-sisp/commit/79dc13466f04915d7403a21dc4bd331c9e0b3f7b))
* add return types to various methods for improved type safety and
  clarity ([90ee79d](https://github.com/akira-io/laravel-sisp/commit/90ee79df8dda8c2a5fda4d4c3583e481dd9b9db5))
* add stack detection for Inertia and Blade, and update view publishing
  logic ([d4876ba](https://github.com/akira-io/laravel-sisp/commit/d4876bab6bf303d4d6763e88dd110eb6016c4e69))
* add strict types declaration and improve code consistency across multiple
  files ([802e7b6](https://github.com/akira-io/laravel-sisp/commit/802e7b6c876e9a8ffdad9a66640cb798435d2779))
* denormalize customer data to invoice and use in PDF
  generation ([b381074](https://github.com/akira-io/laravel-sisp/commit/b381074e1d22e8adf0f04aae0830a7f455c3e9c2))
* enhance Inertia support with customizable components and add middleware for transaction
  protection ([7861361](https://github.com/akira-io/laravel-sisp/commit/786136175f07071769ed913f91f26e30dc4a929f))
* enhance invoice generation with configurable company details and PDF URL
  retrieval ([1bf307b](https://github.com/akira-io/laravel-sisp/commit/1bf307b60fae096c2b941b937bfed4d87cdeffa0))
* enhance payment processing with strict types and improved request
  handling ([bb60530](https://github.com/akira-io/laravel-sisp/commit/bb60530d6b03c3d941b82fb0dbd8dc32c47a4e66))
* enhance payment response handling with structured error responses and fingerprint
  generation ([c9394e6](https://github.com/akira-io/laravel-sisp/commit/c9394e69dff4defa0ee4df27262267ee5d3a6fa7))
* enhance sandbox payload generation with random identifiers and improve test case
  structure ([e1706a3](https://github.com/akira-io/laravel-sisp/commit/e1706a3eb66f8bb116b4a8cf38dad4ad5badb088))
* expand error and success message types with comprehensive labels and remove posID from payment
  fields ([46690be](https://github.com/akira-io/laravel-sisp/commit/46690bea6533474cb6b78a3725ca264727c9b920))
* handle user cancellation in payment
  response ([831b7d4](https://github.com/akira-io/laravel-sisp/commit/831b7d4224c259bd883b0b1c3d49643c6ff4ec74))
* implement blacklist and rate limiting features with associated models and
  actions ([7e4ca15](https://github.com/akira-io/laravel-sisp/commit/7e4ca150c63918d238ce6445939c808dac8f0f75))
* implement fingerprint validation in payment callback and streamline error message
  handling ([429faa6](https://github.com/akira-io/laravel-sisp/commit/429faa631a194fc03d127cf8ee30d99e61874e20))
* implement Generator interface in merchant and timestamp generator
  actions ([0539f8c](https://github.com/akira-io/laravel-sisp/commit/0539f8c70c14dba86712b6e7e57c4b3cd5614c96))
* implement GET and POST handling in CallbackController and enhance payment response
  views ([1227ea1](https://github.com/akira-io/laravel-sisp/commit/1227ea1317c49e85879c4c081b2384f2b08a1520))
* implement GET and POST handling in CallbackController and enhance payment response
  views ([0797434](https://github.com/akira-io/laravel-sisp/commit/07974346ea703670f99b684fc6a41bb4f2fbd0ca))
* implement structured translations for payment response messages and add retry
  functionality ([9117314](https://github.com/akira-io/laravel-sisp/commit/911731484b50f986abda1c8b4f40e73a629200d3))
* payment request ([20c719a](https://github.com/akira-io/laravel-sisp/commit/20c719a906032121671ca475288a2eea63b4a301))
* refactor payment response handling and introduce new validation
  actions ([ba868fc](https://github.com/akira-io/laravel-sisp/commit/ba868fc2b1d62ee94778ed7ef4c32a7173176d31))
* refactor various methods for improved clarity and consistency, including exception handling and query
  usage ([e145bca](https://github.com/akira-io/laravel-sisp/commit/e145bcad190b9af4ca1b1a5b40c57b45121daf99))
* update CallbackController and related actions for improved transaction handling and metadata
  storage ([b7530b1](https://github.com/akira-io/laravel-sisp/commit/b7530b13c9476bfbfb7cc903b9eed3e4cc8fcf20))
* update transaction cancellation flow and improve routing for user
  cancellations ([d5a6332](https://github.com/akira-io/laravel-sisp/commit/d5a6332df3244329a16eb5ad16bd0f6e3b2c93ad))
* update transaction cancellation logic and adjust test coverage
  settings ([f6f48f2](https://github.com/akira-io/laravel-sisp/commit/f6f48f21930468fad35f9b604589daaff61d0fe6))
