# 學裏ライブラリ
WEB開発ツールです。

公開ディレクトリ以外に権限がなくても、PHPさえ動くならどんなサーバーでも使える！(.htaccess可能なものを推奨)


## 設置・アップデート方法
```
html
├── css
│   └── index.php
├── js
│   └── index.php
├── 404.php
├── index.php
├── LICENSE
├── README.md
├── favicon.ico
├── favicon.png
├── gaku-ura
│   ├── conf
│   │   ├── alt-mbstring.php (mbstringが使えない環境向けライブラリ)
│   │   ├── conf.php (中核ライブラリ)
│   │   ├── db.php (db操作ライブラリ)
│   │   ├── gaku-ura.conf (設定ファイル)
│   │   └── users.php (ユーザー情報操作ライブラリ)
│   ├── data (静的ファイルとデータ格納ディレクトリ)
│   │   ├── 404
│   │   │   ├── css
│   │   │   │   └── index.css
│   │   │   └── html
│   │   │       └── index.html
│   │   ├── default
│   │   │   ├── default.css (共通css)
│   │   │   ├── default.html (テンプレート)
│   │   │   ├── description.txt
│   │   │   ├── file (新規作成テンプレート)
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
│   │   │   └── lib (include命令で使えるライブラリ)
│   │   │       ├── css
│   │   │       │   └── animation.css
│   │   │       ├── description.txt
│   │   │       └── js
│   │   │           ├── element.js
│   │   │           ├── keyboard.js
│   │   │           ├── popup.js
│   │   │           ├── reload_csrf.js
│   │   │           └── string.js
│   │   ├── description.txt
│   │   ├── home (/index.php で使うデータ)
│   │   │   ├── css
│   │   │   │   ├── document.css
│   │   │   │   └── index.css
│   │   │   └── html
│   │   │       ├── about.html
│   │   │       └── index.md
│   │   └── users
│   │       ├── css
│   │       │   ├── index.css
│   │       │   └── upgrade.css
│   │       ├── description.txt
│   │       ├── html
│   │       │   ├── custom (アップグレード対象外 カスタム用ファイル)
│   │       │   │   ├── admin_shortcut.html
│   │       │   │   └── custom.html
│   │       │   ├── admin.html
│   │       │   ├── admin_edit.html
│   │       │   ├── admin_edit_table.html
│   │       │   ├── home.html
│   │       │   ├── home_admin.html
│   │       │   ├── login.html
│   │       │   ├── regist.html
│   │       │   ├── upgrade.html
│   │       │   └── user_page.html
│   │       ├── js
│   │       │   ├── admin.js
│   │       │   ├── admin_edit.js
│   │       │   ├── admin_edit_table.js
│   │       │   └── upgrade.js
│   │       └── user_list* (ユーザーデータベース)
│   ├── description.txt
│   ├── flock (ファイル読み取りから書き込みまでの間を処理待ちさせるフラグ管理する場所)
│   └── main
│       ├── 404.php (一般エラーページ)
│       ├── description.txt
│       ├── index.php (トップページ)
│       ├── src_link.php (cssやjsを配信)
│       └── users.php (ユーザーページや管理機能)
└── users
    ├── admin
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

/gaku-ura 以下全てのパーミッションは777にするか、所有権をphp実行者にしてください。

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

index.html以外は「index.css」ではなく「document.css」が適用されます。


## javascript軽量化
一般的なjavascriptの記法ではない。または、不完全な文法で記述している。(行末のセミコロンが抜けているなど)

という場合は、軽量化を有効にしているとブラウザエラーになることがあります。

htmlメソッドの$minifyをfalseにすると、無効に出来ます。すると、

コメントアウト除去も行われません。

jsファイルの先頭に、```#!option notminify;```と記述しても同様の効果が得られます。#!include命令などの詳細は、gaku-ura/data/default/libをご覧下さい。


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
display_startup_errors = Off
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
session.referer_check =
session.cache_limiter = nocache
session.cache_expire = 180
session.use_trans_sid = 1
session.trans_sid_tags = "form="
session.hash_function = 1
session.hash_bits_per_character = 5
;以下3つは特に重要
session.use_strict_mode = 1
session.cookie_httponly = 1
session.cookie_samesite = "Strict"

[Date]
date.timezone = "Asia/Tokyo"
```

## 外部ライブラリ
### CDN
* ACE editor
ファイル編集機能に使用を選択できます。
website: https://ace.c9.io/
github: https://github.com/ajaxorg/ace

### ダウンロード

