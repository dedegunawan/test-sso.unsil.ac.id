<?php

namespace App\Http\Controllers;

use App\Helpers\SsoSimakHelper;
use Illuminate\Http\Request;

class CallbackController extends Controller
{
    const DEFAULT_DASHBOARD = 'dashboard';
    const CALLBACK_URL = 'callback';
    public function index(Request $request)
    {
        $token = $request->input('token');
        if (!$token) {
            return SsoSimakHelper::loginUrlSimak();
        }
        $last_url = $request->input('last_url');
        if (!$last_url) $last_url = self::DEFAULT_DASHBOARD;

        $user = SsoSimakHelper::getInstance()->loginToken($token);
        if ($user) return redirect($last_url);
        $exception = SsoSimakHelper::getInstance()->getException();
        $message = $exception->getMessage();
        if (
            !$user
            && in_array($message, ['Expired token', 'User sudah logout.'])
        ) {
            return SsoSimakHelper::getInstance()->generateLoginUrl();
        }
        return abort(403, $message);

    }

    public function login(Request $request) {
        $last_url = $request->input('last_url');
        if (
            $last_url == "/".self::CALLBACK_URL
            || $last_url == self::CALLBACK_URL
        ) $last_url = self::DEFAULT_DASHBOARD;

        return redirect(
            SsoSimakHelper::loginUrlSimak($last_url)
        );
    }
}
