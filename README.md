# 學裏ライブラリ
WEB開発ツールです。

公開ディレクトリ以外に権限がなくても、PHPさえ動くならどんなサーバーでも使える！(.htaccess可能なものを推奨)

下に色々書いてありますが、基本的に何も設定しなくても最初から動作します。

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
その他、不要になったファイルは削除してください。(例えば、「/gaku-ura/main/js.php」と「/gaku-ura/main/css.php」は「/gaku-ura/main/src_link.php」に統合されたので削除)


## 設定ファイル
gaku-ura/conf/gaku-ura.conf を編集して下さい。

/gaku-uraのパーミッションは777にするか、所有権をphp実行者にしてください。


## 会員制サイト機能
gaku-ura.conf の設定でログイン機能が有効のとき使用可能です。

ユーザー登録で、パスワードのハッシュ化を行うため、**サイトを起動する必要があります。**

トップページにアクセスし、メニューの「ユーザー」をクリックすると登録画面に行きます。

登録すると、gaku-ura/data/users/user_list.tsv に情報が書き込まれます。

「admin」列の値を「4」に書き換えると、そのユーザーの権限が最高になります。

この値は0から4の5段階あり、承認式の会員制サイトを想定しています。

最高権限のユーザーは、他のすべてのユーザーを管理したり、設定ファイルを「管理機能(WEBファイル管理機能)」で編集できます。


## テンプレート
gaku-ura/data/default の中にテンプレートがあります。

default.html がテンプレートのhtmlファイルで、

default.css が共通のcssファイルです。(htmlメソッドの引数オプションで共通cssを使わなく出来ます)


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

jsファイルの先頭に、```#!option notminify;```と記述しても同様の効果が得られます。


#!include命令などの詳細は、gaku-ura/data/default/libをご覧下さい。




以下の内容はPHP開発者向けです。ただ単にサイトを作る目的であれば不要です。(極論、何もせずに公開ディレクトリにコピーするだけでも動作する)

## GakuUraクラス
gaku-ura/conf/conf.php のGakuUraクラスは、引数に「サードパーティーのjavascript可否」をとります。

これは、外部サイトのjavascriptを導入するなどのために、「コンテントセキュリティーポリシー」の基準を緩和する目的です。

任意引数で、指定するとgaku-ura.confより優先されます。全体に適用したい場合は、confファイルに記述することを推奨します。


file_lock, file_unlockメソッドについては、同時にファイルにアクセスさせたくない場合に使用します。

例えば、ファイルの行数をカウントしている途中に、他からファイルを変更されて行数が変わってしまうことを防止する時に使います。


## GakuUraUserクラス
gaku-ura標準以外で、ユーザー情報を使用する際に使います。

詳細はgaku-ura/conf/users.phpで。

(ユーザー情報にアクセスする機能を提供します)


## 推奨するphp.ini
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

