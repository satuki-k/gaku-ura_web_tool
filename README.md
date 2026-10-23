# 學裏ライブラリ
WEB開発ツールです。

公開ディレクトリ以外に権限がなくても、PHPさえ動くならどんなサーバーでも使える！(.htaccess可能なものを推奨)


## 設置・アップデート方法
```

├── 404.php (404エラーの他、見せることができない場合に表示)
├── LICENSE
├── README.md (このファイル)
├── css
│   └── index.php (linkタグでcssを読み込むときの参照先)
├── favicon.ico
├── favicon.png
├── gaku-ura (外部に公開せず、サーバー内部だけでアクセスできるように設定してください)
│   ├── conf (ライブラリ、設定)
│   │   ├── alt-mbstring.php (mbstringが無い環境で適宜、conf.phpに読み込ませてください)
│   │   ├── conf.php (gaku-ura中核ライブラリ)
│   │   ├── db.php (データベース操作)
│   │   ├── gaku-ura.conf (設定ファイル)
│   │   ├── math.php (数的処理)
│   │   └── users.php (ユーザー管理)
│   ├── data
│   │   ├── 404
│   │   │   ├── css
│   │   │   │   └── index.css
│   │   │   └── html
│   │   │       ├── form_die.html (POSTリクエストで不正がある場合)
│   │   │       └── index.html (基本的なエラーページ)
│   │   ├── default (テンプレートファイル)
│   │   │   ├── default.css (デフォルトでは共通して適用するCSS)
│   │   │   ├── default.html (デフォルトで採用するテンプレートHTML)
│   │   │   ├── description.txt
│   │   │   ├── mail.html
│   │   │   ├── file (ファイルマネージャーで「新規作成」に使い初期内容)
│   │   │   │   ├── description.txt
│   │   │   │   ├── index.css
│   │   │   │   ├── index.html
│   │   │   │   ├── index.js
│   │   │   │   ├── index.php
│   │   │   │   ├── index.pl
│   │   │   │   ├── index.py
│   │   │   │   ├── main.php
│   │   │   │   ├── robots.txt
│   │   │   │   └── sitemap.xml
│   │   │   └── lib (php以外のライブラリ)
│   │   │       ├── css
│   │   │       │   ├── animation.css (アニメーション用CSS)
│   │   │       │   ├── popup.css (popup.jsに使うstyleデータ)
│   │   │       │   └── tabs.css (モーダル画面用CSS)
│   │   │       ├── description.txt
│   │   │       └── js
│   │   │           ├── element.js (document.getElement...を省略する)
│   │   │           ├── keyboard.js (OSごとにキー名称が異なって判定が漏れるのを埋める)
│   │   │           ├── popup.js (汎用的なダイアログ表示)
│   │   │           ├── reload_csrf.js (csrfトークンをリロードしてセッションタイムアウトを抑止)
│   │   │           └── string.js (文字列操作)
│   │   ├── description.txt
│   │   ├── home (/index.phpで表示するページ)
│   │   │   ├── css
│   │   │   │   ├── document.css (トップページ以外に適用するcss, 変更可能)
│   │   │   │   └── index.css (トップページに適用するcss, 変更可能)
│   │   │   └── html
│   │   │       ├── about.md
│   │   │       └── index.html (トップページ)
│   │   └── users
│   │       ├── css
│   │       │   ├── editor.css
│   │       │   ├── index.css
│   │       │   ├── upgrade.css
│   │       │   └── users.css
│   │       ├── description.txt
│   │       ├── html
│   │       │   ├── admin.html
│   │       │   ├── admin_edit.html
│   │       │   ├── admin_edit_table.html
│   │       │   ├── custom (アップグレード対象外、各自でカスタムする用)
│   │       │   │   ├── admin_shortcut.html (管理で表示するショートカット)
│   │       │   │   └── custom.html (ユーザーページの「全員へ」メッセージ)
│   │       │   ├── home.html
│   │       │   ├── home_admin.html
│   │       │   ├── login.html
│   │       │   ├── mail.html
│   │       │   ├── regist.html
│   │       │   ├── upgrade.html
│   │       │   ├── upgrade_form.html
│   │       │   └── user_page.html
│   │       ├── js
│   │       │   ├── admin.js
│   │       │   ├── admin_edit.js
│   │       │   ├── admin_edit_table.js
│   │       │   ├── editor_lang.json
│   │       │   ├── home.js
│   │       │   ├── table_cmd.json
│   │       │   └── upgrade.js
│   │       ├── message (個別・掲示板のメッセージデータ)
│   │       ├── mail (メール認証用の一時ファイル)
│   │       ├── template.css
│   │       ├── template.html
│   │       ├── template_home.html
│   │       └── user_list.tsv
│   ├── description.txt
│   ├── flock (ファイルの同時アクセス防止フラグ用)
│   └── main (本体プログラム)
│       ├── 404.php (/404.phpの本体)
│       ├── description.txt
│       ├── index.php (/index.phpの本体)
│       ├── src_link.php (/css/index.php, /js/index.phpの本体)
│       └── users.php (ユーザーページ及び狩り機能の本体)
├── index.php
├── js
│   └── index.php
└── users
    ├── admin
    │   └── index.php
    ├── mail
    │   └── index.php
    ├── index.php
    └── login
        ├── index.php
        └── regist
            └── index.php
```
公開ディレクトリに全て配置します。ドキュメントルート直下である必要はありません。(サブディレクトリで公開するときは、設定ファイルの「u_root」項目を確認・編集してください)

