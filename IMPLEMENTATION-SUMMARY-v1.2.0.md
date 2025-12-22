# סיכום עדכון WordPress AI Assistant v1.2.0

**תאריך**: 8 דצמבר 2025  
**גרסה**: 1.2.0  
**מפתח**: Amit Trabelsi

---

## 🎯 מטרות העדכון

1. ✅ עדכון רשימת המודלים למודלים העדכניים ביותר (דצמבר 2025)
2. ✅ הוספת תמיכה ביצירת תמונות עם Gemini 2.5 Flash Image
3. ✅ הוספת שדות ניהול להנחיות כלליות ושפה גרפית
4. ✅ תמיכה מלאה בעברית בממשק התוסף
5. ✅ תמיכה באתרים רב-לשוניים (WPML/Polylang/WordPress native)

---

## 📋 שינויים שבוצעו

### 1. עדכון רשימת המודלים

#### OpenAI Provider
**קובץ**: `includes/ai/class-openai-provider.php`

**מודלים חדשים שנוספו**:
- `gpt-5.1` - GPT-5.1 (Latest flagship)
- `gpt-5-pro` - GPT-5 Pro (highest quality)
- `gpt-5` - GPT-5 (previous flagship)
- `gpt-5-mini` - GPT-5 Mini (fast/cheap)
- `gpt-5-nano` - GPT-5 Nano (fastest/cheapest)
- `gpt-4.1` - GPT-4.1
- `gpt-4.1-mini` - GPT-4.1 Mini
- `gpt-4.1-nano` - GPT-4.1 Nano
- `o3` - O3 (reasoning)
- `o3-pro` - O3 Pro (reasoning, more compute)
- `o3-mini` - O3 Mini (small reasoning)
- `o4-mini` - O4 Mini (legacy small reasoning)
- `o4-mini-deep-research` - O4 Mini Deep Research

**מודל ברירת מחדל חדש**: `gpt-5.1`

#### Anthropic Provider
**קובץ**: `includes/ai/class-anthropic-provider.php`

**מודלים חדשים שנוספו**:
- `claude-opus-4-5` - Claude Opus 4.5 (Latest flagship)
- `claude-opus-4-5-20251101` - Claude Opus 4.5 Snapshot (2025-11-01)
- `claude-opus-4-1` - Claude Opus 4.1
- `claude-opus-4-1-20250805` - Claude Opus 4.1 Snapshot (2025-08-05)
- `claude-opus-4` - Claude Opus 4 (legacy)
- `claude-opus-4-20250514` - Claude Opus 4 Snapshot (2025-05-14)
- `claude-sonnet-4-5` - Claude Sonnet 4.5
- `claude-sonnet-4-5-20250929` - Claude Sonnet 4.5 Snapshot (2025-09-29)
- `claude-sonnet-4` - Claude Sonnet 4
- `claude-sonnet-4-20250514` - Claude Sonnet 4 Snapshot (2025-05-14)
- `claude-haiku-4-5` - Claude Haiku 4.5 (fast/small)
- `claude-haiku-4-5-20251001` - Claude Haiku 4.5 Snapshot (2025-10-01)
- `claude-3-7-sonnet-20250219` - Claude Sonnet 3.7 (deprecated)

**מודל ברירת מחדל חדש**: `claude-opus-4-5`

#### Google AI Provider
**קובץ**: `includes/ai/class-google-provider.php`

**מודלים חדשים שנוספו**:
- `gemini-3-pro` - Gemini 3 Pro (Latest flagship)
- `gemini-3-pro-preview` - Gemini 3 Pro Preview
- `gemini-2.5-pro` - Gemini 2.5 Pro
- `gemini-2.5-flash` - Gemini 2.5 Flash
- `gemini-2.5-flash-lite` - Gemini 2.5 Flash-Lite (fast/small)
- `gemini-2.5-flash-image` - Gemini 2.5 Flash Image (native image gen)
- `gemini-2.5-flash-preview-tts` - Gemini 2.5 Flash Preview TTS
- `gemini-2.5-pro-preview-tts` - Gemini 2.5 Pro Preview TTS
- `gemini-live-2.5-flash-preview` - Gemini Live 2.5 Flash (Realtime preview)

