# Spin Reward Module

## Requirements

- PHP 8.1+
- Magento framework 103.0.x

## License

This module is proprietary software.

You may use it in your own Magento projects and in client Magento projects.
You may modify it for project needs.

You may not:

- resell this module
- redistribute this module as a standalone product
- sublicense this module
- publish this module as your own product

See [LICENSE.md](/Users/artemdoroshko/Sites/magento/src/app/code/Doroshko/SpinReward/LICENSE.md) for the full license text.

The **Spin Reward** module allows customers to spin a reward wheel and receive discount coupons based on configured **Cart Price Rules**.  
This documentation explains how to create, configure, and manage wheels in the admin panel.

---

## 📍 Accessing the Wheel Configuration

To create a new wheel:

1. Go to **Spin Reward > Spin Reward Wheels**
2. Click **Add New Wheel**
3. Configure the wheel using the tabs described below

---

## ⚙️ 1. General Settings

This section defines the core settings of the wheel and its customer messages.

### **Fields**

- **Title**  
  Title of the popup displayed to customers.

- **Win Message**  
  Message shown when a customer wins a prize.

- **No Win Message**  
  Message shown when a customer does not win.

- **Attempts Per User**  
  Number of attempts allowed for each user.

- **Attempts Period**  
  Time period during which attempts are counted  
  (e.g., *1 attempt per 24 hours*).

---

## 🎡 2. Wheel Configuration

Configure the individual sectors of the wheel.

Each sector can include:

- Sector label  
- Whether it is a win or no-win sector  
- Linked **Cart Price Rule**  
- Probability / weight

---

## 🪄 3. Popup Settings

This tab defines all the content displayed inside the Spin Reward popup:

- Headings  
- Descriptions  
- Additional information  
- Visual and text content

---

## 🔘 4. CTA Configuration

Configures the **Call-to-Action button** that triggers the Spin Reward popup.

If the CTA button is **disabled**, the popup will open based on triggers defined in **Popup Trigger Configuration**.

---

## ⚡ 5. Popup Trigger Configuration

Controls when and how the popup is automatically triggered.

### **Trigger Types**

- **Enable Scroll Trigger**  
  Popup appears when the user scrolls a specified percentage of the page.

- **Enable Timeout Trigger**  
  Popup appears after a set delay.

- **Enable Exit Intent Trigger**  
  Popup appears when the customer attempts to leave the page  
  (e.g., cursor moves outside the browser window).

---

## ✅ Summary

Using these settings, you can fully customize:

- Wheel sectors and probabilities  
- Popup content and display behavior  
- CTA button appearance and behavior  
- Automatic popup triggers

---

If you'd like, I can also:
- Add screenshots sections  
- Create a version in Russian  
- Generate badges, a table of contents, or add code examples  
Just let me know!
