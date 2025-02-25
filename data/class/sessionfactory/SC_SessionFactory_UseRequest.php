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
 * Cookieを使用せず、リクエストパラメーターによりセッションを継続する設定を行うクラス.
 *
 * このクラスを直接インスタンス化しないこと.
 * 必ず SC_SessionFactory クラスを経由してインスタンス化する.
 * また, SC_SessionFactory クラスの関数を必ずオーバーライドしている必要がある.
 *
 * @author EC-CUBE CO.,LTD.
 *
 * @version $Id$
 *
 * @deprecated
 */
class SC_SessionFactory_UseRequest extends SC_SessionFactory_Ex
{
    public $state;

    /**
     * PC/モバイルのセッション管理オブジェクトを切り替える
     *
     * @param string $state
     */
    public function setState($state = 'pc')
    {
        switch ($state) {
            case 'mobile':
                $this->state = new LC_UseRequest_State_Mobile();
                break;

            case 'pc':
            default:
                $this->state = new LC_UseRequest_State_PC();
                break;
        }
    }

    /**
     * Cookieを使用するかどうか
     *
     * @return bool 常にfalseを返す
     */
    public function useCookie()
    {
        return false;
    }

    /**
     * dtb_mobile_ext_session_id テーブルを検索してセッションIDを取得する。
     * PCサイトでもモバイルサイトでもこのテーブルを利用する.
     *
     * @return string|null 取得したセッションIDを返す。
     *                     取得できなかった場合は null を返す。
     */
    public function getExtSessionId()
    {
        if (!preg_match('|^'.ROOT_URLPATH.'(.*)$|', $_SERVER['SCRIPT_NAME'], $matches)) {
            return null;
        }

        $url = $matches[1];
        $lifetime = $this->state->getLifeTime();
        $time = date('Y-m-d H:i:s', time() - $lifetime);
        $objQuery = SC_Query_Ex::getSingletonInstance();

        foreach ($_REQUEST as $key => $value) {
            $session_id = $objQuery->get(
                'session_id',
                'dtb_mobile_ext_session_id',
                'param_key = ? AND param_value = ? AND url = ? AND create_date >= ?',
                [$key, $value, $url, $time]
            );
            if (isset($session_id)) {
                return $session_id;
            }
        }

        return null;
    }

    /**
     * 外部サイト連携用にセッションIDとパラメーターの組み合わせを保存する。
     *
     * @param  string $param_key   パラメーター名
     * @param  string $param_value パラメーター値
     * @param  string $url         URL
     *
     * @return void
     */
    public function setExtSessionId($param_key, $param_value, $url)
    {
        $objQuery = SC_Query_Ex::getSingletonInstance();

        // GC
        $lifetime = $this->state->getLifeTime();
        $time = date('Y-m-d H:i:s', time() - $lifetime);
        $objQuery->delete('dtb_mobile_ext_session_id', 'create_date < ?', [$time]);

        $arrValues = [
            'session_id' => session_id(),
            'param_key' => $param_key,
            'param_value' => $param_value,
            'url' => $url,
        ];

        $objQuery->insert('dtb_mobile_ext_session_id', $arrValues);
    }

    /**
     * セッションデータが有効かどうかをチェックする。
     *
     * @return bool セッションデータが有効な場合は true、無効な場合は false を返す。
     */
    public function validateSession()
    {
        /*
         * PCサイトでは
         *  ・セッションデータが適切に設定されているか
         *  ・UserAgent
         *  ・IPアドレス
         *  ・有効期限
         * モバイルサイトでは
         *  ・セッションデータが適切に設定されているか
         *  ・機種名
         *  ・IPアドレス
         *  ・有効期限
         *  ・phone_id
         * がチェックされる
         */

        return $this->state->validateSessionData();
    }

    /**
     * パラメーターから有効なセッションIDを取得する。
     *
     * @return string|false 取得した有効なセッションIDを返す。
     *                      取得できなかった場合は false を返す。
     */
    public function getSessionId()
    {
        // パラメーターからセッションIDを取得する。
        $sessionId = @$_POST[session_name()];
        if (!isset($sessionId)) {
            $sessionId = @$_GET[session_name()];
            // AU動画音声ファイルダウンロード対策
            // キャリアがAUで、動画、音声ファイルをダウンロードする際に
            // SESSIONIDの後に余計なパラメータが付与され、セッションが無効になるケースがある
            if (SC_MobileUserAgent::getCarrier() == 'ezweb') {
                $idArray = explode('?', $sessionId);
                $sessionId = $idArray[0];
            }
        }
        if (!isset($sessionId)) {
            $sessionId = $this->getExtSessionId();
        }
        if (!isset($sessionId)) {
            return false;
        }

        // セッションIDの存在をチェックする。
        $objSession = new SC_Helper_Session_Ex();
        if ($objSession->sfSessRead($sessionId) === null) {
            GC_Utils_Ex::gfPrintLog('Non-existent session id : sid='.substr(sha1($sessionId), 0, 8));

            return false;
        }

        return session_id($sessionId);
    }

