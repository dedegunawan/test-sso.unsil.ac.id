<?php

namespace App\Http\Controllers;

use App\Helpers\SsoSimakHelper;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return SsoSimakHelper::getInstance()->getUser();
    }
}
