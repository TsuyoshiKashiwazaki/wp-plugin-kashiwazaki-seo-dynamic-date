# Kashiwazaki SEO Dynamic Date

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0--dev-orange.svg)](https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-dynamic-date/releases)

投稿や固定ページに動的な日付を表示するショートコードを提供し、コンテンツを常に最新の状態に保つことでSEO効果を向上させるWordPressプラグインです。

## 概要

このプラグインは、投稿や固定ページに動的な日付を簡単に挿入できる `[ksdate]` ショートコードを提供します。これにより、コンテンツの鮮度を保ち、検索エンジンに対して最新の情報であることをアピールできます。

## 主な機能

- **動的日付表示**: 現在の日付を自動的に表示
- **カスタムフォーマット**: PHPの`date()`関数と同じ形式でカスタマイズ可能
- **相対日付**: 過去や未来の日付を相対的に指定可能（例：20年前、6ヶ月前など）
- **日付差分計算**: 指定日からの経過時間、未来の日付までのカウントダウンを表示
  - 年単位: `[ksdate diff="1999" format="年"]` → 26年
  - 月単位: `[ksdate diff="1999-01" format="ヶ月"]` → 313ヶ月
  - 日単位: `[ksdate diff="1999-01-01" format="日"]` → 9794日
- **SEO最適化**: コンテンツの鮮度維持により検索順位向上に貢献
- **軽量実装**: 外部ライブラリ不要で高速動作

## インストール

1. プラグインフォルダを `/wp-content/plugins/` ディレクトリにアップロード
2. WordPressの「プラグイン」メニューからプラグインを有効化
3. 投稿や固定ページで `[ksdate]` ショートコードを使用

## 使い方

### 基本的な使用方法

```
[ksdate]                        // デフォルト: Y年m月d日形式
[ksdate format="Y年m月d日"]      // 日本語形式
[ksdate format="Y/m/d"]         // スラッシュ区切り
[ksdate format="Y-m-d H:i:s"]  // 日時を含む形式
```

### 相対日付の使用

```
[ksdate format="Y年" offset="-20y"]    // 20年前
[ksdate format="Y年m月" offset="-6m"]  // 6ヶ月前
[ksdate format="Y/m/d" offset="+3d"]   // 3日後
[ksdate format="Y/m/d" offset="-1w"]   // 1週間前
```

### 日付差分の使用

#### 過去からの経過時間

```
[ksdate diff="1999"]                    // 出力: 26
[ksdate diff="1999" format="年"]        // 出力: 26年
[ksdate diff="1999-01" format="ヶ月"]   // 出力: 313ヶ月
[ksdate diff="1999-01-01" format="日"]  // 出力: 9794日
```

#### 未来までのカウントダウン

```
[ksdate diff="2030"]                    // 出力: 5
[ksdate diff="2030" format="年"]        // 出力: 5年
[ksdate diff="2025-12" format="ヶ月"]   // 出力: 2ヶ月
[ksdate diff="2025-12-31" format="日"]  // 出力: 67日
```

### パラメータ

| パラメータ | 必須 | デフォルト値 | 説明 |
|----------|------|------------|------|
| `format` | 任意 | `Y年m月d日` | PHP date()関数の形式文字列 |
| `offset` | 任意 | なし | 相対的な日付オフセット |
| `diff` | 任意 | なし | 差分計算の基準日 |

### オフセット形式

- **年:** `y` (例: `-20y`, `+5y`)
- **月:** `m` (例: `-6m`, `+3m`)
- **週:** `w` (例: `-1w`, `+2w`)
- **日:** `d` (例: `-30d`, `+7d`)

### 差分計算形式

- **YYYY形式** (例: "1999", "2030") → デフォルトで年単位
- **YYYY-MM形式** (例: "1999-01", "2025-12") → デフォルトで月単位
- **YYYY-MM-DD形式** (例: "1999-01-01", "2025-12-31") → デフォルトで日単位

## 使用例

### SEOでの活用

