<?php


namespace App\Helpers;


use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Request;

class SsoSimakHelper
{
    const USER_URL = 'https://simak.unsil.ac.id/a/user?token=';
    const REFRESH_URL = 'https://simak.unsil.ac.id/a/refresh?token=';
    const LOGIN_URL = 'https://simak.unsil.ac.id/a/sso?app_id=%s&client_id=%s&last_url=%s';

    public static function loginUrlSimak($last_url='') {
        return sprintf(
            self::LOGIN_URL,
            env('SIMAK_APP_ID'),
            env('SIMAK_CLIENT_ID'),
            $last_url
        );
    }

    protected $exception;

    /**
     * @return \Exception
     */
    public function getException() {
        return $this->exception;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed
     */
    public function getSession()
    {
        return session();
    }

    /**
     * @return array|false|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    public static function loginFromToken()
    {
        $login = self::getInstance()->hasLogin();
        if ($login) return self::getInstance()->getUser();
        $token = Request::input('token');
        if (!$token) return false;
        return self::getInstance()->loginToken($token);
    }

    /**
     * @return SsoSimakHelper
     */
    public static function getInstance()
    {
        static $instance;
        if (!$instance) $instance = new self();
        return $instance;
    }

    public function hasLogin() {
        $now = time();
        static $token;
        static $expired;
        static $user;
        if (
            !$token
            || !$expired
            || !$user
        ) {
            $token = $this->getSession()->get('token');
            $expired = $this->getSession()->get('expired');
            $user = $this->getSession()->get('user');
            if ($token) $this->loginToken($token);
        }
        if (
            !$token
            || !$expired
            || $now > $expired
            || !$user
        ) return false;

        return true;

    }

    public function getUser() {
        return $this->getSession()->get('user');
    }

    public function loginToken($token) {
        try {
            //$this->getSession()->flush();

            $publicKey = $this->loadPublicKey();

            $try_token = 0;
            do {
                $data = (array) JWT::decode($token, $publicKey, array('RS256'));
                if (
                    $data
                    && ($expired = $data['exp'] )
                    && $expired<time()
                ) {
                    $token = $this->refreshUser($token);
                    $try_token = 1;
                }
            } while($try_token);

            $data = (array) JWT::decode($token, $publicKey, array('RS256'));
            if ($data) {
                $expired = $data['exp'];
                $user = $this->getUserToken($token);

                $now = time();
                if ($expired<$now) {
                    throw new \Exception("Token expired");
                }

                //$_SESSION['token'] = $token;
                $this->getSession()->put('token', $token);
                //$_SESSION['expired'] = $expired;
                $this->getSession()->put('expired', $expired);
                //$_SESSION['user'] = $token;

                $user = array_merge(array(
                    '_Login' => @$user['Login'],
                    '_Nama' => @$user['Login'],
                    '_TabelUser' => @$user['TabelUser'],
                    '_LevelID' => @$user['LevelID'],
                    '_Superuser' => (@$user['Superuser'] == "Y"? @$user['Superuser']:''),
                    '_ProdiID' => @$user['ProdiID'],
                    '_KodeID' => 'UNSIL'
                ), $user);
                $this->getSession()->put('user', (array) $user);

            }
        } catch (\Exception $exception) {
            $this->exception = $exception;
            if ($exception->getMessage() == 'Expired token') {
                return false;
            }
            if ($exception->getMessage() == 'User sudah logout.') {
                $this->getSession()->flush();
                return false;
            }
            return false;
        }
        return $user;
    }

    public function getUserToken($token)
    {
        $user = $this->curlUser($token);
        return $user;
    }

    public function curlUser($token)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::USER_URL.$token,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = @json_decode($response, 1);
        if (!$response) throw new \Exception("Gagal mengambil user");

        if (!$response['status'])  throw new \Exception($response['message']);
        return $response['data'];
    }

    public function refreshUser($token) {
        $token = $this->curlRefreh($token);
        if (!@$token['status']) throw new \Exception(@$token['message']);

        return @$token['token'];
    }

    public function curlRefreh($token) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::REFRESH_URL.$token,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = @json_decode($response, 1);
        if (!$response) throw new \Exception("Gagal refresh token");

        return $response;
    }

    public function loadPublicKey()
    {
        return file_get_contents(
            base_path(env('PUBLIC_KEY_PATH'))
        );
    }

    public function generateLoginUrl()
    {
        $uri_string = url()->current();
        $uri_string = str_ireplace(url("/"), "", $uri_string);
        if (
            $uri_string == "/sso_callback"
            || $uri_string == "sso_callback"
        ) $uri_string = "dashboard";

        return redirect(route('login').'?last_url='.$uri_string);
    }
}
