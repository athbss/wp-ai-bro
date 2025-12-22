# מדריך התחלה מהירה - WordPress AI Assistant v1.2.0

## 🚀 התחלה ב-3 דקות

### דקה 1: התקנה והפעלה
```bash
# העלה לוורדפרס
wp-content/plugins/wordpress-ai-assistant/

# או דרך ממשק
Plugins > Add New > Upload Plugin > wordpress-ai-assistant.zip
```

**הפעל את התוסף** → Plugins > Installed Plugins > WordPress AI Assistant > Activate

---

### דקה 2: הגדרת API Key (בחר אחד)

#### אופציה 1: OpenAI (GPT-5.1)
```
1. WP Admin > AI Assistant > Settings > API Credentials
2. OpenAI API Settings
3. הזן API Key (מ-platform.openai.com/api-keys)
4. Default Model: GPT-5.1 (או GPT-5 Mini לעלויות נמוכות)
5. Test Connection
6. Save Changes
```

#### אופציה 2: Anthropic (Claude Opus 4.5)
```
1. WP Admin > AI Assistant > Settings > API Credentials
2. Anthropic API Settings
3. הזן API Key (מ-console.anthropic.com)
4. Default Model: Claude Opus 4.5 (או Claude Haiku 4.5 לעלויות נמוכות)
5. Test Connection
6. Save Changes
```

#### אופציה 3: Google AI (Gemini 3 Pro)
```
1. WP Admin > AI Assistant > Settings > API Credentials
2. Google AI API Settings
3. הזן API Key (מ-ai.google.dev)
4. Default Model: Gemini 3 Pro (או Gemini 2.5 Flash לעלויות נמוכות)
5. Test Connection
6. Save Changes
```

---

### דקה 3: בדיקה ראשונה

#### בדיקה 1: AI Playground
```
1. AI Assistant > AI Playground
2. הזן:
   "כתוב פסקה קצרה על היתרונות של בינה מלאכותית בוורדפרס"
3. Generate
4. בדוק שהתשובה בעברית (אם האתר בעברית)
```

#### בדיקה 2: עיבוד פוסט
```
1. Posts > Add New
2. כתוב כותרת ותוכן
3. צד ימין > AI Assistant > ✓ Enable AI processing
4. Process with AI
5. בדוק שנוצרו תגיות וקטגוריות
```

#### בדיקה 3: Alt Text לתמונה
```
1. Media > Add New
2. העלה תמונה
3. המתן 5-10 שניות
4. בדוק שנוצר alt text אוטומטית
```

---

## ⚙️ הגדרות מומלצות

### למי שרוצה לחסוך בעלויות 💰
```
✅ General Settings:
   Active Provider: Google AI
   
✅ API Credentials:
   Google AI:
   - Default Model: Gemini 2.5 Flash-Lite (fast/small)
   
✅ AI Features:
   ✓ Auto-generate alt text for images
   ☐ Auto-tagging and categorization (כבה - דורש הרבה tokens)
   ☐ Auto-generate excerpt (כבה - דורש tokens)
   ☐ Auto-create categories (כבה - דורש tokens)
   ✓ Auto-tag uploaded media
```

**עלות משוערת**: ~$0.10-0.50 לחודש (שימוש בינוני)

---

### למי שרוצה איכות מקסימלית 🌟
```
✅ General Settings:
   Active Provider: OpenAI
   
✅ API Credentials:
   OpenAI:
   - Default Model: GPT-5.1 (Latest flagship)
   
✅ AI Features:
   ✓ Auto-generate alt text for images
   ✓ Auto-tagging and categorization
   ✓ Auto-generate excerpt
   ✓ Auto-create categories
   ✓ Auto-tag uploaded media
   
✅ General Prompt Instructions:
   "כתוב בעברית ברמה גבוהה. השתמש בשפה ברורה ומקצועית. 
   הימנע מז'רגון. התמקד בתוכן איכותי ושימושי."
```

**עלות משוערת**: ~$5-20 לחודש (שימוש אינטנסיבי)

---

### למי שרוצה מאוזן ⚖️
```
✅ General Settings:
   Active Provider: Anthropic
   
✅ API Credentials:
   Anthropic:
   - Default Model: Claude Sonnet 4.5
   
✅ AI Features:
   ✓ Auto-generate alt text for images
   ✓ Auto-tagging and categorization
   ☐ Auto-generate excerpt (ידני - רק כשצריך)
   ☐ Auto-create categories (ידני - רק כשצריך)
   ✓ Auto-tag uploaded media
```

