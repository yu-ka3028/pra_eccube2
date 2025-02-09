# ボタンのホバー設定 [4 系](https://github.com/users/yu-ka3028/projects/7?pane=issue&itemId=97060443&issue=yu-ka3028%7Cpra_eccube4%7C33)

## 調査

- 該当ファイル：ec-cube2/data/eccube.js
  - ブラウザの検証ツールでブタンを選択し該当ファイル探す
  - もちろん grep でも OK`grep -rl "class名" ./`
- 用意する画像：通常表示 1 枚、ホバー時 1 枚（ファイル名は通常画像に_on を追加）
- 画像を置く場所：
  - ec-cube2/html/user_data/packages/default/img/button/btn_add_address_complete.jpg
  - ec-cube2/html/user_data/packages/default/img/button/btn_add_address_complete_on.jpg
