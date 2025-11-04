# Inertia Setup for Laravel SISP

## Overview

When using Inertia with Laravel SISP, the install command automatically detects your setup and publishes the necessary components for you.

## Installation

Simply run the install command:

```bash
php artisan sisp:install
```

The command will:
1. Detect that you're using Inertia (React or Vue)
2. Publish the configuration
3. Publish migrations
4. Ask if you want to publish the Inertia components (for customization)
5. Run database migrations

## Configuration

To enable Inertia support in your `.env` file:

```env
SISP_USE_INERTIA=true
```

## Components

After running `php artisan sisp:install` and choosing to publish the components, they will be copied to `resources/js/pages/sisp/`.

Your `resources/js/app.tsx` will resolve them automatically:

```typescript
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    // ... rest of config
});
```

## Available Components

- `sisp/payment-form` - Payment form component that redirects to SISP gateway
- `sisp/payment-response` - Payment response/callback component showing transaction status

## Manual Publishing

If you didn't publish the components during install, you can do it manually:

```bash
php artisan vendor:publish --tag=sisp-inertia-components
```

For Vue applications:

```bash
php artisan vendor:publish --tag=sisp-vue-components
```

## Customization

You can customize the component paths in your `config/sisp.php`:

```php
'use_inertia' => [
    'enabled' => true,
    'payment_form_component' => 'sisp/payment-form',
    'payment_response_component' => 'sisp/payment-response',
],
```