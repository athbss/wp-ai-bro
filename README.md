# WordPress AI Assistant

[![Version](https://img.shields.io/badge/version-1.3.0-blue.svg)](https://github.com/athbss/wp-ai-bro)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

תוסף WordPress מתקדם המספק יכולות בינה מלאכותית לשיפור תהליכי יצירת תוכן, נגישות וחוויית המשתמש.

**🆕 גרסה 1.3.0:** כפתורי AI בממשק המדיה, תיוג אוטומטי חכם, אופטימיזציה SEO/AEO!

## ✨ תכונות עיקריות

### 🤖 תמיכה במספר ספקי AI
- **OpenAI**: GPT-5.1, GPT-5 Pro, GPT-5, GPT-5 Mini, GPT-5 Nano, GPT-4.1, O3/O4 (Reasoning models)
- **Anthropic**: Claude Opus 4.5, Claude Sonnet 4.5, Claude Haiku 4.5, Claude Opus/Sonnet 4
- **Google AI**: Gemini 3 Pro, Gemini 2.5 Pro/Flash, Gemini 2.5 Flash Image (יצירת תמונות)

### 📸 כפתורי AI בממשק המדיה (🆕 v1.3.0)
- **כפתור ליד כל שדה** בממשק עריכת תמונות
- **יצירת כותרות** אוטומטית (3-7 מילים)
- **יצירת תיאורים מפורטים** לנגישות ואינדקסציה
- **יצירת alt text** לנגישות WCAG
- **יצירת כיתובים** קצרים ומעניינים
- זמין בכל מקום: Grid View, List View, עריכה בודדת

### 🏷️ תיוג אוטומטי חכם (🆕 v1.3.0)
- **בחירה מתוך טקסונומיות קיימות** (לא יוצר חדשות)
- תמיכה ב-**Tags, Categories, Custom Taxonomies**
- **בחירה מרובה** של טקסונומיות לתיוג
- **אישור ידני** לפני החלת הצעות
- תצוגת מספר איברים לכל טקסונומיה

### 🔍 אופטימיזציה SEO ו-AEO (🆕 v1.3.0)
- **ניתוח תוכן מקיף** עם 5 קטגוריות
- **מבנה**: שיפור H1-H6, רשימות, פסקאות
- **מילות מפתח**: המלצות LSI ו-long-tail keywords
- **קריאות**: טיפים לשיפור clarity
- **Featured Snippets**: אופטימיזציה לגוגל
- **תשובות AI**: אופטימיזציה ל-ChatGPT, Perplexity

### 🎨 יצירת תוכן אוטומטית
- **טקסט חלופי לתמונות**: יצירה אוטומטית של alt text נגיש לכל התמונות
- **תיוג אוטומטי**: תיוג וקיטלוג תוכן באופן חכם
- **תקצירים**: יצירת תקצירים תמציתיים לפוסטים
- **קטגוריות**: יצירה אוטומטית של קטגוריות רלוונטיות
- **יצירת תמונות**: שימוש ב-Gemini 2.5 Flash Image ליצירת תמונות מטקסט

### 🌍 תמיכה רב-לשונית מלאה
- זיהוי אוטומטי של שפת הפריט (WPML, Polylang, WordPress native)
- כל התוכן שנוצר מתבצע באותה השפה של הפריט
- תמיכה מלאה בעברית בממשק התוסף
- תרגום טקסט עם שמירה על הקשר וסגנון

### ⚙️ התאמה אישית מלאה
- **הנחיות כלליות**: הגדרת סגנון כתיבה, טון, והנחיות תוכן גלובליות
- **שפה גרפית**: הגדרת סגנון ויזואלי, ערכות צבעים, והעדפות עיצוב
- בחירת סוגי פוסטים להצמדת עוזר AI
- הפעלה/השבתה של תכונות ספציפיות

### 💰 ניהול עלויות
- מעקב מפורט אחר שימוש בטוקנים
- חישוב עלויות אוטומטי לפי ספק ומודל
- סטטיסטיקות שימוש מפורטות
- ניתוח עלויות לאורך זמן

## 📦 התקנה

1. העלה את תיקיית התוסף לתיקיית `/wp-content/plugins/`
2. הפעל את התוסף דרך תפריט 'תוספים' ב-WordPress
3. גש להגדרות AI Assistant והזן את מפתחות ה-API שלך
4. בחר את ספק ה-AI המועדף ואת המודל
5. הגדר הנחיות כלליות ושפה גרפית (אופציונלי)
6. התחל להשתמש!

## 🔑 מפתחות API

התוסף דורש מפתח API לפחות מאחד הספקים הבאים:

- **OpenAI**: [platform.openai.com/api-keys](https://platform.openai.com/api-keys)
- **Anthropic**: [console.anthropic.com](https://console.anthropic.com)
- **Google AI**: [ai.google.dev](https://ai.google.dev)

## 🚀 שימוש

### עיבוד פוסט עם AI
1. פתח פוסט או צור חדש
2. בצד השמאלי, מצא את תיבת "AI Assistant"
3. סמן "Enable AI processing for this post"
4. לחץ על "Process with AI"
5. התוסף יצור תגיות, תקציר, וקטגוריות אוטומטית

### יצירת טקסט חלופי לתמונות
- העלה תמונה - התוסף יצור אוטומטית טקסט חלופי
- או לחץ על "Generate Alt Text" בעמוד העריכה של התמונה

### תרגום תוכן
1. פתח פוסט
2. מצא את תיבת "AI Translation"
3. בחר שפת יעד
4. לחץ על "Translate"

### יצירת תמונות (Gemini 2.5 Flash Image)
1. גש ל-AI Playground
2. בחר Google AI כספק
3. בחר מודל Gemini 2.5 Flash Image
4. הזן פרומפט לתמונה
5. לחץ על "Generate"

## ⚙️ הגדרות מתקדמות

### הנחיות כלליות לפרומפטים
דוגמה:
```
כתוב בטון מקצועי ועניני. השתמש בשפה ברורה ותמציתית. הימנע מז'רגון מיותר. התמקד בתוכן איכותי ומועיל למשתמש.
```

### הנחיות שפה גרפית
דוגמה:
```
השתמש בסגנון עיצובי מודרני ונקי. העדף צבעים תוססים אך לא צעקניים. שמור על מראה מקצועי ונגיש. הימנע מעומס ויזואלי.
```

## 🌐 תמיכה רב-לשונית

התוסף תומך באתרים רב-לשוניים עם:
- **WPML**: זיהוי אוטומטי של שפת הפריט
- **Polylang**: תמיכה מלאה בזיהוי שפה
- **WordPress Native**: שימוש ב-locale של WordPress

כל התוכן שנוצר (תגיות, תיאורים, תקצירים) יהיה באותה השפה של הפריט המקורי.

## 📊 מעקב שימוש

התוסף מספק לוח בקרה מפורט עם:
- סך בקשות API
- סך טוקנים שנוצלו
- עלות כוללת
- פירוט לפי ספק
- עלות ממוצעת לבקשה
- גרפים של שימוש לאורך זמן

## 🔐 אבטחה

- כל מפתחות ה-API מאוחסנים בצורה מוצפנת
- בדיקות הרשאות על כל פעולה
- Nonce verification על כל בקשת AJAX
- Sanitization מלא של כל הקלטים

## 📝 רישיון

GPL v2 or later - [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## 👨‍💻 מפתח

**Amit Trabelsi**  
🌐 [amit-trabelsi.co.il](https://amit-trabelsi.co.il)  
📧 amit@amit-trabelsi.co.il

## 🐛 דיווח על באגים

דווח על באגים או בקש תכונות חדשות דרך [GitHub Issues](https://github.com/athbss/wp-ai-bro/issues)

## 📋 Changelog

ראה [CHANGELOG.md](CHANGELOG.md) לרשימה מלאה של שינויים.

---

**גרסה נוכחית**: 1.3.0  
**תאריך עדכון**: דצמבר 8, 2024  
**WordPress נבדק עד**: 6.6  
**PHP מינימלי**: 7.4

## 📚 תיעוד מקיף

- **מדריך משתמש:** [QUICKSTART-v1.3.0-HE.md](QUICKSTART-v1.3.0-HE.md)
- **תיעוד טכני:** [FEATURE-UPDATE-v1.3.0.md](FEATURE-UPDATE-v1.3.0.md)
- **סיכום יישום:** [IMPLEMENTATION-SUMMARY-v1.3.0.md](IMPLEMENTATION-SUMMARY-v1.3.0.md)
- **Changelog:** [CHANGELOG.md](CHANGELOG.md)
