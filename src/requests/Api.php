<?php
namespace ayutenn\core\requests;

use Exception;
use ayutenn\core\utils\Redirect;
use ayutenn\core\requests\RequestValidator;

/**
 * 【概要】
 * APIの抽象クラス
 *
 * 【解説】
 * APIの抽象クラスです。
 *
 * 【無駄口】
 * とくにいうことなし
 *
 */
abstract class Api
{
    // リクエストパラメタに期待するフォーマット
    // 書式はRequestsValidatorのコメントを参照
    protected array $RequestParameterFormat = [];

    // 型変換されたリクエストパラメータ
    protected array $parameter = [];

    // メイン処理
    abstract public function main(): array;

    // API実行
    public function run(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $request_parameters = $_GET;
        } else {
            // TODO PUTとかに対応が必要ないか調べる
            $request_parameters = $_POST;
        }

        // パラメータのバリデート
        try {
            $validator = new RequestValidator($this->RequestParameterFormat, $request_parameters);
            $validate_errors = $validator->validate();
        } catch (Exception $e) {
            $response = $this->createResponse(
                succeed: false,
                payload: [
                    'message' => 'バリデートに関するサーバーエラーが発生しました。',
                    'errors' => [$e->getMessage()],
                ]
            );
            Redirect::apiResponse($response);
            return;
        }

        // バリデートエラーがあった場合、エラーメッセージを返す
        if (count($validate_errors) > 0) {
            $response = $this->createResponse(
                succeed: false,
                payload: [
                    'message' => 'リクエストパラメータにエラーがあります。',
                    'errors' => $validate_errors,
                ]
            );
            Redirect::apiResponse($response);
            return;
        }

        $this->parameter = $validator->cleanRequestParameter;

        Redirect::apiResponse($this->main());
    }

    /**
     * JSONレスポンスを返す
     *
     * @param bool $succeed 成功したかどうか
     * @param array $payload レスポンスデータ
     * @return array 通常時はここでexit テスト時のみレスポンスの連想配列を返す
     */
    protected function createResponse(bool $succeed, array $payload = []): array
    {
        return [
            'status' => $succeed ? 0 : 9,
            'payload' => $payload
        ];
    }
}