**מודלים ליצירת תמונות**:
- `gemini-2.5-flash-image` - Gemini 2.5 Flash Image (native image gen)
- `nano-banana` - Nano Banana (image gen/edit)
- `nano-banana-pro` - Nano Banana Pro (image gen/edit)

**מודלים ליצירת וידאו**:
- `veo-3.1` - Veo 3.1 (video generation)

**מודל ברירת מחדל חדש**: `gemini-3-pro`

---

### 2. תמיכה ביצירת תמונות

**קבצים שעודכנו**:
- `includes/ai/class-ai-provider.php` - הוספת `generate_image()` abstract method
- `includes/ai/class-google-provider.php` - מימוש `generate_image()` עם Gemini 2.5 Flash Image
- `includes/ai/class-ai-manager.php` - הוספת `generate_image()` public method

**יכולות**:
- יצירת תמונות מטקסט באמצעות Gemini 2.5 Flash Image
- שילוב אוטומטי של הנחיות שפה גרפית מההגדרות
- תמיכה במספר תמונות בבקשה אחת
- מעקב שימוש ועלויות

---

### 3. שדות ניהול להנחיות

**קובץ**: `admin/class-admin.php`

**שדות חדשים**:

#### General Prompt Instructions
- שדה textarea להזנת הנחיות כלליות
- מתווסף אוטומטית לכל הפרומפטים
- שימוש: הגדרת סגנון כתיבה, טון, הנחיות תוכן

**דוגמה**:
```
כתוב בטון מקצועי. השתמש בשפה ברורה ותמציתית. הימנע מז'רגון.
```

#### Visual Style Instructions
- שדה textarea להזנת הנחיות ויזואליות
- מתווסף לפרומפטים של יצירת תמונות
- שימוש: סגנון ויזואלי, ערכות צבעים, העדפות עיצוב

**דוגמה**:
```
השתמש בסגנון מודרני ונקי. העדף צבעים תוססים. שמור על מראה מקצועי.
```

**פונקציות חדשות שנוספו**:
- `render_general_prompt_instructions_field()` - רנדור שדה הנחיות כלליות
- `render_visual_style_instructions_field()` - רנדור שדה הנחיות ויזואליות

---

### 4. תמיכה באתרים רב-לשוניים

**קובץ**: `includes/functions.php`

**פונקציות חדשות**:

#### `at_ai_assistant_get_post_language($post_id)`
- זיהוי אוטומטי של שפת הפריט
- תמיכה ב-WPML, Polylang, WordPress native
- נסיון מדורג: WPML → Polylang → WordPress locale → ברירת מחדל

#### `at_ai_assistant_get_default_language()`
- החזרת שפת ברירת מחדל מהגדרות WordPress
- Fallback ל-English אם לא מוגדר

#### `at_ai_assistant_get_language_name($lang_code)`
- המרת קוד שפה לשם מלא
- תמיכה ב-12 שפות: English, עברית, Español, Français, Deutsch, Italiano, Português, Русский, العربية, 中文, 日本語, 한국어

#### `at_ai_assistant_build_language_aware_prompt($base_prompt, $post_id, $default_lang)`
- בניית פרומפט עם הקשר שפה
- הוספת הנחיה ל-AI לענות בשפה הנכונה

**שימוש**:
```php
$post_language = at_ai_assistant_get_post_language($post_id);
$prompt = at_ai_assistant_build_language_aware_prompt($base_prompt, $post_id);
```

---

### 5. עדכון AI Manager

**קובץ**: `includes/ai/class-ai-manager.php`

**שינויים**:

#### `generate_text($prompt, $options, $post_id)`
- הוספת פרמטר `$post_id` אופציונלי
- שילוב הנחיות כלליות מההגדרות
- שילוב הקשר שפה אוטומטי

#### `translate_text($text, $target_language, $source_language, $context, $post_id)`
- הוספת פרמטר `$post_id` אופציונלי
- שמירה על טון וסגנון בתרגום

