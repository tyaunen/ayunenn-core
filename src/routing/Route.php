<?php
namespace ayutenn\core\routing;

use ayutenn\core\config\Config;
use ayutenn\core\routing\Middleware;
use ayutenn\core\utils\Redirect;
use ayutenn\core\requests\Controller;
use ayutenn\core\requests\Api;
use Exception;

/**
 * 【概要】
 * ルート定義クラス
 *
 * 【解説】
 * リクエストに対してどのようなファイルを表示したり、どのような処理を行うかを定義するクラス
 *
 * 【無駄口】
 * 本当はもっといろいろな種類のリクエストやパラメタが定義できるんだろうが、
 * とりあえずはシンプルに保っている
 * 未実装の言い訳は全部『シンプル』。便利な言葉ですよほんと。
 * PUTとかDELETEはともかく、404ぐらいは扱えるようにしたほうがいい……の、かも……？
 *
 */
class Route
{
    /**
     * ルートの定義
     * @param string $method HTTPメソッド
     * @param string $path ルートのパス
     * @param string $routeAction どのファイルを読み込むか "controller" or "view" or "api" or "redirect"
     * @param string $targetResourceName 読み込むファイルのパス system/top.php とか
     *               routeAction = "controller" の場合は、同名クラスのrunメソッドを呼び出す
     *               routeAction = "view" の場合は、同名の.phpファイルを表示する
     *               routeAction = "api" の場合は、同名クラスのrunメソッドを呼び出す
     *               routeAction = "redirect" の場合は、$targetResourceNameにリダイレクト
     * @param array<int, Middleware> $middleware ミドルウェア
     */
    public function __construct(
        public string $method = 'GET',
        public string $path = '/',
        public string $routeAction = 'view',
        public string $targetResourceName = 'top',
        public array $middleware = [],
    ) {
        $this->path = rtrim($this->path, '/');
        $this->validateRoute();
    }

    /**
     * 変な操作が指定されていないかチェック
     *
     * @return void
     */
    private function validateRoute(): void
    {
        $valid_actions = ['controller', 'view', 'api', 'redirect'];
        if (!in_array($this->routeAction, $valid_actions, true)) {
            throw new Exception("エラー: 不正なルートアクションが指定されています。{$this->routeAction}");
        }
    }

    /**
     * ルートが条件に沿っているかチェック
     */
    public function matches(string $request_method, string $request_uri): bool
    {
        $request_uri = rtrim($request_uri, '/');
        return (strtoupper($this->method) === $request_method && $this->path === $request_uri);
    }

    /**
     * ルートアクションの実行
     *
     * @return void
     */
    public function executeRouteAction(): void
    {
        // ミドルウェアの判定
        foreach ($this->middleware as $middleware) {
            if ($middleware->canOverrideRoute()) {
                $this->routeAction = $middleware->routeAction;
                $this->targetResourceName = $middleware->targetResourceName;
                break;
            }
        }

        switch ($this->routeAction) {
            case 'controller':
                $this->handleController($this->targetResourceName);
                break;
            case 'view':
                $this->handleView($this->targetResourceName);
                break;
            case 'api':
                $this->handleApi($this->targetResourceName);
                break;
            case 'redirect':
                $this->handleRedirect($this->targetResourceName);
                break;
            default:
                throw new Exception("エラー: 不正なコールバックタイプです。");
        }
    }

    /**
     * コントローラーを処理する
     * @param string $controller_path
     * @throws Exception
     */
    private function handleController(string $controller_path): void
    {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . rtrim(Config::getAppSetting('CONTROLLER_DIR'). '/') . "{$controller_path}.php";
        if (!file_exists($file_path)) {
            throw new Exception("エラー: コントローラーファイルが見つかりません。（{$file_path}）");
        }

        // ファイルロード
        $controller = require_once $file_path;

        // require_onceの戻り値がクラスインスタンスであることを期待
        if (!is_object($controller)) {
            throw new Exception("
                エラー: requireから {$controller_path} クラスのインスタンスが取得できません。
                コントローラーファイルは、ファイル末尾でインスタンスを返す必要があります。(return new クラス名;)
            ");
        }

        if (get_parent_class($controller) !== Controller::class) {
            throw new Exception("エラー: {$controller_path} が返すインスタンスがControllerクラスを継承していません。");
        }

        $controller->run();
    }

    /**
     * ビューを処理する
     * @param string $view_name
     * @throws Exception
     */
    private function handleView(string $view_name): void
    {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . rtrim(Config::getAppSetting('VIEW_DIR'), '/') . "{$view_name}.php";
        if (!file_exists($file_path)) {
            throw new Exception("エラー: ビューファイルが見つかりません。（{$file_path}）");
        }

        // ファイルの内容を表示
        http_response_code(200);
        Redirect::show($file_path);
    }

    /**
     * APIを処理する
     * @param string $api_path
     * @throws Exception
     */
    private function handleApi(string $api_path): void
    {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . rtrim(Config::getAppSetting('API_DIR'). '/') . "{$api_path}.php";
        if (!file_exists($file_path)) {
            throw new Exception("エラー: APIファイルが見つかりません。（{$file_path}）");
        }

        // ファイルロード
        $api = require_once $file_path;

        // require_onceの戻り値がクラスインスタンスであることを期待
        if (!is_object($api)) {
            throw new Exception("
                エラー: requireから {$file_path} クラスのインスタンスが取得できません。
                APIファイルは、ファイル末尾でインスタンスを返す必要があります。(return new クラス名;)
            ");
        }

        if (get_parent_class($api) !== Api::class) {
            throw new Exception("エラー: {$file_path} が返すインスタンスがApiクラスを継承していません。");
        }

        $api->run();
    }

    /**
     * リダイレクトを処理する
     * @param string $redirect_path
     * @throws Exception
     */
    private function handleRedirect(string $redirect_path): void
    {
        http_response_code(200);
        $top_dir = Config::getConfig("URL_BASE");
        Redirect::redirect($top_dir . $redirect_path);
    }

    /**
     * 404エラーを処理する
     */
    public static function showNotFoundPage(): void
    {
        $view_name = Config::getAppSetting('404_VIEW_FILE');
        $file_path = $_SERVER['DOCUMENT_ROOT'] . rtrim(Config::getAppSetting('VIEW_DIR'), '/') . "{$view_name}";
        if (!file_exists($file_path)) {
            throw new Exception("エラー: 404ビューファイルが見つかりません。（{$file_path}）");
        }

        // ファイルの内容を表示
        http_response_code(404);
        Redirect::show($file_path);
    }
}
