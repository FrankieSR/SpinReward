# Spin Reward Technical Overview

This document explains how the `Doroshko_SpinReward` module works.

It is written for:

- Magento developers
- technical project managers
- integrators
- support engineers

The language is kept simple.

---

## 1. Module Purpose

The module adds a Spin Reward promotion to Magento.

It allows a store to:

- show a popup or CTA on the storefront
- collect customer email before spin
- validate spin rules
- select a wheel sector
- generate a coupon for winning results
- send a coupon email
- track spin analytics
- show analytics in Magento Admin

This module is not only a visual popup.
It also includes backend validation, coupon flow, analytics, and admin management.

---

## 2. Main Functional Areas

The module has four main areas:

1. Admin wheel management
2. Frontend popup and spin flow
3. Coupon and email processing
4. Analytics and reporting

These parts work together, but each part has its own responsibility.

---

## 3. Admin Area

Admin users can manage wheels from:

- `Spin Reward -> Spin Reward Wheels`
- `Spin Reward -> Spin Reward Analytics`
- `Spin Reward -> Configuration`

### Wheel management

The wheel form is defined by Magento UI components.

Main file:

- [wishreward_wheel_form.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/adminhtml/ui_component/wishreward_wheel_form.xml)

Wheel list:

- [wishreward_wheel_listing.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/adminhtml/ui_component/wishreward_wheel_listing.xml)

Main admin controllers:

- [Index.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Controller/Adminhtml/Wheel/Index.php)
- [Edit.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Controller/Adminhtml/Wheel/Edit.php)
- [NewAction.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Controller/Adminhtml/Wheel/NewAction.php)
- [Save.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Controller/Adminhtml/Wheel/Save.php)
- [Delete.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Controller/Adminhtml/Wheel/Delete.php)
- [MassDelete.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Controller/Adminhtml/Wheel/MassDelete.php)

### Configuration

System configuration is defined in:

- [system.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/etc/adminhtml/system.xml)

At the moment, the main config setting is:

- `Enable Module`

### ACL and menu

Access control and admin menu are defined in:

- [acl.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/etc/adminhtml/acl.xml)
- [menu.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/etc/adminhtml/menu.xml)

Best practice:

- keep ACL resources aligned with controller `ADMIN_RESOURCE`
- do not add admin routes without real controllers

---

## 4. Frontend Area

The frontend part shows the wheel popup and handles customer actions.

Main layout files:

- [default.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/frontend/layout/default.xml)
- [wishreward_wheel_popup.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/frontend/layout/wishreward_wheel_popup.xml)

Main templates:

- [init.phtml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/frontend/templates/init.phtml)
- [wheel_popup.phtml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/frontend/templates/wheel_popup.phtml)

Main JavaScript files:

- [init.js](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/frontend/web/js/init.js)
- [wheel-handler.js](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/frontend/web/js/wheel-handler.js)
- [lotteryWheelWidget.js](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/frontend/web/js/lotteryWheelWidget.js)

The frontend supports:

- popup display
- CTA display
- popup triggers
- wheel rendering
- spin request submission
- frontend analytics events

---

## 5. Wheel Data Model

The main business entity is the wheel.

Core model files:

- [Wheel.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/Wheel.php)
- [WheelRepository.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/WheelRepository.php)
- [WheelInterface.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Api/Data/WheelInterface.php)
- [Wheel.php resource model](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/ResourceModel/Wheel.php)

Wheel data includes:

- title
- active status
- customer limits
- date range
- store views
- customer groups
- wheel sector config
- popup content
- CTA config
- popup trigger config

The wheel form stores both business data and visual configuration in one admin object.

---

## 6. Spin Flow

The main spin request is handled by:

- [SpinWheel.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Controller/Ajax/SpinWheel.php)

The flow is:

1. Customer opens popup or clicks CTA
2. Customer submits email and optional wish data
3. Backend validates request
4. Backend checks wheel rules
5. Backend checks spin limit
6. Backend runs optional wish validation
7. Backend selects sector
8. Backend generates coupon for winning sector
9. Backend sends email if needed
10. Backend saves analytics and response

This controller is the central business flow of the module.

Best practice:

