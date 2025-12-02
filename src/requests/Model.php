<?php
namespace ayutenn\core\requests;

require_once __DIR__ . '/../../vendor/autoload.php';
use ayutenn\core\config\Config;

/**
 * 【概要】
 * リクエストパラメタ単体のバリデーションを行うモデルクラス
 * RequestValidatorの子分
 *
 * 【解説】
 * ユーザーから渡された未検証データ1つと、
 * そのデータがどのような型であるべきかを定義したjsonファイルを使って、
 * 検証と変換を行うクラス。
 *
 * 問題がなければ空文字を、問題があればエラーメッセージを返す。
 * また、求められれば型変換も行う。
 * $_POSTや$_GETから受け取る文字列を安全に扱うのがねらい。
 *
 * 【無駄口】
 * PHPの型変換はミスりやすいので、このファイルは度々直すことになると思う
 * あとエラーメッセージをここにハードコーディングしちゃってるんだけど、
 * これってどうなんだろうね？外出しにしたほうがいいか？いやめんどくせえな……
 * 機動力も下がりそうだし……
 *
 */
class Model
{
    /**
     * jsonファイルから取得した、リクエストパラメタの値に求める型や最大長などのフォーマット
     *
     * @var array
     */
    private array $format = [];

    /**
     * コンストラクタ
     * フォーマットのjsonファイルを読み込み、$this->formatに格納する
     *
     * @param string $format_name ファイル名
     */
    public function __construct(
        string $format_file_name
    ) {
        $format_file_path = $_SERVER['DOCUMENT_ROOT'] . rtrim(Config::getAppSetting('MODEL_PATH'). '/') . "{$format_file_name}.json";
        if (file_exists($format_file_path)) {
            $json = file_get_contents($format_file_path);
            $this->format = json_decode($json, true);
        } else {
            throw new \Exception("modelファイルが見つかりませんでした。: {$format_file_path}");
        }
    }

    /**
     * validate
     * $this->formatに格納されたフォーマットに従って、リクエストパラメタの値を検証する
     * 問題がなければ空文字を、問題があればエラーメッセージを返す
     * $requireがtrueの場合、フォーマットに従っていなくても値がemptyな場合は空文字を返す
     *
     * @param mixed $value
     * @param bool $require
     * @return string
     */
    public function validate($value, bool $require): string
    {
        if (!$require) {
            if (empty($value)) {
                // 必須項目でない + 空欄ならvalidate ok
                return "";
            }
        }

        // 型チェック
        if (isset($this->format['type']) && !$this->validateType($value, $this->format['type'])) {
            return "データの形式が不正です。";
        }

        // 条件チェック
        if (isset($this->format['condition']) && is_array($this->format['condition'])) {
            foreach ($this->format['condition'] as $condition) {
                return $this->validateCondition($value, $condition);
            }
        }

        // 最小値・最大値チェック（数値の場合）
        if (isset($this->format['type']) && ($this->format['type'] === 'int' || $this->format['type'] === 'number')) {
            // 最小値
            if (isset($this->format['min']) && $value < $this->format['min']) {
                return "{$this->format['min']}以上である必要があります。（現在: {$value}）";
            }

            // 最大値
            if (isset($this->format['max']) && $value > $this->format['max']) {
                return "{$this->format['max']}以下である必要があります。（現在: {$value}）";
            }
        }

        // 最小長・最大長チェック（文字列の場合）
        if (isset($this->format['type']) && $this->format['type'] === 'string') {
            $value_normalized = str_replace("\r\n", "\n", $value);
            $length = mb_strlen($value_normalized);
            $line_count = substr_count($value_normalized, "\n") + 1;

            // 最小長
            if (isset($this->format['min_length']) && $length < $this->format['min_length']) {
                return "{$this->format['min_length']}文字以上である必要があります。(現在: {$length}文字)";
            }

            // 最大長
            if (isset($this->format['max_length']) && $length > $this->format['max_length']) {
                return "{$this->format['max_length']}文字以下である必要があります。(現在: {$length}文字)";
            }

            // 最小行
            if (isset($this->format['min_line']) && $line_count < $this->format['min_line']) {
                return "{$this->format['min_line']}行以上である必要があります。(現在: {$line_count}行)";
            }

            // 最大行
            if (isset($this->format['max_line']) && $line_count > $this->format['max_line']) {
                return "{$this->format['max_line']}行以下である必要があります。(現在: {$line_count}行)";
            }
        }

        return ""; // バリデーション成功
    }