**עלות משוערת**: ~$1-5 לחודש (שימוש בינוני)

---

## 🌍 הגדרה לאתר רב-לשוני

### עם WPML
```bash
# אין צורך בשינוי!
# התוסף מזהה אוטומטית את שפת הפריט מ-WPML

תוצאה:
- פוסט בעברית → תגיות בעברית
- פוסט באנגלית → תגיות באנגלית
- פוסט בספרדית → תגיות בספרדית
```

### עם Polylang
```bash
# אין צורך בשינוי!
# התוסף מזהה אוטומטית את שפת הפריט מ-Polylang

תוצאה:
- כל פריט מקבל תוכן AI בשפה שלו
```

### ללא תוסף רב-לשוני
```bash
# התוסף משתמש ב-WordPress locale

Settings > General > Site Language: עברית
→ כל התוכן שנוצר יהיה בעברית

Settings > General > Site Language: English
→ כל התוכן שנוצר יהיה באנגלית
```

---

## 🎨 שימוש ביצירת תמונות

### צעד 1: ודא שיש לך Google AI API Key
```
AI Assistant > Settings > API Credentials > Google AI
API Key: [הזן מפתח מ-ai.google.dev]
Save Changes
```

### צעד 2: נסה ב-Playground
```
AI Assistant > AI Playground

Prompt:
תמונה של חתול חמוד יושב על ספה בסלון מעוצב, 
סגנון מודרני, אור טבעי, צבעים חמים

Generate
```

### צעד 3: שימוש בקוד
```php
// ב-theme או plugin
$ai_manager = AT_AI_Manager::get_instance();

$result = $ai_manager->generate_image(
    'נוף של הרים מושלגים עם שקיעה',
    array(
        'model' => 'gemini-2.5-flash-image',
        'num_images' => 1,
        'size' => '1024x1024'
    )
);

if (!is_wp_error($result)) {
    foreach ($result['images'] as $image) {
        // שמור את התמונה
        $image_data = base64_decode($image['data']);
        file_put_contents('generated-image.png', $image_data);
    }
}
```

---

## 🔧 פתרון בעיות נפוצות

### ❌ "API key is required"
```
פתרון:
1. Settings > API Credentials
2. הזן API Key
3. Test Connection
4. Save Changes
```

### ❌ "Connection failed"
```
פתרון:
1. בדוק שה-API Key נכון
2. בדוק שיש יתרת זכות ב-account
3. בדוק שאין חסימת firewall
4. נסה provider אחר
```

### ❌ התוסף לא בעברית
```
פתרון:
1. Settings > General > Site Language → עברית
2. Deactivate plugin
3. Activate plugin
4. רענן דף
```

### ❌ תגיות לא נוצרות
```
פתרון:
1. בדוק ש-AI Features מופעל
2. בדוק שיש API key
3. בדוק שסוג הפוסט מופעל ב-Settings
4. בדוק ב-Usage & Costs אם יש שגיאות
```

### ❌ Alt text לא נוצר
```
פתרון:
1. Settings > AI Features
2. ✓ Auto-generate alt text for images
3. Save Changes
4. העלה תמונה מחדש
```

---

## 📊 מעקב שימוש ועלויות

```
AI Assistant > Usage & Costs

מה תראה:
• Total Requests - כמה בקשות API בוצעו
• Total Tokens - כמה tokens נוצלו
• Total Cost - כמה זה עלה ($)
• Usage by Provider - פירוט לפי ספק
```

**טיפ**: עקוב אחר העלויות באופן שבועי. אם עולה מעל התקציב, שנה ל-Mini/Flash models.

---

## 🎯 תרחישי שימוש נפוצים

### תרחיש 1: בלוג תוכן בעברית
```
הגדרות:
- Provider: Google AI (זול)
- Model: Gemini 2.5 Flash
- Features: alt text + media tagging
- Prompt Instructions: "כתוב בעברית תקנית וברורה"

תוצאה:
- תמונות מקבלות alt text בעברית
- מדיה מתוייגת אוטומטית
- עלות נמוכה
```

