# תיקון בעיית התרגום + עדכון ל-Abilities API
## WordPress AI Assistant v1.2.1

---

## 🎯 מה תוקן

### 1️⃣ בעיית התרגום (ממשק באנגלית)

**הבעיה:**
- הממשק הוצג באנגלית למרות שקובץ התרגום (.po) היה מלא בתרגומים עבריים
- WordPress לא טען את התרגומים כראוי

**הפתרון:**

#### א. שיפור מנגנון טעינת השפה
עדכנתי את `includes/class-i18n.php` עם מנגנון משופר:

```php
// Force load Hebrew if site locale is Hebrew
$locale = get_locale();
$mofile = sprintf('%s-%s.mo', WORDPRESS_AI_ASSISTANT_TEXT_DOMAIN, $locale);
$mofile_local = WORDPRESS_AI_ASSISTANT_PATH . 'languages/' . $mofile;
$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;

// Try to load from plugin directory first, then global
if (file_exists($mofile_local)) {
    load_textdomain(WORDPRESS_AI_ASSISTANT_TEXT_DOMAIN, $mofile_local);
} elseif (file_exists($mofile_global)) {
    load_textdomain(WORDPRESS_AI_ASSISTANT_TEXT_DOMAIN, $mofile_global);
}
```

**מה זה עושה:**
- בודק במפורש את locale של WordPress
- מחפש קובץ `.mo` בשני מיקומים (תיקיית התוסף ותיקייה גלובלית)
- טוען אותו **ישירות** במקום להסתמך רק על `load_plugin_textdomain`

#### ב. קמפול מחדש של קבצי תרגום
יצרתי סקריפט אוטומטי לקמפול:

**קובץ:** `scripts/compile-translations.sh`

```bash
#!/bin/bash
# מקמפל קבצי .po ל-.mo
for po_file in languages/*.po; do
    base_name=$(basename "$po_file" .po)
    mo_file="languages/$base_name.mo"
    msgfmt -o "$mo_file" "$po_file"
done
```

**שימוש:**
```bash
cd /path/to/plugin
./scripts/compile-translations.sh
```

**מה זה עושה:**
- ממיר קבצי `.po` (טקסט) ל-`.mo` (binary)
- WordPress קורא **רק** מקבצי `.mo`
- צריך להריץ אחרי כל שינוי בתרגומים

---

### 2️⃣ עדכון ל-Abilities API (במקום Feature API)

**הזיהוי שלך היה נכון לחלוטין!**

**Feature API** → ❌ לא רלוונטי, פרויקט ישן  
**Abilities API** → ✅ פרויקט רשמי של WordPress!

#### מה זה Abilities API?