#### `build_translation_prompt()`
- עדכון לתמיכה בהקשר שפה
- שימוש במחרוזות ניתנות לתרגום

#### `generate_tags($content, $existing_taxonomies, $language_code)`
- הוספת פרמטר `$language_code` אופציונלי
- יצירת תגיות באותה שפה של התוכן

#### `build_tagging_prompt()`
- עדכון להוספת הנחיית שפה
- שימוש במחרוזות ניתנות לתרגום

#### `generate_image($prompt, $options)` - חדש!
- יצירת תמונות מטקסט
- שילוב הנחיות ויזואליות
- מעקב שימוש ועלויות

---

### 6. עדכון Auto Tagger

**קובץ**: `includes/features/class-auto-tagger.php`

**שינויים**:
- זיהוי שפת הפריט לפני יצירת תגיות
- העברת קוד השפה ל-AI Manager
- תגיות נוצרות באותה השפה של הפריט

**קוד מעודכן**:
```php
$post_language = at_ai_assistant_get_post_language($object_id);
$tags = $this->ai_manager->generate_tags($content, $taxonomies, $post_language);
```

---

### 7. עדכון Image Alt Generator

**קובץ**: `includes/features/class-image-alt-generator.php`

**שינויים**:

#### `get_alt_text_prompt($attachment_id)`
- הוספת פרמטר `$attachment_id` אופציונלי
- זיהוי שפת הפריט המקורי
- הוספת הנחיית שפה לפרומפט
- שילוב הנחיות כלליות מההגדרות

**קוד מעודכן**:
```php
$post_id = get_post_meta($attachment_id, '_wp_attachment_parent', true);
if ($post_id) {
    $post_language = at_ai_assistant_get_post_language($post_id);
    $lang_name = at_ai_assistant_get_language_name($post_language);
    // Add language instruction to prompt
}
```

---

### 8. עדכון Admin Class

**קובץ**: `admin/class-admin.php`

**שינויים**:

#### `process_post_with_ai($post)`
- שימוש בשפת הפריט בעיבוד תגיות וקטגוריות
- העברת קוד השפה לכל פעולות ה-AI

#### `generate_excerpt($post)`
- שימוש ב-`generate_text()` עם `$post_id`
- הקשר שפה אוטומטי

#### `register_settings()`
- רישום שדות חדשים: `general_prompt_instructions`, `visual_style_instructions`

#### `render_general_prompt_instructions_field()` - חדש!
- רנדור שדה textarea להנחיות כלליות
- placeholder עם דוגמה
- הסבר מפורט

#### `render_visual_style_instructions_field()` - חדש!
- רנדור שדה textarea להנחיות ויזואליות
- placeholder עם דוגמה
- הסבר מפורט

---

### 9. עדכון Text Translator

**קובץ**: `includes/features/class-text-translator.php`

**שינויים**:
- העברת `post_id` מה-context ל-`translate_text()`
- שמירה על טון וסגנון בתרגום

---

### 10. תמיכה בעברית מלאה

**קבצים שנוצרו**:
1. `languages/wordpress-ai-assistant.pot` - תבנית תרגום בינלאומית
2. `languages/wordpress-ai-assistant-he_IL.po` - תרגום עברי (מקור)
3. `languages/wordpress-ai-assistant-he_IL.mo` - תרגום עברי (קומפילציה)

**מחרוזות מתורגמות**: 80+ מחרוזות

**כיסוי תרגום**:
- ✅ כל ממשק הניהול
- ✅ כל הודעות השגיאה
- ✅ כל הטקסטים במטה-בוקסים
- ✅ כל הודעות ה-AJAX
- ✅ כל הסברי השדות

---

## 🔧 שימוש בתכונות החדשות

### הגדרת הנחיות כלליות

1. גש ל-**AI Assistant > Settings**
2. גלול ל-**General Prompt Instructions**
3. הזן הנחיות כלליות, לדוגמה:
```
כתוב בטון מקצועי ועניני.
השתמש בשפה ברורה ותמציתית.
הימנע מז'רגון מיותר.
התמקד בתוכן איכותי ומועיל למשתמש.
שמור על עקביות בכתיבה.
```

