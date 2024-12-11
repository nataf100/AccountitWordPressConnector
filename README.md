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

## Installation Guide

WooCommerce Plugin for AccountIT
Version: 1.55
תוסף זה מאפשר לחנויות המופעלות על פלטפורמת וורדפרס ו-WooCommerce להתממשק למערכת
AccountIT ולהפיק מסמך באופן אוטומטי לפי נתוני עסקת המכירה.
שינויים מגרסה קודמת:
.1 תמיכה בשערי מט"ח. סוג המטבע שיישלח למערכת AccountIT יילקח ממטבע ברירת המחדל
שהוגדר בוורדפרס. במידה והמטבע לא הוגדר גם במערכת AccountIT יחושבו הערכים
בשקלים.
.2 שליחה אוטומטית של חשבונית/קבלה בעת קבלת הזמנה חדשה.
.3 מניעת שליחת מסמך נוסף בעת שינוי סטטוס הזמנה.
.4 הוספת טיפול במשלוח ומיסוי משלוח.
.5 הוספת בחירה מרובה של מצבי הפעלה.
.6 הוספת אפשרות לבחירת סביבת העבודה.
.7 הוספת משיכה של פריט ומספר לקוח ממערכת AccountIT.
התקנת התוסף:
תהליך התקנת התוסף פשוט יחסית אך ממומלץ שיתבצע ע"י מישהו המכיר לעומק את הפלטפורמות
השונות.
.1 התקנת התוסף – יש להתקין כרגיל ע"י העלאת הקובץ המכווץ לפלטפורמת וורדפרס והפעלתו.
.2 לאחר שהתוסף מותקן ומופעל )active )יש להיכנס ל-“Connector AccountIT Woo ”
בדשבורד של וורדפרס.
.3 בשדה Name User יש להזין את שם המשתמש במערכת AccountIT – זהו בדרך כלל המייל
עמו נרשמתם למערכת AccountIT.
.4 בשדה: Key API יש להזין את המפתח האישי של משתמש AccountIT. ניתן לקבלו מתוך
מערכת AccountIT בתפריט: הגדרות -< עריכת פרטי משתמש -< קוד API פרטי
.5 בשדה: ID Company יש להזין את מזהה החברה עמה נרצה שהתוסף יעבוד. ניתן לקבלו
בתפריט: הגדרות -< עריכת פרטי משתמש -< קוד חברה.
התהליך לעיל מאפשר את חיבור התוסף למערכת, וכעת יש להגדיר את אופן ניהול והפקת המסמכים:
.1 בשדה Type Document Generated יש לבחור את סוג המסמך שנרצה להפיק עם התוסף
באופן אוטומטי: קבלה )Receipt), חשבונית )Invoice), או חשבונית קבלה ) & Invoice
.)Receipt
.2 בשדה Trigger Activation נגדיר מתי התוסף פעיל: כבוי )Disabled )או פעיל כשהסטטוס
.)Run when order status changed to( משתנה
.3 בשדה Trigger Select נגדיר מה יהיה טריגר הפעולה להפקת מסמך. מומלץ שיהיה מוגדר
completed לאחר הטיפול בהזמנה.
.4 בשדה Notification) Buyer (Client נגדיר האם לשלוח את המסמך ללקוח במייל ) Email
Attached PDF with )או לא לשלוח כלל )None).
.5 בשדה ID Client AccountIT נזין את הקוד )המספרי( של החשבון במערכת AccountIT אליו
נרצה לשייך את פקודות היומן שיווצרו במערכת – בדרך כלל חשבון כללי או חשבון שמייצג את
החנות הספציפית. חשוב מאוד שחשבון זה יהיה קיים במערכת AccountIT.
.6 בשדה Item AccountIT נציין את קוד הפריט במערכת AccountIT. בדרך כלל אין חפיפה בין
הפריטים בחנות הוורדפרס לפריטים ב-AccountIT ולכן ממולץ להזין בשדה זה את קוד הפריט
הכללי )בדרך כלל 1(. חשוב מאוד שקוד פריט זה יהיה קיים במערכת AccountIT.
.7 בשדה Calculation VAT נבחר כיצד יתבצע חישוב המע"מ בפועל:
 כל כי מגדיר – Let AccountIT recalculate VAT based on your company settings -
חישוב המע"מ יעשה ע"י מערכת AccountIT בהתאם להגדרות שהוזנו בה.
חשוב: במידה ולא הוזן מס )TAX )לפריט מסוים אז המערכת תתייחס למכירה כאל מכירה
לחו"ל, ללא חובת מע"מ ולכן יש לשים לב שהגדרות המע"מ בחנות תואמות את הדרישות
החוקיות של רשויות המס.
 מאפשר – Allow Zero VAT deals (Based on WooCommerce Standard Rates) -
הפקת מסמכים ללא חישוב מע"מ במערכת AccountIT אלא כפי שהוגדר בתוסף
WooCommerce( משמש בעיקר לצורך מכירות לחו"ל(.
.8 בשדה Inventory Update נבחר כיצד לעדכן את מלאי העסק במערכת AccountIT:
- inventory AccountIT update not Do – מגדיר לא לעדכן את המלאי.
- inventory AccountIT Update – מגדיר לעדכן את המלאי.
 לעדכן מגדיר – Update AccountIT inventory and add new items if missing -
את המלאי ולהוסיף פריטים חדשים אם המלאי אזל.
.9 בשדה Environment נקבע את מצב התוסף )למתקדמים בלבד(:
- Live – התוסף עובד.
- Testing – שרת ניסיון לוורדפרס, ניתן להפעיל את התוסף רק שם.
– Develop – שרת מפתחים לוורדפרס, ניתן להפעיל את התוסף רק שם.
שימוש בתוסף
לאחר התקנת התוסף בהצלחה תתווסף עמודה לצד כל הזמנה שבוצעה באתר בתוסף
WooCommerce עם מספר המסמך.
הערה חשובה: לא ניתן להפיק חשבונית/חשבונית קבלה לקנייה המכילה פריטים החייבים במע"מ עם
פריטים שלא חייבים במע"מ.
בהצלחה!
