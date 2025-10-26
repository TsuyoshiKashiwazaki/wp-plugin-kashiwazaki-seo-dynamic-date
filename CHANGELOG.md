# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-10-26

### Added
- 初回リリース
- 動的日付表示機能: `[ksdate]` ショートコードで現在の日付を表示
- カスタム日付フォーマット: PHPの`date()`関数形式に対応
- オフセット機能: 相対的な日付指定（年・月・週・日）
- 日付差分計算機能: 過去からの経過時間と未来へのカウントダウン
  - 年単位の差分計算
  - 月単位の差分計算
  - 日単位の差分計算
- 管理画面の実装
  - 設定ページ: デフォルト日付フォーマット設定
  - プレビュー機能: リアルタイムで日付プレビュー
  - 使い方ガイド: 詳細な使用方法と説明
  - 使用例: 実用的なコード例とプレビュー
- 完全日本語化: 管理画面とドキュメント
- WordPressタイムゾーン対応
- セキュリティ対策: サニタイズ、エスケープ処理

### Technical Details
- WordPress 5.8以上に対応
- PHP 7.4以上に対応
- 外部ライブラリ不要の軽量実装
- GPL-2.0-or-laterライセンス
