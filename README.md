# 學裏ライブラリ
WEB開発ツールです。

公開ディレクトリ以外に権限がなくても、PHPさえ動くならどんなサーバーでも使える！(.htaccess可能なものを推奨)

管理機能を使う際の注意: 「ファイル管理機能が使える最低の権限(デフォルトで3)」以上のユーザーのみ、管理機能が使えます。権限のレベルも「管理機能」のユーザー管理から設定します。
つまり、一番最初の管理者は、**自分の権限を最高権限に設定するために、FTPなどを用いて手動で書き換える必要があります。** gaku-ura/data/users/user_list.tsv を編集します。

これさえやれば、あとはサイトタイトルなどを設定して、htmlやcssを書き換えたらセットアップ完了です。


## 設置・アップデート方法
公開ディレクトリに全て配置します。ドキュメントルート直下である必要はありません。(サブディレクトリで公開するときは、設定ファイルの「u_root」項目を確認してください)

アップデートは、変更点を読み、それぞれのファイルを上書きアップロードまたは修正してください。
あるいは、以下のファイルをまとめて上書きしてください。(ダウンロードして展開したファイルの中から、以下のファイルをあなたのサイトへ上書きアップロードしてください)
```
/gaku-ura/conf 以下の「gaku-ura.conf」以外のファイル
/gaku-ura/data css、html、jsファイル(home/html/* などの編集を加えたファイルはアップデート差分をよく見て修正)
/gaku-ura/main 以下全て
/README.md
/LICENSE
/.gitignore
/index.php
/404.php
/css 以下全て
/js 以下全て
/users 以下全て
```
その他、統廃合されたファイルを適宜削除します。


## 設定ファイル
gaku-ura/conf/gaku-ura.conf を編集して下さい。管理ページの「設定」リンクからも行けます。

/gaku-ura 以下全てのパーミッションは777にするか、所有権をphp実行者にしてください。


## 会員制サイト機能
gaku-ura.conf の設定でログイン機能が有効のとき使用可能です。

最初に管理者を登録する場合は、データ形成を行うためにサイトを起動(公開)します。データフォーマットを十分に理解している方は、手動で入力可能です。

登録は、トップページにアクセスしてメニューの「ユーザー」をクリックすると登録画面に行きます。

登録すると、gaku-ura/data/users/user_list.tsv に情報が書き込まれます。

「admin」列の値を「4」に書き換えると、そのユーザーの権限が最高になります。(この操作を行えるのはadminが4以上のユーザーです。初回はFTPなどを使って書き込む必要があります)

この値は0から4の5段階あり、承認式の会員制サイトを想定しています。

最高権限のユーザーは、他のすべてのユーザーを管理したり、設定ファイルを「管理機能(WEBファイル管理機能)」で編集できます。


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