gaku-ura9.7.0以降は、http://bq.f5.si/?Page=code で配布されているtar.gzファイルを送信することでアップグレードできます。
ただし、更新対象ファイルに登録されていないファイル(readmeやlicense、一部のhtmlやcssなど編集が前提のファイルなど)は、手動で上書きしない限り更新されません。

/gaku-ura 以下全てを、www-dataが書き込み可能な状態にしてください。

## 設定ファイル
gaku-ura/conf/gaku-ura.conf を編集して下さい。管理ページの「設定」リンクからも行けます。


## ユーザー登録機能
gaku-ura.conf の設定でログイン機能が有効のとき使用可能です。

登録は、トップページにアクセスしてメニューの「ユーザー」をクリックすると新規登録の案内が表示されます。

登録すると、gaku-ura/data/users/user_list.tsv に情報が書き込まれます。

「admin」列の値を「4」に書き換えると、そのユーザーの権限が最高になります。
このユーザーはサイトをフルコントロールできます。

## テンプレート
gaku-ura/data/default の中にテンプレートがあります。

default.html がテンプレートのhtmlファイルで、

default.css が共通のcssファイルです。(htmlメソッドの引数オプションまたはhtmlfメソッドで指定されたファイルに記述するオプションで共通cssを使わなく出来ます)


## ブログ
gaku-ura/data/home は「http(s)://ドメイン/」でアクセスしたときのページ内容を保管する場所です。

そこにhtmlやcss、javascriptなどを配置します。

PHPなどのサーバー側スクリプトは全て、gaku-ura/main にあります。

htmlに適用するcssやjavascriptは、htmlファイル内にinclude命令やoption設定で指定します。

## javascript軽量化
一般的なjavascriptの記法ではない。または、不完全な文法で記述している。(行末のセミコロンが抜けているなど)

という場合は、軽量化を有効にしているとブラウザエラーになることがあります。

GakuUra::htmlメソッドの$minifyをfalseにすると、無効に出来ます。すると、

コメントアウト除去も行われません。

jsファイルの先頭に、```#!option notminify;```と記述しても同様の効果が得られます。#!include命令などの詳細は、http://bq.f5.si/?Page=code#src　をご覧ください。


一括で無効化する場合は、htmlfメソッド仕様時に指定ファイルで```<!option js_minify 0>```と記述すると、$minify=falseと同じ意味になります。


## PHP
### GakuUraクラス
gaku-ura/conf/conf.php のGakuUraクラスは、任意引数に「厳格なCSPの有効化」をとります。

デバッグややむを得ない理由が無い限り、引数を省略してデフォルトにしておきます。

引数を指定するとgaku-ura.confの「use_nonce」より優先されます。


file_lock, file_unlockメソッドについては、同時にファイルにアクセスさせたくない場合に使用します。

例えば、ファイルの行数をカウントしている途中に、他からファイルを変更されて行数が変わってしまうことを防止する時に使います。


### GakuUraUserクラス
ユーザー情報にアクセスするときに使用します。ユーザー情報取得、変更、存在確認、登録などの機能があります。

### 推奨するphp.ini
php.iniを編集出来る場合は、以下の値に変更が推奨されます。

```
[PHP]
default_charset = UTF-8
display_errors = Off
allow_url_fopen = Off
allow_url_include = Off
file_uploads = On
post_max_size = 1G
upload_max_filesize = 1G
short_open_tag = Off
expose_php = Off
log_errors = On

[Session]
session.save_path = "WEBアクセス不能なフォルダの絶対パス(その権限が無い場合は設定しない)"
session.use_cookies = 1
session.use_only_cookies = 0
session.name = SID
session.auto_start = 0
session.cookie_lifetime = 999999
session.cookie_path = /
session.serialize_handler = php
session.use_trans_sid = 1
session.trans_sid_tags = "form="
;以下3つは特に重要
session.use_strict_mode = 1
session.cookie_httponly = 1
session.cookie_samesite = "Strict"

[Date]
date.timezone = "Asia/Tokyo"

phar.readonly = Off
```

## 外部ライブラリ
### CDN
* ACE editor
ファイル編集機能に使用を選択できます。
website: https://ace.c9.io/
github: https://github.com/ajaxorg/ace

### ダウンロード

