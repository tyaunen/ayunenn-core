# ayutenn/core

ayutennは、@tyau_nen_によるオレオレフレームワークです！
個人的な学習と趣味のプロジェクトでの利用を目的としており、第三者による利用は推奨しません。
使うたびに毎回足りない機能が見つかるので、俺が使うたびに更新されていきます。

## 思想
### 1. ayutennは俺（@tyau_nen_）のためだけに最適化されるべきです。
いかなる場合でも俺が必要としていない機能を、実装してはいけません。
俺が必要としていても、俺がすぐに理解・使用できないコードを実装してはいけません。
それが俺向きならば、あらゆるアンチパターンと非効率を許容しますし、スケーラビリティも無視して構いません。

### 2. ayutennは簡単かつ単純な方法で実装されるべきです。
俺は**大いに努力して**実装をシンプルに保つべきです。
『俺のためにシンプル』はこのプロジェクトの最も重要なドグマであり、
俺はこれを撤回する場合新たなプロジェクトを興す必要があります。

### 3. ayutennは可能な限り小規模なプロジェクトであるべきです。
使用頻度が非常に高い機能だけをここに実装してください。
**__"念の為に"__** だとか **__"いつか使いそうだから"__** を理由にして機能を実装してはいけません。

## 特徴
* **シンプルなルーター**: 動的ルーティングを排除してシンプルに保ったルーティングシステム。
* **ちょっとしたDB操作**: PDOファクトリーやクエリ結果のラッパー、値の一括バインドなど、本当に最低限のSQL発行サポート。
* **MVCもどきバリデータ**: JSONファイルでバリデーションルールを定義するModel + リクエスト処理とバリデーションを統合したController。
* **そのほかユーティリティ色々**: CSRFトークン、ファイル操作、ロガー、UUID、htmlspecialchars()をh()にするとか色々。

## 動作環境
*   PHP 8.0 以上
*   Composer

## インストール
あとで書く

## TODO
* modelファイルの仕様を書き出す
* インストール方法を書く
* assetディレクトリの作成とcss,jsの配信
* utilsのテスト
* マイグレーションについて考える

## 機能
多分書き漏らしいっぱいある

### 1. Routing (ルーティング)

`ayutenn\core\routing\Router` クラスを使用します。
ルート定義ファイルはPHP配列として記述し、`Route` または `RouteGroup` のインスタンスを返します。

**ルート定義例 (`routes/web.php`):**

```php
<?php
use ayutenn\core\routing\Route;
use ayutenn\core\routing\RouteGroup;

return [
    // 単一のルート
    new Route('GET', '/home', 'controller', 'HomeController'),

    // グループ化（ミドルウェア適用など）
    new RouteGroup('/admin', [
        new Route('GET', '/dashboard', 'controller', 'AdminController'),
    ], [new AdminMiddleware()]),
];
```

**エントリポイント (`index.php`):**

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
use ayutenn\core\routing\Router;

// ルート定義ディレクトリとURLプレフィックスを指定
$router = new Router(__DIR__ . '/routes', '/myapp');
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
```

### 2. Database (データベース)

`ayutenn\core\database\DbConnector` で接続し、`ayutenn\core\database\DataManager` でクエリを実行します。

**設定 (`config/config.json`):**
※ `Config` クラスを通じて読み込まれます。

```json
{
    "PDO_DSN": "mysql:host=localhost;dbname=mydb;charset=utf8mb4",
    "PDO_USERNAME": "root",
    "PDO_PASSWORD": ""
}
```

**使用例:**

```php
use ayutenn\core\database\DataManager;

class UserManager extends DataManager
{
    public function getUserMaster(int $min_money): QueryResult
        $conditions = []; // 条件式
        $params = []; // 条件式にバインドするキーと値の組み合わせ

        // 条件式とバインドするものの登録
        $conditions[] = 'mst.money >= :min_money';
        $params[':min_money'] = [$min_money, PDO::PARAM_INT];

        // カンマ区切りで展開
        $where_clause = !empty($conditions) ? implode(' AND ', $conditions) : '';

        $sql = <<<SQL
            SELECT
                mst.user_id,
                mst.user_name,
                mst.money
            FROM
                user_master mst
            WHERE
                {$where_clause}
            ORDER BY
                mst.on_update asc
            ;
        SQL;

        $stmt = $this->executeAndFetchAll($sql, $params);
        return QueryResult::success();

        // $return->isSucceed -> true
        // $return->data -> 取得したデータのarray
}
```

### 3. Requests (MVC)

#### Controller

`ayutenn\core\requests\Controller` を継承してコントローラを作成します。
`run()` メソッドが呼ばれると、自動的にバリデーションが実行され、成功すれば `main()` が呼ばれます。

```php
use ayutenn\core\requests\Controller;

class MyController extends Controller
{
    public static function name(): string { return 'MyController'; }

    // リクエストパラメタに対するバリデーションルール
    // fotmatで指定した、バリデーションルール(json)を読み込む
    protected array $RequestParameterFormat = [
        'user-id' => ['name' => 'ユーザーID', 'format' => 'user_id'],
        'password' => ['name' => 'パスワード', 'format' => 'password'],
        'something' => ['name' => '必須ではない項目', 'format' => 'hogehoge', 'require' => false],
    ];

    protected function main(): void
    {
        // バリデーション + キャスト済のデータには $this->parameter でアクセス
        $userId = $this->parameter['user_id'];

        /* ここにメイン処理 */

        $this->redirect('/ayutenn/top');
    }
}
```

#### Model (Validation)

バリデーションルールはJSONファイルで定義し、`ayutenn\core\requests\Model` で読み込みます。
`Controller` の `$RequestParameterFormat` で指定するフォーマットもこの仕組みを利用しています。

**モデル定義 (`models/user_id.json`):**

```json
{
    "type": "int",
    "min": 1,
    "condition": ["numeric"]
}
```

### 4. Session

`ayutenn\core\session\AlertsSession` を使用して、リダイレクト後のフラッシュメッセージ（アラート）を管理できます。

```php
use ayutenn\core\session\AlertsSession;

// メッセージの保存
AlertsSession::putAlertMessageIntoSession("ユーザーIDは2文字以上である必要があります。");
AlertsSession::putAlertMessageIntoSession("再入力パスワードが一致していません");

// メッセージの取得と表示（ビュー側）
$alerts = AlertsSession::getAlerts();
foreach ($alerts as $alert) {
    echo $alert['text'];
}
```

### 5. Config

`ayutenn\core\config\Config` クラスは、JSON形式の設定ファイルを読み込みます。

```php
use ayutenn\core\config\Config;

// config/app.json の 'APP_NAME' を取得
$appName = Config::getAppSetting('APP_NAME');
```

### 6. Utils

ユーティリティクラス群です。

*   **Logger**: `ayutenn\core\utils\Logger` - ログ出力。
*   **Redirect**: `ayutenn\core\utils\Redirect` - リダイレクト処理。
*   **CsrfTokenManager**: `ayutenn\core\utils\CsrfTokenManager` - CSRFトークンの生成と検証。
*   **FileHandler**: `ayutenn\core\utils\FileHandler` - ファイルアップロード処理など。
*   **DiscordWebhook**: `ayutenn\core\utils\DiscordWebhook` - Discordへの通知。

## ライセンス

CC-BY-1.0