מתוך [GitHub - WordPress/abilities-api](https://github.com/WordPress/abilities-api):

> **Purpose:** provide a common way for WordPress core, plugins, and themes to describe what they can do ("abilities") in a machine-readable, human-friendly form.
> 
> **Part of:** AI Building Blocks for WordPress initiative

**סטטוס:**
- ✅ פרויקט רשמי של WordPress
- ✅ חלק מיוזמת "AI Building Blocks"
- 🚧 בפיתוח להטמעה ב-**WordPress 6.9**
- 📦 זמין כחבילת Composer: `wordpress/abilities-api`
- 🔌 זמין כתוסף Feature Plugin

#### מה עדכנתי

**קובץ:** `includes/class-dependency-checker.php`

**לפני:**
```php
'wp_feature_api' => array(
    'name' => 'WordPress Feature API',
    'description' => 'ספרייה לניהול מתקדם של features...',
)
```

**אחרי:**
```php
'wp_abilities_api' => array(
    'name' => 'WordPress Abilities API',
    'type' => 'core', // מתוכנן להיות מוטמע ב-WP 6.9
    'status' => 'optional',
    'description' => __('API רשמי של WordPress לגילוי והצהרה על יכולות תוספים...', 'wordpress-ai-assistant'),
    'install_method' => 'composer',
    'docs_url' => 'https://github.com/WordPress/abilities-api',
)
```

**פונקציית בדיקה משופרת:**
```php
private function check_wp_abilities_api() {
    global $wp_version;
    
    // 1. בדיקה אם מוטמע ב-WordPress 6.9+
    if (version_compare($wp_version, '6.9', '>=')) {
        if (function_exists('wp_register_ability')) {
            return true;
        }
    }

    // 2. בדיקה אם התוסף Feature Plugin מותקן
    if (function_exists('is_plugin_active')) {
        if (is_plugin_active('abilities-api/abilities-api.php')) {
            return true;
        }
    }

    // 3. בדיקה אם חבילת Composer מותקנת
    if (class_exists('WordPress\\AbilitiesAPI\\Registry')) {
        return true;
    }

    // 4. בדיקת פונקציות עיקריות
    if (function_exists('wp_register_ability') || 
        function_exists('wp_get_ability')) {
        return true;
    }

    return false;
}
```

**מה זה בודק:**
1. האם זה WordPress 6.9+ ויש את הפונקציות
2. האם התוסף Feature Plugin מותקן ופעיל
3. האם החבילת Composer קיימת
4. האם הפונקציות העיקריות של ה-API זמינות

---

## 📦 איך להתקין Abilities API (אופציונלי)

### אופציה 1: התקנה דרך Composer
```bash
cd /path/to/wordpress/wp-content/plugins/your-plugin
composer require wordpress/abilities-api
```

### אופציה 2: התקנה כתוסף
```bash
cd /path/to/wordpress/wp-content/plugins
git clone https://github.com/WordPress/abilities-api.git
cd abilities-api
composer install
```

ואז הפעל את התוסף דרך WordPress Admin.

### אופציה 3: המתן ל-WordPress 6.9
אם אתה משתמש ב-WordPress 6.9 ומעלה, זה יהיה מובנה! 🎉

---

## 🔧 בדיקת התיקונים

### בדיקת תרגום

1. **וודא locale בעברית:**
```bash
wp-env run cli wp option get WPLANG
# צריך להחזיר: he_IL
```

אם לא:
```bash
wp-env run cli wp language core install he_IL
wp-env run cli wp site switch-language he_IL
```

2. **נקה cache:**
```bash
wp-env run cli wp cache flush
```

3. **בדוק קבצי תרגום:**
```bash
ls -lh languages/
# צריך לראות:
# wordpress-ai-assistant-he_IL.po
# wordpress-ai-assistant-he_IL.mo (עם תאריך עדכני!)
```

4. **רענן דפדפן:**
- פתח WordPress Admin
- גש ל-AI Assistant → Settings
- **כעת הכל צריך להיות בעברית!** 🇮🇱

### בדיקת Abilities API

1. **גש לעמוד System Status:**
```
AI Assistant → System Status
```

2. **חפש את השורה:**
```
WordPress Abilities API
```

3. **סטטוס צפוי:**
- ✅ **מותקן** - אם יש לך WordPress 6.9+ או התקנת את התוסף
- ❌ **חסר** - זה בסדר! זה אופציונלי (optional)

---

## 📚 משאבים נוספים

### Abilities API
- **GitHub**: https://github.com/WordPress/abilities-api
- **Make WordPress**: https://make.wordpress.org/ai/2025/07/17/abilities-api/
- **Composer**: `wordpress/abilities-api`
- **סטטוס**: In progress for WordPress 6.9

### קבצי תרגום
- **POEdit** - כלי עריכה גרפי: https://poedit.net/
- **Loco Translate** - תוסף WordPress: https://wordpress.org/plugins/loco-translate/
- **gettext** - כלי שורת פקודה: `brew install gettext`

---

## 🐛 פתרון בעיות

### התרגום עדיין לא עובד?

1. **בדוק שקובץ .mo קיים ומעודכן:**
```bash
ls -lh languages/*.mo
```

2. **הרץ קמפול מחדש:**
```bash
./scripts/compile-translations.sh
```

3. **בדוק permissions:**
```bash
chmod 644 languages/*.mo
```

4. **נקה cache של WordPress:**
```bash
wp cache flush
# או ידנית במנהל התוספים
```

5. **בדוק locale:**
```php
// הוסף זמנית ל-wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// בדוק log:
tail -f wp-content/debug.log
```

### Abilities API לא מזוהה?

זה **בסדר גמור**! הסטטוס שלו `optional` - לא נדרש לפעולה תקינה של התוסף.

אם תרצה להתקין:
```bash
composer require wordpress/abilities-api
```

---

## ✅ סיכום

**מה תיקנתי:**
1. ✅ מנגנון טעינת תרגומים משופר
2. ✅ קמפול מחדש של קבצי .mo
3. ✅ החלפת Feature API ב-Abilities API הרשמי
4. ✅ בדיקה מתקדמת ל-WordPress 6.9+
5. ✅ סקריפט קמפול אוטומטי
6. ✅ עדכון תיעוד ו-CHANGELOG

**מה צריך לעשות עכשיו:**
1. רענן את WordPress Admin
2. בדוק שהממשק בעברית
3. גש ל-System Status ובדוק סטטוס תלויות

**הכל אמור לעבוד!** 🎉

---

**גרסה:** 1.2.1  
**תאריך:** 8 בדצמבר 2025  
**מפתח:** Claude + Amit Trabelsi

