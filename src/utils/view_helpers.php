<?php
namespace ayutenn\core\utils;

use ayutenn\core\config\Config;

/**
 * 【概要】
 * ビュー関連のヘルパー関数群
 *
 * 【解説】
 * ビューでよく使う関数をまとめたもの
 *
 * 【無駄口】
 * 特になし
 *
 */

/**
 * HTMLエスケープを行う
 * htmlspecialcharsのラッパー
 *
 * @param string $str エスケープ対象の文字列
 * @return string エスケープ後の文字列
 */
function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * HTMLエスケープを行うと同時に改行を<br>に変換する
 * h()とnl2br()のラッパー
 *
 * @param string $str
 * @return string
 */
function hbr(string $str): string
{
    return nl2br(h($str));
}

/**
 * アセットのURLを生成する
 * index.phpと同ディレクトリにアセット置いてるから必要なさそう てことでコメントアウト中
 * 復活させる時はPUBLIC_PATHの設定を忘れずに
 *
 * 使用例：
 * <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
 * <img src="<?= asset('img/logo.png') ?>" alt="Logo">
 *
 * @param string $asset_relative_path アセットの相対パス（例: "css/style.css"）
 * @return string アセットのフルURL
 */
/*
function asset(string $asset_relative_path): string
{
    $base_url = rtrim(Config::getAppSetting('PUBLIC_PATH'), '/');
    return "{$base_url}/{$asset_relative_path}";
}
*/

/**
 * 指定されたパスのフルURLを生成する
 *
 * 使用例：
 * <a href="<?= url('user/profile') ?>">プロフィールへ</a>
 *
 * @param string $path パス（例: "about/us"）
 * @return string フルURL
 */
function url(string $path): string
{
    $base_url = rtrim(Config::getAppSetting('PATH_ROOT'), '/');
    return "{$base_url}/{$path}";
}