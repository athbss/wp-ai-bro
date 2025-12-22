#!/bin/bash
###############################################################################
# סקריפט לקמפול קבצי תרגום
# מייצר קבצי .mo מקבצי .po
###############################################################################

set -e

# נתיב לתיקיית התוסף
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LANGUAGES_DIR="$PLUGIN_DIR/languages"

echo "🌍 קמפול קבצי תרגום..."
echo "תיקייה: $LANGUAGES_DIR"
echo ""

# בדיקה אם msgfmt קיים
if ! command -v msgfmt &> /dev/null; then
    echo "❌ שגיאה: msgfmt לא מותקן"
    echo ""
    echo "להתקנה ב-macOS:"
    echo "  brew install gettext"
    echo "  brew link gettext --force"
    echo ""
    exit 1
fi

# קמפול כל קבצי .po
for po_file in "$LANGUAGES_DIR"/*.po; do
    if [ -f "$po_file" ]; then
        # שם הקובץ ללא הסיומת
        base_name=$(basename "$po_file" .po)
        mo_file="$LANGUAGES_DIR/$base_name.mo"
        
        echo "📝 מקמפל: $base_name..."
        msgfmt -o "$mo_file" "$po_file"
        
        if [ $? -eq 0 ]; then
            echo "✅ נוצר: $mo_file"
        else
            echo "❌ שגיאה בקמפול: $po_file"
        fi
    fi
done

echo ""
echo "✨ קמפול הושלם!"
echo ""
echo "קבצים שנוצרו:"
ls -lh "$LANGUAGES_DIR"/*.mo