    /**
     * 値の型を検証する
     *
     * @param mixed $value 検証する値
     * @param string $type 期待する型
     * @return bool 検証結果
     */
    private function validateType($value, string $type): bool
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'number':
                return is_numeric($value);
            case 'int':
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
            case 'boolean':
                return in_array($value, [true, false, 0, 1, '0', '1'], true);
            case 'array':
                return is_array($value);
            default:
                return true; // 未知の型は検証しない
        }
    }

    /**
     * 条件に基づいて値を検証する
     *
     * @param mixed $value 検証する値
     * @param string $condition 条件
     * @return string 検証結果
     */
    private function validateCondition($value, string $condition): string
    {
        switch ($condition) {
            case 'numeric':
                if (!is_numeric($value)) {
                    return "数値である必要があります。";
                }
                break;

            case 'int':
                if (!filter_var($value, FILTER_VALIDATE_INT) !== false) {
                    return "数値である必要があります。";
                }
                break;

            case 'boolean':
                if (!in_array($value, [0, 1, '0', '1'], true)) {
                    return "フラグの形式である必要があります。";
                }
                break;

            case 'email':
                if (!preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', $value)) {
                    return "メールアドレスの形式である必要があります。";
                }
                break;

            case 'url':
                if (!preg_match('/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/', $value)) {
                    return "URLの形式である必要があります。";
                }
                break;

            case 'alphabets':
                if (preg_match('/^[a-zA-Z]+$/', $value) !== 1) {
                    return "英字のみである必要があります。";
                }
                break;

            case 'alphanumeric':
                if (preg_match('/^[a-zA-Z0-9]+$/', $value) !== 1) {
                    return "英数字のみである必要があります。";
                }
                break;

            case 'symbols':
                if (preg_match('/^[a-zA-Z0-9\p{P}\p{S}]+$/u', $value) !== 1) {
                    return "英数字+記号のみである必要があります。";
                }
                break;

            case 'datetime':
                $format = 'Y/m/d H:i:s';
                $dateTime = \DateTime::createFromFormat($format, $value);

                // パース失敗 または 形式が一致しない場合
                if ($dateTime === false || $dateTime->format($format) !== $value) {
                    return "日付+時刻の形式である必要があります。";
                }

                break;

            case 'color_code':
                if (preg_match('/^#[a-fA-F0-9]{6}$/', $value) !== 1) {
                    return "カラーコードの形式である必要があります。";
                }
                break;

            case 'local_file':
                // ローカルファイルのパス形式をチェック
                // ここでは基本的な形式チェックのみ実施
                if (preg_match('/^[a-zA-Z0-9\/_\-\.]+$/', $value) !== 1) {
                    return "ファイルパスの形式である必要があります。";
                }
                break;

            default:
                return ""; // 未知の条件は検証しない
        }
        return "";
    }

    /**
     * キャスト
     * 与えられた値を、フォーマットに従って変換する
     *
     * @return mixed 変換した値
     */
    public function cast($value): mixed
    {
        if (isset($this->format['type'])) {
            switch ($this->format['type']) {
                case 'int':
                    return (int)$value;
                case 'number':
                    return (float)$value;
                case 'string':
                    return (string)$value;
                case 'boolean':
                    return (bool)$value;
                case 'array':
                    return (array)$value;
                default:
                    return $value;
            }
        }

        // 型が指定されていない場合はそのまま返す
        return $value;
    }

    public function getFormLabel(): string
    {
        $labels = [];
        if (isset($this->format['type'])) {
            switch ($this->format['type']) {
                case 'int':
                    $labels[] = '';
                    if (isset($this->format['min']) && isset($this->format['max'])) {
                        $labels[] = "{$this->format['min']}～{$this->format['max']}の数値";
                    } elseif (isset($this->format['max'])) {
                        $labels[] = "{$this->format['max']}の数値";
                    } elseif (isset($this->format['min'])) {
                        $labels[] = "{$this->format['min']}の数値";
                    } else {
                        $labels[] = '';
                    }
                    break;

                case 'number':
                    $labels[] = '';
                    if (isset($this->format['min']) && isset($this->format['max'])) {
                        $labels[] = "{$this->format['min']}～{$this->format['max']}の数値";
                    } elseif (isset($this->format['max'])) {
                        $labels[] = "{$this->format['max']}の数値";
                    } elseif (isset($this->format['min'])) {
                        $labels[] = "{$this->format['min']}の数値";
                    }
                    break;

                case 'string':
                    if (isset($this->format['min_length']) && isset($this->format['max_length'])) {
                        $labels[] = "{$this->format['min_length']}～{$this->format['max_length']}文字";
                    } elseif (isset($this->format['max_length'])) {
                        $labels[] = "{$this->format['max_length']}文字";
                    } elseif (isset($this->format['min_length'])) {
                        $labels[] = "{$this->format['min_length']}文字";
                    } else {
                        $labels[] = '';
                    }

                    if (isset($this->format['min_line']) && isset($this->format['max_line'])) {
                        $labels[] = "{$this->format['min_line']}～{$this->format['max_line']}行";
                    } elseif (isset($this->format['max_line'])) {
                        $labels[] = "{$this->format['max_line']}行以下";
                    } elseif (isset($this->format['min_line'])) {
                        $labels[] = "{$this->format['min_line']}行以上";
                    }
                    break;
            }
        }

        if (isset($this->format['condition'])) {
            foreach ($this->format['condition'] as $condition) {
                switch ($condition) {
                    case 'email':
                        $labels[] = 'メールアドレス';
                        break;
                    case 'url':
                        $labels[] = 'URL形式';
                        break;
                    case 'alpha':
                        $labels[] = '英字のみ';
                        break;
                    case 'alphaNum':
                        $labels[] = '英数字のみ';
                        break;
                    case 'symbols':
                        $labels[] = '英数記号のみ';
                        break;
                    case 'datetime':
                        $labels[] = '日付形式';
                        break;
                    case 'color_code':
                        $labels[] = 'カラーコード形式';
                        break;
                    case 'local_file':
                        $labels[] = 'ファイルパス形式';
                        break;
                }
            }
        }

        $label = implode(' ', $labels);
        return $label;
    }
}
