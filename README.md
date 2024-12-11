# Woo AccountIt Connector

## Description
The Woo AccountIt Connector plugin sends a mail to the shop admin and the customer a mail of the ordered pdf and also pushes the data to AccountIT database 

## Latest Release
## Updating the Version and Publishing a New Release

Follow these steps to update the plugin version and publish a new release for **AccountitWordPressConnector**:

### 1. Update the Plugin Version
- Open the main plugin file:  
  `woo-accountit-connector.php`.  
- Update the version in two locations:
  - Find the `define` statement and update the version:  
    ```php
    define('VERSION', "2.0");
    ```
  - Update the `Version` header:  
    ```php
    Version: 2.0
    ```
- Save the file.

### 2. Commit and Push the Changes
- Add the changes to Git and commit them:  
  ```bash
  git add woo-accountit-connector.php
  git commit -m "Update plugin version to 2.0"
  git push
### 3. Create a New Release on GitHub
Navigate to the AccountitWordPressConnector GitHub repository.
Click on the Releases tab.
Select Draft a New Release.
Create a new version tag:
Click on the Choose a Tag dropdown.
Enter the new version number (e.g., 2.0) and press Enter to create the tag.
Add release details:
Release Title: Use the version number (e.g., Version 2.0).
Description: Provide a summary of changes, features, or fixes included in the release.
Set as Latest Release: Check the box for Set as Latest Release.
Click on Publish Release.
### 4. Verify the Release
Ensure the new release appears under the Releases tab.
Confirm the latest release tag matches the updated version number.

[Latest Release Link](https://github.com/nataf100/AccountitWordPressConnector/releases/)

## Overview

# WooCommerce AccountIT Connector Plugin
This WordPress WooCommerce plugin allows stores to interface with the AccountIT system and automatically generate documents based on sales transaction data.

## Features
- Automatic document generation (Receipt, Invoice, or Invoice & Receipt)
- Support for foreign currency exchange rates
- Automatic document sending for new orders
- Shipping and taxation handling
- Multiple activation mode selection
- Work environment selection
- Item and customer details retrieval from AccountIT

## Installation

### Prerequisites
- WordPress
- WooCommerce
- AccountIT System Account

### Installation Steps
1. Upload the compressed plugin file to your WordPress platform and activate it.
2. Navigate to "Woo AccountIT Connector" in the WordPress dashboard.
3. Configure the following settings:
   - **User Name**: Your AccountIT system email
   - **API Key**: Personal API key from AccountIT (Settings -> Edit User Details -> API Personal Code)
   - **Company ID**: Company identifier (Settings -> Edit User Details -> Company Code)

## Configuration Options

### Document Generation
- **Generated Document Type**: 
  - Receipt
  - Invoice
  - Invoice & Receipt

### Activation Triggers
- Disabled
- Activate when order status changes

### Client Notification
- Send document via email
- No notification

### Additional Settings
- VAT Calculation methods
- Inventory update options
- Environment modes (Live, Testing, Develop)

## Important Notes
- Cannot generate invoices mixing VAT-liable and non-VAT-liable items
- Ensure tax settings comply with legal requirements
- Recommended installation by someone familiar with WordPress and WooCommerce platforms

## Support
For support and additional information, contact your AccountIT system administrator.


## Disclaimer
This plugin is provided as-is. Always test thoroughly in a staging environment before production deployment.

---

# תוסף WooCommerce למערכת AccountIT

## סקירה כללית
תוסף זה למערכות WordPress ו-WooCommerce מאפשר ממשק למערכת AccountIT ויצירת מסמכים באופן אוטומטי על פי נתוני עסקאות מכירה.

## תכונות
- יצירת מסמכים אוטומטית (קבלה, חשבונית, או חשבונית וקבלה)
- תמיכה בשערי מט"ח
- שליחת מסמכים אוטומטית עבור הזמנות חדשות
- טיפול במשלוח ובמיסוי
- בחירה מרובה של מצבי הפעלה
- בחירת סביבת עבודה
- משיכת פרטי פריט ולקוח ממערכת AccountIT

## התקנה

### דרישות מוקדמות
- WordPress
- WooCommerce
- חשבון במערכת AccountIT

### שלבי התקנה
1. העלאת קובץ התוסף המכווץ לפלטפורמת WordPress והפעלתו.
2. כניסה ל-"Woo AccountIT Connector" בלוח הבקרה של WordPress.
3. הגדרת ההגדרות הבאות:
   - **שם משתמש**: כתובת המייל של מערכת AccountIT
   - **מפתח API**: מפתח API אישי מ-AccountIT (הגדרות -> עריכת פרטי משתמש -> קוד API פרטי)
   - **מזהה חברה**: מזהה החברה (הגדרות -> עריכת פרטי משתמש -> קוד חברה)

## אפשרויות תצורה

### יצירת מסמכים
- **סוג מסמך שייווצר**: 
  - קבלה
  - חשבונית
  - חשבונית וקבלה

### טריגרי הפעלה
- כבוי
- הפעלה בעת שינוי סטטוס הזמנה

### התראות ללקוח
- שליחת מסמך בדוא"ל
- ללא התראה

### הגדרות נוספות
- שיטות חישוב מע"מ
- אפשרויות עדכון מלאי
- מצבי סביבה (חי, בדיקה, פיתוח)

## הערות חשובות
- לא ניתן להפיק חשבוניות המערבות פריטים החייבים במע"מ עם פריטים שאינם חייבים במע"מ
- ודאו כי הגדרות המס תואמות את הדרישות החוקיות
- מומלץ להתקין על ידי מישהו המכיר לעומק את WordPress ו-WooCommerce

## תמיכה
לתמיכה ומידע נוסף, צרו קשר עם מנהל מערכת AccountIT.

## הבהרה
תוסף זה מסופק כפי שהוא. תמיד בצעו בדיקות יסודיות בסביבת staging לפני הפעלה בסביבת הפקה.