### תרחיש 2: אתר חדשות מקצועי
```
הגדרות:
- Provider: OpenAI
- Model: GPT-5.1
- Features: הכל מופעל
- Prompt Instructions: "סגנון עיתונאי מקצועי, תמצית לעניין"

תוצאה:
- תקצירים איכותיים
- תיוג מדויק
- קטגוריזציה חכמה
```

### תרחיש 3: חנות מקוונת (WooCommerce)
```
הגדרות:
- Provider: Anthropic
- Model: Claude Sonnet 4.5
- Post Types: ✓ product
- Prompt Instructions: "תיאורים שיווקיים, הדגש יתרונות"
- Visual Style: "תמונות מוצר על רקע לבן, תאורה מקצועית"

תוצאה:
- תיאורי מוצרים משכנעים
- תמונות מוצר מקצועיות
- תיוג לפי קטגוריות
```

### תרחיש 4: אתר רב-לשוני (WPML)
```
הגדרות:
- Provider: OpenAI
- Model: GPT-5 Mini (חסכוני)
- Features: tagging + alt text
- אין צורך בהגדרות שפה מיוחדות!

תוצאה:
- פוסטים בעברית → תגיות בעברית
- פוסטים באנגלית → תגיות באנגלית
- פוסטים בערבית → תגיות בערבית
```

---

## 📖 קישורים שימושיים

### תיעוד
- [README.md](README.md) - מדריך מלא
- [CHANGELOG.md](CHANGELOG.md) - היסטוריית שינויים
- [IMPLEMENTATION-SUMMARY-v1.2.0.md](IMPLEMENTATION-SUMMARY-v1.2.0.md) - פרטי עדכון טכניים

