<?php

use Rbn\Framework\Core\Routes\Route;
use Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint;

/**
 * Auth Namespace - Core Modules\UserManagement
 * RBN 3.0: Smart Module Injection (Zero-Code) 🚀
 */
Route::module('Suite', 'RbnAuth')->prefix('')->group(function () {

    // Giriş sayfaları
    Route::get(RouteBlueprint::LOGIN_PATH, 'AuthViewController@showLogin')->name('login');
    Route::get('lockscreen', 'AuthViewController@showLockscreen')->name('lockscreen');

    // Kayıt sayfaları
    Route::get('register', 'AuthViewController@showRegister')->name('register');
    Route::get('kayit', 'AuthViewController@showRegister');

    // Giriş işlemleri
    Route::post('auth/login', 'AuthController@loginSubmit')->name('auth.login');
    Route::post('auth/authenticate', 'AuthController@authenticate');

    // Kayıt işlemleri
    Route::post('auth/register', 'AuthActionController@registerSubmit')->name('auth.register');

    // Çıkış işlemleri
    // [R-16] ASIL çıkış yolu: POST + CSRF (csrf_token ZORUNLU, fail-closed).
    Route::post('auth/logout', 'AuthController@logoutSubmit')->name('auth.logout');
    // GERİYE UYUMLU yollar: kaldırılmaz (meşru çıkış bağlantıları + duman).
    // Her kullanım LOG-ONLY `logout_get_deprecated` sayacına yazılır ve
    // çapraz-site (Sec-Fetch-Site/Referer) istekte çıkış YAPILMAZ (403).
    Route::get('logout', 'AuthController@logout')->name('logout');
    Route::get('cikis', 'AuthController@logout');

    // Şifre İşlemleri
    // [A0-6] Akış artık tek bağlantı (token) üzerinden yürür. `/verify-code`
    // rotası ve `AuthActionController@verifyCodeSubmit` **kaldırıldı**: hedef metot
    // hiç yoktu (YA-1) ve bağlantısız bir rotaydı → Dispatcher istisnası/500.
    Route::get('forgot-password', 'AuthViewController@showForgotPassword')->name('password.forgot');
    Route::get('reset-password', 'AuthViewController@showResetPassword')->name('password.reset');

    Route::post('auth/forgot-password', 'AuthActionController@forgotPasswordSubmit')->name('auth.password.forgot');
    Route::post('auth/reset-password', 'AuthActionController@resetPasswordSubmit')->name('auth.password.reset');

    // E-posta Doğrulama
    Route::get('verify-email', 'AuthActionController@verifyEmail')->name('auth.verify.email');
    Route::get('auth/verify-email', 'AuthActionController@verifyEmail');

    // Sovereign User Dashboard (/user) - RbnAuth Otomatik Koruma 🛡️
    Route::role('user')->prefix('user')->group(function () {
        Route::get('/', 'AuthViewController@showUserDashboard')->name('user.dashboard');
    });
});



