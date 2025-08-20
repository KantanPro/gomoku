#!/bin/bash

# プラグイン情報
PLUGIN_NAME="gomoku"
PLUGIN_VERSION="1.0.0"
CURRENT_DATE=$(date +"%Y%m%d")

# 出力先ディレクトリ
OUTPUT_DIR="/Users/kantanpro/Desktop/Game_TEST_UP"

# zipファイル名
ZIP_FILENAME="${PLUGIN_NAME}_${PLUGIN_VERSION}_${CURRENT_DATE}.zip"

# 出力先ディレクトリが存在しない場合は作成
if [ ! -d "$OUTPUT_DIR" ]; then
    echo "出力先ディレクトリを作成中: $OUTPUT_DIR"
    mkdir -p "$OUTPUT_DIR"
fi

# 一時作業ディレクトリを作成
TEMP_DIR=$(mktemp -d)
echo "一時作業ディレクトリを作成: $TEMP_DIR"

# プラグインディレクトリを作成
PLUGIN_DIR="$TEMP_DIR/$PLUGIN_NAME"
mkdir -p "$PLUGIN_DIR"

# 必要なファイルとディレクトリをコピー
echo "ファイルをコピー中..."

# メインファイル
cp gomoku.php "$PLUGIN_DIR/"

# 管理画面
mkdir -p "$PLUGIN_DIR/admin"
cp admin/admin-page.php "$PLUGIN_DIR/admin/"

# インクルードファイル
mkdir -p "$PLUGIN_DIR/includes"
cp includes/class-gomoku-game.php "$PLUGIN_DIR/includes/"
cp includes/class-gomoku-shortcode.php "$PLUGIN_DIR/includes/"

# 公開ファイル
mkdir -p "$PLUGIN_DIR/public/css"
mkdir -p "$PLUGIN_DIR/public/js"
cp public/css/gomoku.css "$PLUGIN_DIR/public/css/"
cp public/js/gomoku.js "$PLUGIN_DIR/public/js/"

# ドキュメントファイル
cp README.md "$PLUGIN_DIR/"
cp readme.txt "$PLUGIN_DIR/"

# zipファイルを作成
echo "zipファイルを作成中: $ZIP_FILENAME"
cd "$TEMP_DIR"
zip -r "$OUTPUT_DIR/$ZIP_FILENAME" "$PLUGIN_NAME"

# 一時ディレクトリを削除
cd - > /dev/null
rm -rf "$TEMP_DIR"

# 結果を表示
echo ""
echo "配布用zipファイルが作成されました:"
echo "ファイル名: $ZIP_FILENAME"
echo "保存先: $OUTPUT_DIR"
echo "ファイルサイズ: $(du -h "$OUTPUT_DIR/$ZIP_FILENAME" | cut -f1)"

# ファイルの内容を確認
echo ""
echo "zipファイルの内容:"
unzip -l "$OUTPUT_DIR/$ZIP_FILENAME" | head -20

echo ""
echo "完了しました！"
