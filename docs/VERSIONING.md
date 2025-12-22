# נהלי גרסאות - WordPress AI Assistant

## מבוא

מסמך זה מתאר את נהלי ניהול הגרסאות של התוסף WordPress AI Assistant.

## עקרונות ניהול גרסאות

התוסף עוקב אחר [Semantic Versioning](https://semver.org/) (SemVer):

```
MAJOR.MINOR.PATCH
```

### סוגי גרסאות

- **MAJOR**: שינויים משמעותיים ששוברים תאימות לאחור
- **MINOR**: הוספת פונקציונליות חדשה תואמת לאחור
- **PATCH**: תיקוני באגים ותיקונים קטנים

## תהליך העלאת גרסה

### 1. הכנה לשחרור

```bash
# עדכון מספר הגרסה בקבצים הבאים:
# - wordpress-ai-assistant.php (כותרת התוסף)
# - plugin-info.json
# - composer.json (אם קיים)
```

### 2. עדכון CHANGELOG.md

```markdown
## [1.1.0] - YYYY-MM-DD

### הוספות
- הוספת יכולות AI חדשות
- שיפור ממשק המשתמש

### שינויים
- עדכון API של ספקי AI

### תיקוני באגים
- תיקון בעיות תאימות

### הוצאו משימוש
- פונקציונליות ישנה שהוצאה משימוש
```

### 3. יצירת Tag ב-Git

```bash
# יצירת tag עם מספר הגרסה
git tag -a v1.1.0 -m "Release version 1.1.0"

# דחיפה של ה-tag ל-GitHub
git push origin v1.1.0
```

### 4. יצירת GitHub Release

1. עבור ל-Releases ב-GitHub
2. לחץ על "Create a new release"
3. בחר את ה-Tag שנוצר
4. העתק את התוכן מ-CHANGELOG.md
5. פרסם את ה-Release

## GitHub Actions

התוסף משתמש ב-GitHub Actions לבניית קבצי ZIP:

### Workflow: Build Release ZIP

- **טריגר**: Push ל-main/master/trunk, Pull Requests, ידני
- **פעולות**:
  - התקנת תלויות (Composer, npm)
  - בניית התוסף
  - יצירת קובץ ZIP באמצעות 10up/action-wordpress-plugin-build-zip
  - העלאת artifact
  - יצירת GitHub Release אוטומטי ל-tags

## קבצי הגדרה

### .distignore

קובץ זה מגדיר אילו קבצים **לא** להכליל בקובץ ה-ZIP:

```
/.wordpress-org
/.git
/.github
/node_modules
/vendor
/docs
*.md
!README.md
!CHANGELOG.md
```

### plugin-info.json

```json
{
  "name": "WordPress AI Assistant",
  "version": "1.1.0",
  "download_url": "https://github.com/.../wordpress-ai-assistant-1.1.0.zip",
  "requires": "5.0",
  "tested": "6.6",
  "requires_php": "7.4"
}
```

## בדיקות לפני שחרור

### רשימת בדיקות חובה

- [ ] קומפילציה ללא שגיאות
- [ ] כל הבדיקות עוברות
- [ ] תאימות עם WordPress בגרסאות הנתמכות
- [ ] תאימות עם PHP בגרסאות הנתמכות
- [ ] בדיקת חיבור ל-API של ספקי AI
- [ ] בדיקת אבטחה בסיסית
- [ ] עדכון CHANGELOG.md
- [ ] עדכון כל קבצי הגרסה
- [ ] בדיקת קבצי .distignore

### בדיקות מומלצות

- [ ] בדיקות אינטגרציה עם ספקי AI שונים
- [ ] בדיקות ביצועים
- [ ] בדיקות אבטחה מתקדמות
- [ ] בדיקת תאימות עם תוספים אחרים

## פרסום

### GitHub Releases

1. העלאת קובץ ה-ZIP ל-GitHub Releases
2. עדכון plugin-info.json עם קישור חדש
3. עדכון אתר התמיכה

### WordPress.org (עתידי)

1. העלאת קובץ ה-ZIP ל-WordPress.org
2. עדכון פרטי התוסף
3. פרסום השחרור

## תיקון באגים דחופים (Hotfix)

### תהליך

1. יצירת ענף hotfix מה-tag האחרון
2. תיקון הבאג
3. עדכון מספר גרסה PATCH
4. מיזוג ל-main
5. יצירת tag חדש
6. יצירת GitHub Release

### דוגמה

```bash
git checkout -b hotfix/1.0.2 v1.0.1
# תיקון הבאג
git commit -m "fix: תיקון באג קריטי ב-API"
git tag -a v1.0.2 -m "Hotfix version 1.0.2"
git push origin v1.0.2
```

## תכונות AI ספציפיות

### עדכוני API

כאשר מתעדכנים APIs של ספקי AI (OpenAI, Claude, etc.):

1. עדכון ספריות הלקוח
2. בדיקת תאימות לאחור
3. עדכון תיעוד
4. בדיקות מקיפות

### אבטחת API Keys

- [ ] אימות הצפנת API keys
- [ ] בדיקת הרשאות גישה
- [ ] תיעוד נהלי אבטחה

## כלים עזר

### סקריפטים מומלצים

```bash
# scripts/version-bump.sh
#!/bin/bash
# סקריפט להעלאת גרסה אוטומטית

# scripts/test-ai-apis.sh
#!/bin/bash
# סקריפט לבדיקת חיבור ל-APIs של AI
```

### כלי בדיקה

- [Plugin Check](https://wordpress.org/plugins/plugin-check/) - בדיקת תוספים
- [PHPStan](https://phpstan.org/) - ניתוח קוד סטטי
- [PHPUnit](https://phpunit.de/) - בדיקות יחידה

## שאלות נפוצות

### מתי להעלות MAJOR version?

כאשר יש שינויים ששוברים תאימות לאחור:
- שינוי API משמעותי
- הסרת יכולות AI קיימות
- שינויים בארכיטקטורת האבטחה

### מתי להעלות MINOR version?

כאשר מוסיפים פונקציונליות חדשה:
- הוספת ספק AI חדש
- יכולות AI חדשות
- שיפורי ממשק משתמש

### מתי להעלות PATCH version?

לתיקוני באגים ולשיפורים קטנים:
- תיקון באגים ביכולות AI
- תיקוני אבטחה
- שיפורי ביצועים קטנים

### איך לטפל בעדכוני API של ספקי AI?

1. נטר הודעות על עדכוני API
2. בדוק תאימות עם הגרסה הנוכחית
3. תכנן מיגרציה לתכונות חדשות
4. וודא תאימות לאחור למשתמשים קיימים

---

**נכתב ב**: 2025-09-16
**עודכן ב**: 2025-09-16
**גרסה**: 1.0.0