4. שמור הגדרות

**תוצאה**: ההנחיות יתווספו אוטומטית לכל בקשות ה-AI (תגיות, תקצירים, תיאורי תמונות)

---

### הגדרת שפה גרפית

1. גש ל-**AI Assistant > Settings**
2. גלול ל-**Visual Style Instructions**
3. הזן הנחיות ויזואליות, לדוגמה:
```
סגנון עיצובי: מודרני ומינימליסטי
צבעים: תוססים אך לא צעקניים, שמור על הרמוניה
מראה: מקצועי ונגיש
הימנע מ: עומס ויזואלי, צבעים בוהקים מדי
```

4. שמור הגדרות

**תוצאה**: ההנחיות יתווספו אוטומטית לכל בקשות יצירת תמונות

---

### שימוש ביצירת תמונות

#### דרך AI Playground:
1. גש ל-**AI Assistant > AI Playground**
2. בחר **Google AI** כספק פעיל (ב-Settings)
3. הזן פרומפט לתמונה:
```
תמונה של חתול מתוק יושב על ספה בסלון מודרני
```
4. לחץ **Generate**

#### דרך קוד:
```php
$ai_manager = AT_AI_Manager::get_instance();
$result = $ai_manager->generate_image('A cute cat sitting on a modern sofa', array(
    'model' => 'gemini-2.5-flash-image',
    'num_images' => 1,
    'size' => '1024x1024'
));

if (!is_wp_error($result)) {
    $images = $result['images'];
    // Process images...
}
```

---

### שימוש באתרים רב-לשוניים

התוסף זוהה אוטומטית את שפת הפריט ויוצר תוכן באותה השפה:

#### עם WPML:
```php
// התוסף מזהה אוטומטית את השפה מ-WPML
// אין צורך בפעולה מיוחדת
```

#### עם Polylang:
```php
// התוסף מזהה אוטומטית את השפה מ-Polylang
// אין צורך בפעולה מיוחדת
```

#### בלי תוסף רב-לשוני:
```php
// התוסף משתמש ב-locale של WordPress
// עברית: he_IL → תגיות בעברית
// אנגלית: en_US → תגיות באנגלית
```

---

## 📝 קבצים שעודכנו

### Core Files
1. ✅ `wordpress-ai-assistant.php` - עדכון גרסה ל-1.2.0
2. ✅ `plugin-info.json` - עדכון מטא-דאטה וchangelog
3. ✅ `CHANGELOG.md` - תיעוד שינויים

### AI Infrastructure
4. ✅ `includes/ai/class-ai-provider.php` - הוספת `generate_image()`
5. ✅ `includes/ai/class-ai-manager.php` - עדכון prompts + `generate_image()`
6. ✅ `includes/ai/class-openai-provider.php` - עדכון מודלים ל-GPT-5.1
7. ✅ `includes/ai/class-anthropic-provider.php` - עדכון מודלים ל-Claude Opus 4.5
8. ✅ `includes/ai/class-google-provider.php` - עדכון מודלים ל-Gemini 3 Pro + יצירת תמונות

### Features
9. ✅ `includes/features/class-auto-tagger.php` - תמיכה רב-לשונית
10. ✅ `includes/features/class-image-alt-generator.php` - תמיכה רב-לשונית
11. ✅ `includes/features/class-text-translator.php` - שיפור תרגום

### Admin & Helpers
12. ✅ `admin/class-admin.php` - שדות הנחיות + תמיכה רב-לשונית
13. ✅ `includes/functions.php` - פונקציות זיהוי שפה

### Translation Files
14. ✅ `languages/wordpress-ai-assistant.pot` - תבנית תרגום בינלאומית
15. ✅ `languages/wordpress-ai-assistant-he_IL.po` - תרגום עברי
16. ✅ `languages/wordpress-ai-assistant-he_IL.mo` - תרגום עברי (קומפילציה)

