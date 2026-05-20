# Spin Reward User Guide

This guide is for store admins and marketers.

It explains how to:

- enable the module
- create a wheel
- set prizes
- control popup behavior
- read basic analytics

This guide uses simple English and focuses on daily work in Magento Admin.

---

## 1. What This Module Does

Spin Reward shows a reward wheel popup on the storefront.

Customers can:

- open the popup
- enter their email
- spin the wheel
- win or not win a reward
- receive a coupon if the result is a winning sector

The module also stores spin results and shows analytics in Magento Admin.

---

## 2. Before You Start

Check these points before you create your first wheel:

1. The module is enabled in Magento configuration.
2. You already have Magento Cart Price Rules for winning sectors.
3. You know which store views should use the wheel.
4. You know how many attempts each customer should have.

Best practice:

- create coupon rules first
- then build the wheel
- then test on storefront
- then check analytics

---

## 3. Enable the Module

Go to:

**Spin Reward -> Configuration**

Find:

- `Enable Module`

Set it to `Yes` and save config.

Best practice:

- enable the module first in staging
- test one full spin flow
- only then enable it in production

---

## 4. Open the Wheel List

Go to:

**Spin Reward -> Spin Reward Wheels**

Here you can:

- see all wheels
- add a new wheel
- edit an existing wheel
- delete one or many wheels

---

## 5. Create a New Wheel

Click:

**Add New Wheel**

You will see several sections:

- General Settings
- Wheel Configuration
- Popup Settings
- CTA Configuration
- Popup Trigger Configuration

Best practice:

- save the wheel only after all sections are checked
- use clear names, for example `Summer Promo DE Store`

---

## 6. General Settings

This section controls the main wheel behavior.

Important fields:

- `Title`  
  Internal and visual name of the wheel.

- `Is Active`  
  Turns the wheel on or off.

- `Win Message`  
  Text shown after a winning spin.

- `No Win Message`  
  Text shown after a losing spin.

- `Attempts Per User`  
  Number of allowed spins for one customer in the selected period.

- `Attempts Period`  
  The limit period: `day`, `week`, `month`, `year`, or `forever`.

- `Store View`  
  Store views where the wheel is active.

- `Allowed Customer Groups`  
  Customer groups that can use the wheel.

- `From` / `To`  
  Start and end dates of the campaign.

Best practice:

- keep `Attempts Per User` at `1` for most campaigns
- always set `Store View` correctly
- use start and end dates for seasonal promotions
- disable old wheels instead of reusing them for a different campaign

---

## 7. Wheel Configuration

This section defines the sectors of the wheel.

Each sector should have:

- a label
- win or no-win logic
- probability or weight
- Cart Price Rule for winning sectors

Example:

- `5% Off`
- `10% Off`
- `Free Shipping`
- `No Win`

Best practice:

- always include at least one no-win sector if you do not want every customer to win
- check probabilities carefully
- connect each winning sector to the correct Cart Price Rule
- test every winning sector before launch

Important:

If a sector is a winning sector but has a wrong rule, the customer experience will break.

---

## 8. Popup Settings

This section controls the popup content.

Important fields:

- `Popup Title`
- `Popup Description`
- `Company Logo`
- `Company Text`
- `Button Text`
- `Decline Button Text`
- `Close Button Text`
- `Terms Text`
- `Enable Wish Area`

Best practice:

- keep text short and easy to read
- use one clear message
- avoid too much legal text in the main area
- move full legal information to the terms or privacy page

If you use the wish area:

- explain clearly what the customer should write
- keep the input task simple

---

## 9. CTA Configuration

CTA means the button or small block that opens the popup.

Important fields:

- `Is CTA Enabled`
- `CTA Image`
- `CTA Title`
- `CTA Button Text`
- `CTA Position`
- `CTA Custom CSS`

If CTA is enabled, the customer can open the wheel manually.

Best practice:

- use CTA when you want a visible entry point on the page
- do not overload CTA with too much text
- choose a position that does not block important page elements
- use custom CSS only if standard styling is not enough

---

## 10. Popup Trigger Configuration

This section controls automatic popup opening.

Available triggers:

