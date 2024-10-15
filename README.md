## Simple Login SIMAK

1. Tambahkan & Sesuaikan routes di `routes/web.php` :
```
Route::get('/callback', [
    \App\Http\Controllers\CallbackController::class,
    'index'
]);

Route::get('/login', [
    \App\Http\Controllers\CallbackController::class,
    'login'
])->name('login');
```

2. Tambahkan & Sesuaikan Controller `app/Http/Controllers/CallbackController.php` untuk `DEFAULT_DASHBOARD` & `CALLBACK_URL` sesuai poin 1 
3. Tambahkan & Sesuaikan middleware `app/Http/Middleware/AuthSimakMiddleware.php`
4. Tambahkan & Sesuaikan middleware `app/Helpers/SsoSimakHelper.php`
5. Copy file `simakunsil_public.key` ke root directory. Biasanya dikirim dari developer.
6. Tambahkan & Sesuaikan konfigurasi pada `.env` :
```
PUBLIC_KEY_PATH=simakunsil_public.key
SIMAK_APP_ID=SESUAI_KONFIGURASI
SIMAK_CLIENT_ID=SESUAI_KONFIGURASI
```

7. Implementasikan middleware untuk resource yang membutuhkan data simak, contoh :
```
Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware(\App\Http\Middleware\AuthSimakMiddleware::class);
```
8. Akses data simak menggunakan fungsi `SsoSimakHelper::getInstance()->getUser()`, contoh pada controller :
```
class DashboardController extends Controller
{
    public function index()
    {
        return SsoSimakHelper::getInstance()->getUser();
    }
}
```
9. Jangan lupa install package firebase/php-jwt : `composer require firebase/php-jwt:^5.4`
10. Selesai
