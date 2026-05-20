# Spin Reward

Magento 2 promotion module with a spin-to-win wheel, popup CTA, coupon generation, anti-abuse limits, and admin analytics.

## Modules

- `Doroshko_SpinReward` - main Luma module
- `Doroshko_SpinRewardHyvaCompatibility` - Hyva theme compatibility module

## Requirements

- PHP 8.1+
- Magento framework 103.0.x
- Hyva Theme for the compatibility module

## Documentation

The docs are stored in the `docs` folder:

- [User Guide](docs/User-Guide.md) - simple guide for merchants and admin users
- [Technical Overview](docs/Technical-Overview.md) - module structure and flow

## License

This module is proprietary software.

You may use it in your own Magento projects and in client Magento projects.
You may modify it for project needs.

You may not:

- resell this module
- redistribute this module as a standalone product
- sublicense this module
- publish this module as your own product

See [LICENSE.md](LICENSE.md) for the full license text.

## What it does

- shows a spin wheel popup on the storefront
- generates coupons from Magento cart price rules
- limits repeated spins by email, customer, and website scope
- stores analytics for spins, wins, coupons, and orders
- supports both Luma and Hyva storefronts

## Admin area

Main admin sections:

- Spin Reward Wheels
- Spin Reward Analytics
- Spin Reward Settings

## Wheel setup

To create a new wheel:

1. Go to **Spin Reward > Spin Reward Wheels**
2. Click **Add New Wheel**
3. Configure the wheel settings, sectors, popup, CTA, and triggers

## Hyva compatibility

If your store uses Hyva, install the separate `Doroshko_SpinRewardHyvaCompatibility` module together with the main module.
It provides Hyva templates, layout integration, and frontend rendering for the same wheel flow.