    /**
     * セッション初期処理を行う。
     *
     * @return void
     */
    public function initSession()
    {
        // セッションIDの受け渡しにクッキーを使用しない。
        if (!headers_sent()) {
            ini_set('session.use_cookies', '0');
            ini_set('session.use_trans_sid', '1');
            ini_set('session.use_only_cookies', '0');
        }

        // パラメーターから有効なセッションIDを取得する。
        $sessionId = $this->getSessionId();

        if (!$sessionId) {
            session_start();
        }

        /*
         * PHP4 では session.use_trans_sid が PHP_INI_PREDIR なので
         * ini_set() で設定できない
         */
        if (!ini_get('session.use_trans_sid')) {
            output_add_rewrite_var(session_name(), session_id());
        }

        // セッションIDまたはセッションデータが無効な場合は、セッションIDを再生成
        // し、セッションデータを初期化する。
        if ($sessionId === false || !$this->validateSession()) {
            session_regenerate_id(true);
            // セッションデータの初期化
            $this->state->inisializeSessionData();

            // 新しいセッションIDを付加してリダイレクトする。
            if ($_SERVER['REQUEST_METHOD'] == 'GET') {
                // GET の場合は同じページにリダイレクトする。
                $objMobile = new SC_Helper_Mobile_Ex();
                header('Location: '.$objMobile->gfAddSessionId());
            } else {
                // GET 以外の場合はトップページへリダイレクトする。
                header('Location: '.TOP_URL.'?'.SID);
            }
            exit;
        }

        // 有効期限を更新する.
        $this->state->updateExpire();
    }
}
/**
 * セッションデータ管理クラスの基底クラス
 *
 * @deprecated
 */
class LC_UseRequest_State
{
    /** 名前空間(pc/mobile) */
    public $namespace = '';
    /** 有効期間 */
    public $lifetime = 0;
    /** エラーチェック関数名の配列 */
    public $validate = [];

    /**
     * 名前空間を取得する
     *
     * @return string
     */
    public function getNameSpace()
    {
        return $this->namespace;
    }

    /**
     * 有効期間を取得する
     *
     * @return int
     */
    public function getLifeTime()
    {
        return $this->lifetime;
    }

    /**
     * セッションデータが設定されているかを判定する.
     * $_SESSION[$namespace]の値が配列の場合に
     * trueを返す.
     *
     * @return bool
     */
    public function validateNameSpace()
    {
        $namespace = $this->getNameSpace();
        if (isset($_SESSION[$namespace]) && is_array($_SESSION[$namespace])) {
            return true;
        }
        GC_Utils_Ex::gfPrintLog("NameSpace $namespace not found in session data : sid=".substr(sha1(session_id()), 0, 8));

        return false;
    }

    /**
     * セッションのデータを取得する
     * 取得するデータは$_SESSION[$namespace][$key]となる.
     *
     * @param  string     $key
     *
     * @return mixed|null
     */
    public function getValue($key)
    {
        $namespace = $this->getNameSpace();

        return $_SESSION[$namespace][$key] ?? null;
    }

    /**
     * セッションにデータを登録する.
     * $_SESSION[$namespace][$key] = $valueの形で登録される.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setValue($key, $value)
    {
        $namespace = $this->getNameSpace();
        $_SESSION[$namespace][$key] = $value;
    }

    /**
     * 有効期限を取得する.
     *
     * @return int
     */
    public function getExpire()
    {
        return $this->getValue('expires');
    }

    /**
     * 有効期限を設定する.
     */
    public function updateExpire()
    {
        $lifetime = $this->getLifeTime();
        $this->setValue('expires', time() + $lifetime);
    }

    /**
     * 有効期限内かどうかを判定する.
     *
     * @return bool
     */
    public function validateExpire()
    {
        $expire = $this->getExpire();
        if ((int) $expire > time()) {
            return true;
        }
        $date = date('Y/m/d H:i:s', $expire);
        GC_Utils_Ex::gfPrintLog("Session expired at $date : sid=".substr(sha1(session_id()), 0, 8));

        return false;
    }

    /**
     * IPアドレスを取得する.
     *
     * @return string
     */
    public function getIp()
    {
        return $this->getValue('ip');
    }