- keep all final validation on the backend
- do not trust frontend checks

---

## 7. Customer Identity and Spin Limits

Spin limits are handled by:

- [SpinLimitValidator.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/SpinLimitValidator.php)

The module currently uses controlled identity rules:

- for logged-in users, the backend uses account email
- for guests, the backend uses submitted email

This helps avoid simple limit bypass for logged-in users.

After a successful spin, the frontend popup is also suppressed.

This logic is handled by:

- [SpinCompletionState.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/SpinCompletionState.php)

This service:

- sets a first-party cookie after successful spin
- checks if a wheel is already completed
- helps server-side rendering decide if CTA or popup should be shown

Best practice:

- use server-side checks as source of truth
- use browser storage only as a UX helper

---

## 8. Popup Rendering Rules

The module does not rely only on JavaScript to decide if the popup should be visible.

Server-side control exists in:

- [InitViewModel.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/ViewModel/InitViewModel.php)
- [WheelPopup.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Block/WheelPopup.php)

This means:

- CTA can be hidden before frontend render
- popup can be blocked before HTML output
- completed spins do not reopen the popup on the next page

This is closer to Magento best practice than frontend-only logic.

---

## 9. Coupon Generation

Winning sectors are connected to Magento Cart Price Rules.

Coupon generation is handled by:

- [CouponGenerator.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/CouponGenerator.php)

Important note:

The module depends on correct Cart Price Rule setup.

If a rule is inactive or misconfigured, the wheel may still return a winning result, but the coupon flow can fail or become invalid for checkout.

Best practice:

- validate every winning sector against a real sales rule
- do not treat wheel config and sales rule config as separate business tasks

---

## 10. Email Sending

Coupon email sending is handled by:

- [EmailSender.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/EmailSender.php)
- [winner_coupon.html](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/frontend/email/winner_coupon.html)

The module records email result status in analytics.

Best practice:

- test template rendering in the correct store view
- confirm sender configuration in Magento
- check failure cases, not only successful send

---

## 11. Wish Validation

The module includes optional local wish validation.

Main file:

- [WishValidationValidator.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/WishValidationValidator.php)

This part can be used to:

- validate submitted wish text
- accept or reject content
- store validation result in analytics

If validation is active, it becomes part of the business funnel.

That means rejected wishes can directly reduce conversion.

Best practice:

- monitor validation reject rate
- monitor validation error rate
- do not enable strict validation without real business review

---

## 12. Analytics Architecture

The analytics part has more than one layer.

### Raw spin analytics

Main table:

- `wishreward_spin_analytics`

This table stores spin-level data such as:

- wheel
- email state
- result
- sector
- coupon
- device
- status
- block reason
- order data when available

Main code:

- [SpinAnalytics.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/Analytics/SpinAnalytics.php)
- [SpinAnalyticsRepository.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/Analytics/SpinAnalyticsRepository.php)
- [SpinAnalyticsProvider.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/Analytics/SpinAnalyticsProvider.php)

### Event log

Main table:

- `wishreward_spin_event`

This table stores event-based records, for example:

- popup impression
- CTA click
- popup open
- spin submit
- spin validated
- coupon generated
- email sent
- coupon applied
- order placed
- blocked limit event

Event logging is handled by:

- [EventLogger.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/Analytics/EventLogger.php)

### Daily aggregates

Main table:

- `wishreward_analytics_daily`

This table stores daily summary metrics for faster admin reporting.

Best practice:

- use raw data as source of truth
- use aggregates for dashboards
- do not calculate all charts from browser-loaded rows

---

## 13. Admin Analytics Dashboard

Main admin files:

- [Index.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Controller/Adminhtml/Analytics/Index.php)
- [Payload.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Controller/Adminhtml/Analytics/Payload.php)
- [ExportCsv.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Controller/Adminhtml/Analytics/ExportCsv.php)
- [ReportService.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Model/Analytics/ReportService.php)

Main UI files:

- [analytics-dashboard.js](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/adminhtml/web/js/analytics/analytics-dashboard.js)
- [analytics-dashboard.html](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/adminhtml/web/template/analytics/analytics-dashboard.html)
- [wishreward_analytics_grid.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/adminhtml/ui_component/wishreward_analytics_grid.xml)

