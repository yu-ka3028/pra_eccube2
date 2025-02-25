<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * 店舗基本情報 のページクラス.
 *
 * @author EC-CUBE CO.,LTD.
 *
 * @version $Id$
 */
class LC_Page_Admin_System_AdminArea extends LC_Page_Admin_Ex
{
    /** @var bool */
    public $tpl_enable_ssl;

    /**
     * Page を初期化する.
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->tpl_mainpage = 'system/adminarea.tpl';
        $this->tpl_subno = 'adminarea';
        $this->tpl_mainno = 'system';
        $this->tpl_maintitle = 'システム設定';
        $this->tpl_subtitle = '管理画面設定';
        $this->tpl_enable_ssl = false;
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    public function process()
    {
        $this->action();
        $this->sendResponse();
    }

    /**
     * Page のアクション.
     *
     * @return void
     */
    public function action()
    {
        if (str_contains(HTTPS_URL, 'https://')) {
            $this->tpl_enable_ssl = true;
        }

        $objFormParam = new SC_FormParam_Ex();

        // パラメーターの初期化
        $this->initParam($objFormParam, $_POST);

        if (count($_POST) > 0) {
            // エラーチェック
            $arrErr = $objFormParam->checkError();

            $this->arrForm = $objFormParam->getHashArray();

            // 設定ファイルの権限チェック
            if (!is_writable(CONFIG_REALFILE)) {
                $arrErr['all'] = CONFIG_REALFILE.' を変更する権限がありません。';
            }

            // 管理画面ディレクトリのチェック
            $this->lfCheckAdminArea($this->arrForm, $arrErr);

            if (SC_Utils_Ex::isBlank($arrErr) && $this->lfUpdateAdminData($this->arrForm)) {
                $this->tpl_onload = "window.alert('管理機能の設定を変更しました。URLを変更した場合は、新しいURLにアクセスしてください。');";
            } else {
                $this->tpl_onload = "window.alert('設定内容に誤りがあります。設定内容を確認してください。');";
                $this->arrErr = array_merge($arrErr, $this->arrErr);
            }
        } else {
            $admin_dir = str_replace('/', '', ADMIN_DIR);
            $this->arrForm = ['admin_dir' => $admin_dir, 'admin_force_ssl' => ADMIN_FORCE_SSL, 'admin_allow_hosts' => ''];
            if (defined('ADMIN_ALLOW_HOSTS')) {
                $allow_hosts = unserialize(ADMIN_ALLOW_HOSTS);
                $this->arrForm['admin_allow_hosts'] = implode("\n", $allow_hosts);
            }
        }
    }

    /**
     * パラメーター初期化.
     *
     * @param  SC_FormParam_Ex $objFormParam
     * @param  array  $arrParams    $_POST値
     *
     * @return void
     */
    public function initParam(&$objFormParam, &$arrParams)
    {
        $objFormParam->addParam('ディレクトリ名', 'admin_dir', ID_MAX_LEN, 'a', ['EXIST_CHECK', 'SPTAB_CHECK', 'ALNUM_CHECK']);
        $objFormParam->addParam('SSL制限', 'admin_force_ssl', 1, 'n', ['NUM_CHECK', 'MAX_LENGTH_CHECK']);
        $objFormParam->addParam('IP制限', 'admin_allow_hosts', LTEXT_LEN, 'a', ['IP_CHECK', 'MAX_LENGTH_CHECK']);
        $objFormParam->setParam($arrParams);
        $objFormParam->convParam();
    }

    /**
     * 管理機能ディレクトリのチェック.
     *
     * @param  array $arrForm $this->arrForm値
     * @param  array $arrErr  エラーがあった項目用配列
     *
     * @return void
     */
    public function lfCheckAdminArea(&$arrForm, &$arrErr)
    {
        $admin_dir = trim($arrForm['admin_dir']).'/';

        $installData = file(CONFIG_REALFILE, FILE_IGNORE_NEW_LINES);
        foreach ($installData as $key => $line) {
            if (str_contains($line, 'ADMIN_DIR') && ADMIN_DIR != $admin_dir) {
                if ($admin_dir == 'admin/') {
                    $arrErr['admin_dir'] .= '別のディレクトリ名を指定してください。';
                }
                // 既存ディレクトリのチェック
                if (file_exists(HTML_REALDIR.$admin_dir) && $admin_dir != 'admin/') {
                    $arrErr['admin_dir'] .= ROOT_URLPATH.$admin_dir.'は既に存在しています。別のディレクトリ名を指定してください。';
                }
                // 権限チェック
                if (!is_writable(HTML_REALDIR.ADMIN_DIR)) {
                    $arrErr['admin_dir'] .= ROOT_URLPATH.ADMIN_DIR.'のディレクトリ名を変更する権限がありません。';
                }
            }
        }
    }

    // 管理機能ディレクトリのリネームと CONFIG_REALFILE の変更
    public function lfUpdateAdminData(&$arrForm)
    {
        $admin_dir = trim($arrForm['admin_dir']).'/';
        $admin_force_ssl = 'false';
        if ($arrForm['admin_force_ssl'] == 1) {
            $admin_force_ssl = 'true';
        }
        $admin_allow_hosts = explode("\n", $arrForm['admin_allow_hosts']);
        foreach ($admin_allow_hosts as $key => $host) {
            $host = trim($host);
            if (strlen($host) >= 8) {
                $admin_allow_hosts[$key] = $host;
            } else {
                unset($admin_allow_hosts[$key]);
            }
        }
        $admin_allow_hosts = serialize($admin_allow_hosts);

        // CONFIG_REALFILE の書き換え
        $installData = file(CONFIG_REALFILE, FILE_IGNORE_NEW_LINES);
        $diff = 0;
        foreach ($installData as $key => $line) {
            if (str_contains($line, 'ADMIN_DIR') && ADMIN_DIR != $admin_dir) {
                $installData[$key] = 'define("ADMIN_DIR", "'.$admin_dir.'");';
                // 管理機能ディレクトリのリネーム
                if (!rename(HTML_REALDIR.ADMIN_DIR, HTML_REALDIR.$admin_dir)) {
                    $this->arrErr['admin_dir'] .= ROOT_URLPATH.ADMIN_DIR.'のディレクトリ名を変更できませんでした。';

                    return false;
                }
                $diff++;
            }

            if (str_contains($line, 'ADMIN_FORCE_SSL')) {
                $installData[$key] = 'define("ADMIN_FORCE_SSL", '.$admin_force_ssl.');';
                $diff++;
            }
            if (str_contains($line, 'ADMIN_ALLOW_HOSTS') && ADMIN_ALLOW_HOSTS != $admin_allow_hosts) {
                $installData[$key] = "define('ADMIN_ALLOW_HOSTS', '".$admin_allow_hosts."');";
                $diff++;
            }
        }

        if ($diff > 0) {
            $fp = fopen(CONFIG_REALFILE, 'w');
            $installData = implode("\n", $installData);
            fwrite($fp, $installData);
            fclose($fp);
        }

        return true;
    }
}