    /**
     * IPアドレスを設定する.
     */
    public function updateIp()
    {
        $this->setValue('ip', $_SERVER['REMOTE_ADDR']);
    }

    /**
     * REMOTE_ADDRとセッション中のIPが同じかどうかを判定する.
     * 同じ場合にtrueが返る
     *
     * @return bool
     */
    public function validateIp()
    {
        $ip = $this->getIp();
        if (!empty($_SERVER['REMOTE_ADDR']) && $ip === $_SERVER['REMOTE_ADDR']) {
            return true;
        }

        $msg = sprintf('Ip Addr mismatch : %s != %s(expected) : sid=%s', $_SERVER['REMOTE_ADDR'], $ip, substr(sha1(session_id()), 0, 8));
        GC_Utils_Ex::gfPrintLog($msg);

        return false;
    }

    /**
     * UserAgentもしくは携帯の機種名を取得する.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->getValue('model');
    }

    /**
     * セッション中のデータ検証する
     *
     * @return bool
     */
    public function validateSessionData()
    {
        foreach ($this->validate as $method) {
            $method = 'validate'.$method;
            if (!$this->$method()) {
                return false;
            }
        }

        return true;
    }

    /**
     * セッションデータを初期化する.
     */
    public function inisializeSessionData()
    {
    }
}

/**
 * PCサイト用のセッションデータ管理クラス
 *
 * @deprecated
 */
class LC_UseRequest_State_PC extends LC_UseRequest_State
{
    /**
     * コンストラクタ
     * セッションのデータ構造は下のようになる.
     * $_SESSION['pc']=> array(
     *     ['model']   => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)'
     *     ['ip']      => '127.0.0.1'
     *     ['expires'] => 1204699031
     * )
     */
    public function __construct()
    {
        $this->namespace = 'pc';
        $this->lifetime = SESSION_LIFETIME;
        $this->validate = ['NameSpace', 'Model', 'Ip', 'Expire'];
    }

    /**
     * セッションにUserAgentを設定する.
     */
    public function updateModel()
    {
        $this->setValue('model', $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * UserAgentを検証する.
     *
     * @return bool
     */
    public function validateModel()
    {
        $ua = $this->getModel();
        if (!empty($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] === $ua) {
            return true;
        }
        $msg = sprintf(
            'User agent model mismatch : %s != %s(expected), sid=%s',
            $_SERVER['HTTP_USER_AGENT'],
            $ua,
            substr(sha1(session_id()), 0, 8)
        );
        GC_Utils_Ex::gfPrintLog($msg);

        return false;
    }

    /**
     * セッションデータを初期化する.
     */
    public function inisializeSessionData()
    {
        $_SESSION = [];
        $this->updateModel();
        $this->updateIp();
        $this->updateExpire();
    }
}

/**
 * モバイルサイト用のセッションデータ管理クラス
 *
 * @deprecated
 */
class LC_UseRequest_State_Mobile extends LC_UseRequest_State
{
    /**
     * コンストラクタ
     * セッションのデータ構造は下のようになる.
     * $_SESSION['mobile']=> array(
     *     ['model']   => 901sh
     *     ['ip']      => 127.0.0.1
     *     ['expires'] => 1204699031
     *     ['phone_id']=> ****
     * )
     */
    public function __construct()
    {
        $this->namespace = 'mobile';
        $this->lifetime = MOBILE_SESSION_LIFETIME;
        $this->validate = ['NameSpace', 'Model', 'Expire'];
    }

    /**
     * 携帯の機種名を設定する
     */
    public function updateModel()
    {
        $this->setValue('model', SC_MobileUserAgent_Ex::getModel());
    }

    /**
     * セッション中の携帯機種名と、アクセスしてきたブラウザの機種名が同じかどうかを判定する
     *
     * @return bool
     */
    public function validateModel()
    {
        $modelInSession = $this->getModel();
        $model = SC_MobileUserAgent_Ex::getModel();
        if (!empty($model) && $model === $modelInSession) {
            return true;
        }

        return false;
    }

    /**
     * 携帯のIDを取得する
     *
     * @return string
     */
    public function getPhoneId()
    {
        return $this->getValue('phone_id');
    }

    /**
     * 携帯のIDを登録する.
     */
    public function updatePhoneId()
    {
        $this->setValue('phone_id', SC_MobileUserAgent_Ex::getId());
    }

    /**
     * セッションデータを初期化する.
     */
    public function inisializeSessionData()
    {
        $_SESSION = [];
        $this->updateModel();
        $this->updateIp();
        $this->updateExpire();
        $this->updatePhoneId();
    }
}
/*
 * Local variables:
 * coding: utf-8
 * End:
 */