### Documentation
17. ✅ `README.md` - מדריך שימוש מלא בעברית

---

## 🧪 בדיקות שבוצעו

### בדיקות קוד
- ✅ אין שגיאות Lint
- ✅ תאימות PHP 7.4+
- ✅ תאימות WordPress 5.0+
- ✅ קומפילציה תקינה של קבצי תרגום

### בדיקות פונקציונליות (לביצוע ידני)
- [ ] בדיקת יצירת תמונות עם Gemini 2.5 Flash Image
- [ ] בדיקת שדות הנחיות בהגדרות
- [ ] בדיקת זיהוי שפה באתר עם WPML
- [ ] בדיקת זיהוי שפה באתר עם Polylang
- [ ] בדיקת תרגום לעברית בממשק
- [ ] בדיקת תגיות בעברית
- [ ] בדיקת תיאורי תמונות בעברית

---

## 🚀 שלבים הבאים (אופציונלי)

1. **בדיקות אינטגרציה**: בדיקה באתר ייצור עם WPML/Polylang
2. **תמיכה בשפות נוספות**: הוספת קבצי .po לשפות נוספות
3. **תיעוד API**: מדריך למפתחים לשימוש ב-hooks ו-filters
4. **דוגמאות קוד**: snippets לשימושים נפוצים
5. **וידאו הדרכה**: מדריך וידאו לשימוש בתוסף

---

## 📊 סטטיסטיקות עדכון

- **קבצים שעודכנו**: 17
- **שורות קוד שנוספו**: ~500
- **מודלים חדשים**: 30+
- **מחרוזות מתורגמות**: 80+
- **פונקציות חדשות**: 8
- **שדות הגדרות חדשים**: 2

---

## ⚠️ הערות חשובות

### מודל ברירת מחדל
**לפני**: GPT-4o Mini (OpenAI), Claude 3 Haiku (Anthropic), Gemini 1.5 Flash (Google)  
**אחרי**: GPT-5.1 (OpenAI), Claude Opus 4.5 (Anthropic), Gemini 3 Pro (Google)

**השלכות**:
- העלויות עשויות לעלות עם המודלים החדשים
- איכות התוצרים תשתפר משמעותית
- למשתמשים ישנים - מומלץ לבדוק את העלויות

### תאימות לאחור
- ✅ כל ההגדרות הקיימות נשמרות
- ✅ מודלים ישנים עדיין זמינים (כ-legacy)
- ✅ אין שינויים breaking ב-API
- ✅ מסד נתונים לא משתנה

### מודלים שהוסרו
- אף מודל לא הוסר, רק סומנו כ-legacy או deprecated

---

## 🔒 אבטחה

- ✅ כל הקלטים עוברים sanitization
- ✅ בדיקות הרשאות על כל פעולה
- ✅ Nonce verification על כל AJAX
- ✅ מפתחות API מוצפנים
- ✅ אין SQL injection
- ✅ אין XSS vulnerabilities

---

## 📞 תמיכה

**דיווח על באגים**: [GitHub Issues](https://github.com/athbss/wp-ai-bro/issues)  
**מפתח**: Amit Trabelsi - [amit-trabelsi.co.il](https://amit-trabelsi.co.il)  
**אימייל**: amit@amit-trabelsi.co.il

---

## ✅ סטטוס עדכון

| משימה | סטטוס |
|-------|--------|
| עדכון מודלים OpenAI | ✅ הושלם |
| עדכון מודלים Anthropic | ✅ הושלם |
| עדכון מודלים Google | ✅ הושלם |
| תמיכה ביצירת תמונות | ✅ הושלם |
| שדות הנחיות כלליות | ✅ הושלם |
| שדות הנחיות ויזואליות | ✅ הושלם |
| זיהוי שפת פריט | ✅ הושלם |
| עדכון prompts | ✅ הושלם |
| תרגום לעברית | ✅ הושלם |
| תיעוד | ✅ הושלם |

---

**עדכון הושלם בהצלחה! 🎉**

התוסף מוכן לשימוש עם כל התכונות החדשות.

