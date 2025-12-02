<?php
namespace ayutenn\core\requests;

use ayutenn\core\requests\Model;

/**
 * 【概要】
 * リクエストパラメタの束のバリデーションとキャストを行うクラス
 * Modelを扱う親玉
 *
 * 【解説】
 * リクエストパラメタのバリデーションを行う。
 *
 * 例えば「$_POSTには16文字以内のユーザーID（英数字のみ）と、9999以下の数値（整数のみ）が必須です」と書かれたパラメタファイルをこいつに渡すと、
 * こいつはまずその条件を満たしているかをチェックした上で、指定した型通りのキャストしたデータを返してくれる。
 * つまり、$_POSTや$_GETから、バリデート + 型変換を終えた"きれい"な値を取得してくれる。
 *
 * 各パラメタの定義はjsonファイル。スプレッドシートなどから生成することを想定している。
 *
 *【無駄口】
 * じっそうしてるとこんがらがってあたまおかしくなりそう
 * 作ってる途中で、json schemaを自分で作ろうとしてる事に気づいた
 * いいじゃんね 車輪なんて何回発明しても
 *
 */
class RequestValidator
{
    public array $cleanRequestParameter = [];

    /**
     * コンストラクタ
     *
     * @param array $requestParameterFormat リクエストパラメタのフォーマット
     * @param array $requestParameter リクエストパラメタ
     */
    public function __construct(
        private array $requestParameterFormat = [],
        private array $requestParameter = []
    ) {}

    /**
     * バリデーション実行と同時にキャストも行う
     *
     * @return array エラーメッセージの配列
     */
    public function validate(): array
    {
        // クリーンパラメータをリセット
        $this->cleanRequestParameter = [];

        // バリデーションとキャストを同時に実行
        $errors = $this->validateAndCastParameters($this->requestParameterFormat, $this->requestParameter);

        // エラーがあった場合はクリーンパラメータをクリア
        if (count($errors) > 0) {
            $this->cleanRequestParameter = [];
        }

        return $errors;
    }

    /**
     * パラメータのバリデーションとキャスト処理を同時に行う
     *
     * @param array $formats フォーマット定義
     * @param array $values バリデーション対象の値
     * @param string $parentPath 親パスの名前（エラーメッセージ用）
     * @return array エラーメッセージの配列
     */
    private function validateAndCastParameters(array $formats, array $values, string $parentPath = ''): array
    {
        $errors = [];
        $tempCleanParams = [];

        foreach ($formats as $format_name => $format) {
            $currentPath = $parentPath ? "$parentPath.$format_name" : $format_name;
            $isValueExists = isset($values[$format_name]);

            if (!$isValueExists) {
                // 値がパラメタとして渡されてきていない
                if (($format['require'] ?? true) == true) {
                    // 必須項目ならエラー
                    $errors[] = "リクエストに必要な値が設定されていません。({$currentPath})";
                    continue;

                } else {
                    // 必須項目ではないならスルー
                    continue;
                }
            }

            $value = $values[$format_name];
            $itemErrors = [];

            switch ($format['type'] ?? 'item') {
                case 'list':
                    [$castedValue, $itemErrors] = $this->validateAndCastList($format['items'], $value, $currentPath);
                    break;

                case 'object':
                    [$castedValue, $itemErrors] = $this->validateAndCastObject($format, $value, $currentPath);
                    break;

                case 'item':
                    [$castedValue, $itemErrors] = $this->validateAndCastItem($format, $value, $currentPath);
                    break;
            }

            $errors = array_merge($errors, $itemErrors);

            // エラーがなければ一時的なクリーンパラメータに追加
            if (empty($itemErrors)) {
                $tempCleanParams[$format_name] = $castedValue;
            }
        }

        // エラーがなければクリーンパラメータにマージ
        if (empty($errors) && !empty($tempCleanParams)) {
            $this->setCleanParametersByPath($parentPath, $tempCleanParams);
        }

        return $errors;
    }

    /**
     * リスト型のバリデーションとキャスト
     *
     * @param array $format フォーマット定義
     * @param mixed $value バリデーション対象の値
     * @param string $path 現在のパス
     * @return array [キャストされた値, エラーメッセージの配列]
     */
    private function validateAndCastList(array $format, $value, string $path): array
    {
        $castedList = [];
        $errors = [];

        if (!is_array($value)) {
            $errors[] = "{$path} はリスト形式である必要があります。";
            return [null, $errors];
        }

        $itemsFormat = $format;
        $itemType = $itemsFormat['type'] ?? 'item';

        foreach ($value as $index => $item) {
            $itemPath = "{$path}[{$index}]";

            switch ($itemType) {
                case 'item':
                    // リストの中身が単純なアイテム
                    $validator = new Model($itemsFormat['format'] ?? null);
                    $error = $validator->validate($item, $itemsFormat['require'] ?? true);

                    if ($error) {
                        $errors[] = "{$itemsFormat['name']}は、{$error}";
                    } else {
                        $castedList[] = $validator->cast($item);
                    }
                    break;

                case 'object':
                    // リストの中身がオブジェクト
                    [$castedItem, $itemErrors] = $this->validateAndCastObject(
                        $itemsFormat,
                        $item,
                        $itemPath
                    );

                    if (empty($itemErrors)) {
                        $castedList[] = $castedItem;
                    } else {
                        $errors = array_merge($errors, $itemErrors);
                    }
                    break;

                case 'list':
                    // リストの中身がリストなんてことないよね？
                    break;
            }
        }

        return [$castedList, $errors];
    }