```html
<!-- 更新日を常に最新に -->
<p>最終更新: [ksdate format="Y年m月d日"]</p>

<!-- 過去の統計データの表示 -->
<p>[ksdate format="Y年" offset="-1y"]の売上データ</p>

<!-- イベント告知 -->
<p>[ksdate format="m月d日" offset="+7d"]開催予定</p>

<!-- 記事の鮮度アピール -->
<p>この情報は[ksdate format="Y年m月"]時点のものです。</p>

<!-- コピーライト表記 -->
<p>© [ksdate format="Y"] Your Company Name. All Rights Reserved.</p>

<!-- 創業からの年数 -->
<p>1999年から[ksdate diff="1999" format="年"]が経過しました</p>

<!-- カウントダウン -->
<p>2030年まであと[ksdate diff="2030" format="年"]</p>
```

### 実用的な使用例

1. **ニュース・レポート記事**
   ```html
   <div class="article-meta">
      <span>公開日: 2024年1月1日</span>
      <span>最終確認: [ksdate format="Y年m月d日"]</span>
   </div>
   ```

2. **統計情報の表示**
   ```html
   <h2>[ksdate format="Y年" offset="-1y"]から[ksdate format="Y年"]までの成長率</h2>
   <p>過去[ksdate format="n" offset="-3m"]ヶ月間のデータ分析</p>
   ```

3. **イベント・キャンペーン**
   ```html
   <div class="campaign">
      <h3>期間限定キャンペーン</h3>
      <p>[ksdate format="n月j日"]から[ksdate format="n月j日" offset="+7d"]まで</p>
   </div>
   ```

## フォーマット文字列の例

よく使用されるフォーマット：

- `Y年m月d日` - 2025年10月24日
- `Y/m/d` - 2025/10/24
- `Y-m-d` - 2025-10-24
- `Y年n月j日` - 2025年10月24日（ゼロパディングなし）
- `Y年m月` - 2025年10月
- `m月d日` - 10月24日
- `Y` - 2025（年のみ）
- `F j, Y` - October 24, 2025（英語形式）
- `l, F j, Y` - Thursday, October 24, 2025（曜日付き英語形式）

## 技術仕様

### 要件
- WordPress 5.8以上
- PHP 7.4以上

### セキュリティ
- すべての入力パラメータはサニタイズ処理
- XSS攻撃に対する保護（`esc_html()`使用）
- 直接アクセスからの保護

### パフォーマンス
- 軽量な実装（外部ライブラリ不要）
- キャッシュプラグインとの互換性
- WordPressのタイムゾーン設定に準拠

## 管理画面

管理メニューから「KS Dynamic Date」を選択すると、使用方法とサンプルコードを確認できます。

## トラブルシューティング

### 日付が表示されない場合
- ショートコードの記述が正しいか確認してください
- プラグインが有効化されているか確認してください

### 不正な日付が表示される場合
- `format`パラメータの形式が正しいか確認してください
- `offset`パラメータの形式が正しいか確認してください（例：`-20y`, `+3d`）

### タイムゾーンが異なる場合
- WordPressの設定 > 一般 > タイムゾーンを確認してください

## ライセンス

GPL v2 or later

## 作者

**柏崎剛 (Tsuyoshi Kashiwazaki)**
- ウェブサイト: https://www.tsuyoshikashiwazaki.jp

## サポート

ご質問やバグ報告は、[作者のウェブサイト](https://www.tsuyoshikashiwazaki.jp)までお問い合わせください。

## 更新履歴

### バージョン 1.0.0 (2025-10-26)
- 初回リリース
- 基本的なショートコード機能の実装
- オフセット計算機能の実装
- 日付差分計算機能の実装（年・月・日）
- 過去と未来の両方に対応
- 管理画面の追加

## 今後の拡張予定

- 曜日表示のサポート
- より多くの言語への対応
- ブロックエディタ（Gutenberg）対応
- カスタムキャッシュオプション
- より高度な日付計算機能