- `Enable Scroll Trigger`
- `Scroll Percentage`
- `Enable Timeout Trigger`
- `Timeout Duration (milliseconds)`
- `Enable Exit Intent Trigger`

Best practice:

- use only one or two trigger types at the same time
- avoid showing the popup too early
- start with a moderate scroll trigger or timeout
- use exit intent only if it fits your store UX

Recommended starting setup:

- Scroll trigger: enabled
- Scroll percentage: medium value
- Timeout trigger: optional
- Exit intent: optional

Do not use all triggers aggressively. This can hurt user experience.

---

## 11. Save and Test the Wheel

After configuration:

1. Click `Save Wheel`
2. Open the storefront in the correct store view
3. Check if the popup or CTA appears
4. Make one real test spin
5. Check if the result matches the wheel setup
6. If you win, check the coupon flow

Best practice:

- test as guest
- test as logged-in customer
- test on desktop and mobile
- test one winning and one no-win result if possible

---

## 12. How Spin Limits Work

The module can limit how many times one customer can spin.

Example:

- `Attempts Per User = 1`
- `Attempts Period = day`

This means one customer can spin one time per day.

Important:

- limits help protect the campaign
- the frontend also stops showing the popup after a successful spin
- backend validation still protects the wheel if the customer tries again

Best practice:

- use strict limits for discount campaigns
- do not set unlimited spins unless you really need it

---

## 13. How Coupons Should Be Prepared

Winning sectors should use valid Magento Cart Price Rules.

Before launch, check:

1. the rule is active
2. the dates are correct
3. the websites match your wheel
4. the discount logic is correct
5. coupon generation works

Best practice:

- use clear rule names
- do not reuse random old rules without checking conditions
- test one coupon from each winning sector

---

## 14. Analytics Dashboard

Go to:

**Spin Reward -> Spin Reward Analytics**

Here you can review:

- total spins
- wins
- win rate
- spins by day
- spins by wheel
- sector performance
- device type
- UTM source
- latest spins

This helps you understand if the campaign is working.

Best practice:

- check analytics after launch day
- compare results by wheel
- review blocked attempts
- watch the latest spins table for strange behavior

---

## 15. Latest Spins Table

The latest spins table is useful for support and quick checks.

You can review:

- wheel
- email
- result
- prize
- device
- status
- block reason
- order data if available

Best practice:

- use filters before export
- check blocked records if customers report problems
- review repeated spins for abuse patterns

---

## 16. Daily Work Tips

For a normal campaign, this is a good workflow:

1. Enable the module
2. Create Cart Price Rules
3. Create one wheel
4. Set limits
5. Set popup text
6. Set CTA or triggers
7. Save and test
8. Launch
9. Review analytics daily

Best practice:

- use one wheel per campaign
- keep names clean
- archive or disable old wheels
- do not edit live wheels too often during a campaign

---

## 17. Common Mistakes

### Popup does not show

Check:

- module is enabled
- wheel is active
- store view is correct
- date range is valid
- customer group is allowed
- popup trigger or CTA is configured

### Customer wins but no coupon is useful

Check:

- Cart Price Rule is active
- conditions are correct
- website and customer conditions match

### Customer cannot spin

Check:

- attempts limit
- time period
- customer group
- wheel dates

### Analytics looks empty

Check:

- the wheel was used in the selected date range
- filters are not too strict

---

## 18. Production Checklist

Before you go live, confirm all points below:

- Module is enabled
- Wheel is active
- Correct store views are selected
- Correct customer groups are selected
- Start and end dates are correct
- Winning sectors are connected to valid Cart Price Rules
- Popup text is reviewed
- CTA and triggers are reviewed
- One full storefront test is completed
- Analytics page is loading

---

## 19. Quick Admin Paths

Use these admin paths:

- `Spin Reward -> Configuration`
- `Spin Reward -> Spin Reward Wheels`
- `Spin Reward -> Spin Reward Analytics`

---

## 20. Final Advice

Start with one simple wheel.

Do not make the first campaign too complex.

The safest first setup is:

- 1 active wheel
- 1 spin per customer
- short popup text
- clear CTA
- tested coupon rules
- daily analytics check

This gives a safer launch and easier support work.