The dashboard uses server-side payload generation.

This is important because:

- it scales better
- it is safer for export
- it is more accurate than browser-side aggregation from paged rows

Current dashboard features include:

- KPI values
- charts
- wheel and sector breakdown
- latest spins data
- CSV export

---

## 14. Order Tracking

The module also tracks order lifecycle for wheel coupons.

Main observer:

- [OrderLifecycleObserver.php](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/Observer/OrderLifecycleObserver.php)

This part links:

- spin
- coupon
- order

This is needed for:

- coupon applied tracking
- order conversion tracking
- revenue analytics
- discount analytics

Best practice:

- treat coupon-to-order linking as part of analytics accuracy
- verify observer behavior after checkout customization

---

## 15. Database Schema

Main schema files:

- [db_schema.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/etc/db_schema.xml)
- [db_schema_whitelist.json](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/etc/db_schema_whitelist.json)

The module uses Magento declarative schema.

Important point:

- schema XML and whitelist must stay in sync

If they are not aligned, production deploy can fail or become unsafe.

Best practice:

- update whitelist after schema changes
- review indexes after adding analytics fields

---

## 16. Dependency Injection

Main DI files:

- [etc/di.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/etc/di.xml)
- [etc/adminhtml/di.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/etc/adminhtml/di.xml)
- [etc/frontend/di.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/etc/frontend/di.xml)

This module uses standard Magento DI.

Best practice:

- keep DI arguments aligned with constructor signatures
- run `setup:di:compile` before production deploy
- do not leave dead DI config in the module

---

## 17. Event and Route Entry Points

Important route files:

- [etc/frontend/routes.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/etc/frontend/routes.xml)
- [etc/adminhtml/routes.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/etc/adminhtml/routes.xml)

Important event config:

- [events.xml](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/etc/events.xml)

Frontend request entry points include:

- popup loading
- spin request
- analytics event request

Admin entry points include:

- wheel CRUD
- analytics page
- CSV export

Best practice:

- every route should have a real controller
- every observer should be tested on a real business event

---

## 18. Frontend Styling

Frontend styles are stored in:

- [_module.less](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/frontend/web/css/source/_module.less)
- [_popup.less](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/frontend/web/css/source/_popup.less)
- [_cta.less](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/frontend/web/css/source/_cta.less)

Admin visual helper styles are stored in:

- [popup-settings-visual.css](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/adminhtml/web/css/popup-settings-visual.css)
- [wheel-config-visual.css](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/view/adminhtml/web/css/wheel-config-visual.css)

These files control presentation only.

Business logic should stay in PHP services, controllers, and validated JS flow.

---

## 19. Deployment Notes

Before production deploy:

1. check PHP version compatibility
2. run `setup:upgrade`
3. run `setup:di:compile`
4. deploy static content if needed
5. flush cache
6. test one full spin flow
7. test one winning coupon
8. open analytics page

Recommended checks:

- admin wheel save works
- admin wheel delete works
- frontend popup shows
- frontend popup hides after completed spin
- analytics rows are created
- CSV export works

---

## 20. Known Design Choices

The module currently uses these important design choices:

- backend validation is the source of truth
- popup suppression is controlled on the server side
- analytics dashboard uses server-side payload generation
- raw analytics, events, and daily aggregates are separate data layers
- Magento Cart Price Rules stay outside this module, but are required for winning sectors

These choices are good for production because they reduce frontend-only logic and improve reporting structure.

---

## 21. Recommended Future Improvements

Some improvements are still useful for a stronger production version:

- add aggregate rebuild cron or CLI command
- add stricter idempotency for repeated order events
- expand anti-abuse logic by email, IP, and session summaries
- improve privacy-policy link configuration
- add more automated integration tests

These are improvements, not basic module requirements.

---

## 22. Summary

`Doroshko_SpinReward` is a Magento promotion module with:

- wheel management
- popup and CTA frontend flow
- backend spin validation
- coupon generation
- email sending
- event logging
- dashboard analytics

It is more than a visual widget.
It should be treated as a business promotion system with customer, coupon, and reporting logic.