### API Keys
- [OpenAI](https://platform.openai.com/api-keys)
- [Anthropic](https://console.anthropic.com)
- [Google AI](https://ai.google.dev)

### תמיכה
- [GitHub Issues](https://github.com/athbss/wp-ai-bro/issues)
- [amit-trabelsi.co.il](https://amit-trabelsi.co.il)

---

## ✨ כל המודלים הזמינים (41 סה"כ)

### OpenAI (15)
```
✨ GPT-5 Series (Latest):
   • GPT-5.1 (flagship) ⭐
   • GPT-5 Pro
   • GPT-5
   • GPT-5 Mini 💰
   • GPT-5 Nano 💰💰

✨ GPT-4.1 Series:
   • GPT-4.1
   • GPT-4.1 Mini
   • GPT-4.1 Nano

🧠 Reasoning Models:
   • O3, O3 Pro, O3 Mini
   • O4 Mini, O4 Mini Deep Research
   
🔄 Legacy:
   • GPT-4o, GPT-4o Mini
```

### Anthropic (13)
```
✨ Claude Opus 4.5 (Latest):
   • Claude Opus 4.5 ⭐
   • Claude Opus 4.5 Snapshot (2025-11-01)

✨ Claude Opus 4.1:
   • Claude Opus 4.1
   • Claude Opus 4.1 Snapshot (2025-08-05)

✨ Claude Sonnet 4.5:
   • Claude Sonnet 4.5
   • Claude Sonnet 4.5 Snapshot (2025-09-29)

✨ Claude Haiku 4.5:
   • Claude Haiku 4.5 💰
   • Claude Haiku 4.5 Snapshot (2025-10-01)

+ עוד...
```

### Google AI (13)
```
✨ Gemini 3 Series (Latest):
   • Gemini 3 Pro ⭐
   • Gemini 3 Pro Preview

✨ Gemini 2.5 Series:
   • Gemini 2.5 Pro
   • Gemini 2.5 Flash 💰
   • Gemini 2.5 Flash-Lite 💰💰

🎨 Image Generation:
   • Gemini 2.5 Flash Image 🖼️
   • Nano Banana 🖼️
   • Nano Banana Pro 🖼️

🎬 Video Generation:
   • Veo 3.1 🎥

+ עוד...
```

**מקרא**:
- ⭐ = מומלץ לאיכות
- 💰 = זול
- 💰💰 = זול מאוד
- 🖼️ = יצירת תמונות
- 🎥 = יצירת וידאו
- 🧠 = Reasoning

---

## 🎓 למידה מתקדמת

### 1. הנחיות דינמיות לפי סוג פוסט
```php
add_filter('at_ai_assistant_prompt_instructions', function($instructions, $post_id) {
    $post_type = get_post_type($post_id);
    
    if ($post_type === 'product') {
        $instructions .= "\nתיאור שיווקי משכנע. הדגש יתרונות ותכונות.";
    } elseif ($post_type === 'post') {
        $instructions .= "\nסגנון עיתונאי מקצועי. עובדות ומקורות.";
    }
    
    return $instructions;
}, 10, 2);
```

### 2. שינוי מודל לפי פעולה
```php
add_filter('at_ai_assistant_model_for_action', function($model, $action) {
    if ($action === 'translation') {
        return 'gpt-5-nano'; // זול לתרגום
    } elseif ($action === 'tagging') {
        return 'gpt-5.1'; // איכותי לתיוג
    }
    return $model;
}, 10, 2);
```

### 3. לוג מפורט
```php
// הפעל logging
add_action('at_ai_assistant_after_generation', function($result, $action, $post_id) {
    error_log(sprintf(
        '[AI] Action: %s, Post: %d, Tokens: %d, Cost: $%s',
        $action,
        $post_id,
        $result['usage']['total_tokens'],
        $result['cost']
    ));
}, 10, 3);
```

---

## 🏆 Best Practices

### 1. התחל קטן
```
✅ התחל עם Mini/Flash models
✅ הפעל רק 1-2 features
✅ עקוב אחר עלויות
✅ אחרי שבוע, החלט אם לשדרג
```

### 2. הגדר הנחיות ברורות
```
רע:
"כתוב טוב"

טוב:
"כתוב בעברית תקנית. השתמש במשפטים קצרים. 
הימנע מז'רגון. התמקד בתועלת למשתמש."
```

### 3. בדוק תוצאות
```
✅ בדוק תגיות שנוצרו - רלוונטיות?
✅ בדוק alt text - מדויק?
✅ בדוק תקצירים - מייצגים את התוכן?
```

### 4. שמור על תקציב
```
✅ הגדר תקרת עלות ב-OpenAI/Anthropic/Google
✅ עקוב ב-Usage & Costs שבועית
✅ שנה ל-Mini models אם עובר תקציב
```

---

## 🎁 בונוס: Snippets שימושיים

### Snippet 1: צור תקציר בעברית
```php
$ai_manager = AT_AI_Manager::get_instance();
$post_content = get_post_field('post_content', $post_id);

$result = $ai_manager->generate_text(
    "צור תקציר של 2-3 שורות לתוכן הבא:\n\n" . $post_content,
    array('max_tokens' => 150),
    $post_id // זיהוי שפה אוטומטי
);

if (!is_wp_error($result)) {
    update_post_meta($post_id, 'custom_excerpt', $result['text']);
}
```

### Snippet 2: תרגום אוטומטי
```php
$translator = new AT_Text_Translator();
$hebrew_text = "שלום עולם";

$english = $translator->translate_text(
    $hebrew_text,
    'en', // target
    'he', // source
    array('wordpress', 'greeting')
);

echo $english; // "Hello world"
```

### Snippet 3: תגיות חכמות
```php
$auto_tagger = new AT_Auto_Tagger();
$post_id = 123;
$post = get_post($post_id);

// התוסף זוהה את השפה אוטומטית ויצור תגיות מתאימות
// אם הפוסט בעברית → תגיות בעברית
// אם הפוסט באנגלית → תגיות באנגלית
```

---

## 📞 צריך עזרה?

### שאלות נפוצות
1. **כמה זה עולה?** → תלוי במודל ובשימוש. בדוק ב-Usage & Costs
2. **איזה מודל הכי טוב?** → GPT-5.1 לאיכות, Gemini Flash לחיסכון
3. **איך משנים שפה?** → אוטומטי! התוסף זוהה מה-WordPress
4. **יש מגבלות?** → תלוי ב-API provider שלך
5. **זה בטוח?** → כן, כל המפתחות מוצפנים

### תמיכה טכנית
- 🐛 **באגים**: [GitHub Issues](https://github.com/athbss/wp-ai-bro/issues)
- 💬 **שאלות**: amit@amit-trabelsi.co.il
- 🌐 **אתר**: [amit-trabelsi.co.il](https://amit-trabelsi.co.il)

---

**בהצלחה! 🚀**

התוסף מוכן לשימוש עם כל התכונות החדשות.

---

*עדכון אחרון: 8 דצמבר 2025*  
*גרסה: 1.2.0*  
*מפתח: Amit Trabelsi*

