# Debug - כפתורי מדיה לא מופיעים

## בדיקות לביצוע

### 1. רענן דף עם cache clear
```
Cmd+Shift+R (Mac) או Ctrl+F5 (Windows)
```

### 2. פתח Console (F12)
בדוק הודעות:
```
✓ "Media AI Generator loading..."
✓ "AI buttons added"
```

### 3. בדוק שהסקריפט נטען
ב-Console הקלד:
```javascript
typeof atAiMedia
// צריך להחזיר: "object"
```

### 4. בדוק attachment ID
ב-Console הקלד:
```javascript
new URLSearchParams(window.location.search).get('item')
// צריך להחזיר: "5"
```

### 5. בדוק אם יש שדות
ב-Console הקלד:
```javascript
jQuery('#attachment_alt').length
// צריך להחזיר: 1 או יותר
```

---

## אם עדיין לא עובד

הוסף לי screenshot של:
1. כל הדף
2. Console (F12 → Console tab)
3. Network tab (F12 → Network → reload → חפש media-generator.js)

---

**הבא:** רענן דף ובדוק console