    /**
     * オブジェクト型のバリデーションとキャスト
     *
     * @param array $format フォーマット定義
     * @param mixed $value バリデーション対象の値
     * @param string $path 現在のパス
     * @return array [キャストされた値, エラーメッセージの配列]
     */
    private function validateAndCastObject(array $format, $value, string $path): array
    {
        $castedObject = [];
        $errors = [];

        if (!is_array($value)) {
            $errors[] = "{$path} はオブジェクト形式である必要があります。";
            return [null, $errors];
        }

        // プロパティが存在しない場合は空の配列として扱う
        $properties = $format['properties'] ?? [];

        // 各プロパティに対してバリデーションを実行
        foreach ($properties as $propName => $propFormat) {
            $propPath = $path ? "{$path}.{$propName}" : $propName;
            $propExists = isset($value[$propName]);

            if (!$propExists && ($propFormat['require'] ?? true)) {
                $errors[] = "リクエストに必要な値が設定されていません。({$propPath})";
                continue;
            }

            if (!$propExists) {
                continue;
            }

            $propValue = $value[$propName];
            $propErrors = [];
            $castedValue = null;

            switch ($propFormat['type'] ?? 'item') {
                case 'list':
                    [$castedValue, $propErrors] = $this->validateAndCastList(
                        $propFormat['items'],
                        $propValue,
                        $propPath
                    );
                    break;

                case 'object':
                    [$castedValue, $propErrors] = $this->validateAndCastObject(
                        $propFormat,
                        $propValue,
                        $propPath
                    );
                    break;

                case 'item':
                    [$castedValue, $propErrors] = $this->validateAndCastItem(
                        $propFormat,
                        $propValue,
                        $propPath
                    );
                    break;
            }

            $errors = array_merge($errors, $propErrors);

            // エラーがなければオブジェクトに追加
            if (empty($propErrors)) {
                $castedObject[$propName] = $castedValue;
            }
        }

        return [$castedObject, $errors];
    }

    /**
     * アイテム型のバリデーションとキャスト
     *
     * @param array $format フォーマット定義
     * @param mixed $value バリデーション対象の値
     * @param string $path 現在のパス
     * @return array [キャストされた値, エラーメッセージの配列]
     */
    private function validateAndCastItem(array $format, $value, string $path): array
    {
        $errors = [];

        $validator = new Model($format['format'] ?? null);
        $error = $validator->validate($value, $format['require'] ?? true);

        if ($error) {
            $errors[] = "{$format['name']}は、{$error}";
            return [null, $errors];
        }

        // バリデーションが成功したらキャスト
        $castedValue = $validator->cast($value);
        return [$castedValue, $errors];
    }

    /**
     * キャスト済みのリクエストパラメータを返す
     * validate()が実行済みでエラーがない場合のみ有効な値を返す
     *
     * @return array 型変換済みのリクエストパラメータ
     */
    public function getCastedParameter(): array
    {
        return $this->cleanRequestParameter;
    }

    /**
     * パスに応じてクリーンパラメータに値を設定する
     *
     * @param string $path ドット区切りのパス
     * @param array $values 設定する値の配列
     */
    private function setCleanParametersByPath(string $path, array $values): void
    {
        if (empty($path)) {
            // ルートレベルの場合は直接マージ
            $this->cleanRequestParameter = array_merge($this->cleanRequestParameter, $values);
            return;
        }

        // パスを分解してネストした位置に値を設定
        $pathParts = explode('.', $path);
        $current = &$this->cleanRequestParameter;

        foreach ($pathParts as $part) {
            // 配列要素の参照 (例: friends[0])
            if (preg_match('/^(.*)\[(\d+)\]$/', $part, $matches)) {
                $arrayName = $matches[1];
                $index = (int)$matches[2];

                if (!isset($current[$arrayName])) {
                    $current[$arrayName] = [];
                }

                if (!isset($current[$arrayName][$index])) {
                    $current[$arrayName][$index] = [];
                }

                $current = &$current[$arrayName][$index];
            } else {
                // 通常のオブジェクトプロパティ
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }

        // 参照先に値をマージ
        $current = array_merge($current, $values);
    }
}