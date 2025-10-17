# Cloudflare Turnstile for PrestaShop

## Overview

Replace annoying reCaptcha puzzles with user-friendly Cloudflare Turnstile. Improve customer experience and reduce cart abandonment by eliminating frustrating captcha challenges.

## Why Turnstile?

Cloudflare Turnstile provides a better user experience by eliminating frustrating captcha puzzles, while using smart browser behavior detection to identify bots. The service is privacy-friendly, improves conversion rates by reducing checkout friction, and is free for most use cases.

## Installation

### Method 1: Backoffice Upload

1. Go to Tags: https://github.com/blauwfruit/turnstile/tags
2. Download the latest zip
2. Go to PrestaShop admin panel
3. Navigate to `Modules` > `Module Manager`
4. Click `Upload a module` and select the downloaded zip
5. Install and configure the module

### Method 2: Git Submodule

```bash
# Navigate to your PrestaShop project's root directory
cd /path/to/your-prestashop-installation

# Add the Turnstile module as a submodule
git submodule add https://github.com/blauwfruit/turnstile modules/turnstile

# Pull the latest version
git submodule update --init --recursive
```

## Configuration

### 1. Get Cloudflare Turnstile Keys

1. Visit [Cloudflare Dashboard](https://dash.cloudflare.com/)
2. Navigate to Turnstile section
3. Create a new site
4. Copy your **Site Key** and **Secret Key**

### 2. Configure Module

1. Go to `Modules` > `Module Manager`
2. Find "Cloudflare Turnstile" and click **Configure**
3. Enable the module
4. Paste your **Site Key**
5. Paste your **Secret Key**
6. Click **Save**

## Protected Forms

The module automatically protects:

- ✅ Customer registration form
- ✅ Customer registration form in checkout
- ✅ Customer login form
- ✅ Customer login form in checkout
- ✅ Contact us page form
- ✅ Subscribe field of ps_emailsubscription

## Docker Development

For development or demo purposes, you can run Docker to test this module.

### Latest PrestaShop Version

```bash
git clone https://github.com/yourusername/turnstile .
docker compose up
```

Access your store at: http://localhost

Default admin credentials:
- Email: `demo@prestashop.com`
- Password: `prestashop_demo`

### Specific PrestaShop Version

```bash
git clone https://github.com/yourusername/turnstile .
docker compose down --volumes && export TAG=8.1.7-8.1-apache && docker compose up
```

Available tags: Check [PrestaShop Docker Hub](https://hub.docker.com/r/prestashop/prestashop/tags)

## Technical Details

### Requirements

- PrestaShop 1.7.0.0 or higher
- PHP 7.1 or higher
- Active Cloudflare Turnstile account (free)
- HTTPS recommended (but works on HTTP for testing)

## Troubleshooting

### Turnstile Widget Not Showing

- Check that the module is enabled
- Verify your Site Key is correct
- Check browser console for JavaScript errors

### Validation Always Fails

- Verify your Secret Key is correct
- Check that your server can reach Cloudflare's API
- Ensure `allow_url_fopen` is enabled in PHP

### Forms Still Submit Without Validation

- Clear PrestaShop cache
- Regenerate module hooks
- Check hook registration in Module Manager

## Support

For issues, feature requests, or contributions:
- Create an issue on GitHub
- Contact: support@blauwfruit.nl

## License

MIT License - See LICENSE file for details

## Credits

Developed by [blauwfruit](https://blauwfruit.nl)

## Changelog

### Version 1.0.0 (2025-10-17)
- Initial release
- Customer account form protection
- Contact form protection
- Admin configuration panel
- Docker development environment
