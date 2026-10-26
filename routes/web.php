<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WhatsAppConnectionController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : response()->view('landing'));
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])->name('webhooks.whatsapp.receive');
Route::view('/conditions', 'legal', ['heading' => 'Conditions d’utilisation', 'document' => 'terms'])->name('legal.terms');
Route::view('/confidentialite', 'legal', ['heading' => 'Politique de confidentialité', 'document' => 'privacy'])->name('legal.privacy');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
});

use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\ProspectingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WhatsAppNumberController;

Route::middleware('auth')->group(function () {
    // Super Administration Plateforme
    Route::middleware('super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/tenants', [AdminController::class, 'tenants'])->name('tenants.index');
        Route::get('/tenants/{tenant}', [AdminController::class, 'showTenant'])->name('tenants.show');
        Route::post('/tenants/{tenant}/toggle-status', [AdminController::class, 'toggleTenantStatus'])->name('tenants.toggle-status');
        Route::post('/tenants/{tenant}/switch', [AdminController::class, 'switchTenant'])->name('tenants.switch');
        Route::get('/users', [AdminController::class, 'users'])->name('users.index');
        Route::post('/users/{user}/toggle-admin', [AdminController::class, 'toggleUserAdmin'])->name('users.toggle-admin');
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
        Route::get('/campaigns', [AdminController::class, 'campaigns'])->name('campaigns.index');
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('campaigns', CampaignController::class);
    Route::post('/campaigns/{campaign}/launch', [CampaignController::class, 'launch'])->name('campaigns.launch');
    Route::get('/channels', [WhatsAppConnectionController::class, 'index'])->name('channels.index');
    Route::post('/channels/whatsapp/test', [WhatsAppConnectionController::class, 'testConfiguration'])->name('channels.whatsapp.test');
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::get('/media/{mediaAsset}/download', [MediaController::class, 'download'])->name('media.download');
    Route::delete('/media/{mediaAsset}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::post('/contacts/import', [ContactController::class, 'import'])->middleware('throttle:10,1')->name('contacts.import');
    Route::post('/contacts/{contact}/consent', [ContactController::class, 'toggleConsent'])->name('contacts.consent');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company.update');
    Route::put('/settings/whatsapp', [SettingsController::class, 'saveWhatsApp'])->name('settings.whatsapp.save');
    Route::delete('/settings/whatsapp', [SettingsController::class, 'deleteWhatsApp'])->name('settings.whatsapp.delete');

    // NOUVELLES FONCTIONNALITÉS : MULTI-NUMÉROS WHATSAPP (10 SLOTS)
    Route::get('/whatsapp-numbers', [WhatsAppNumberController::class, 'index'])->name('whatsapp-numbers.index');
    Route::post('/whatsapp-numbers', [WhatsAppNumberController::class, 'store'])->name('whatsapp-numbers.store');
    Route::put('/whatsapp-numbers/{account}', [WhatsAppNumberController::class, 'update'])->name('whatsapp-numbers.update');
    Route::post('/whatsapp-numbers/{account}/toggle', [WhatsAppNumberController::class, 'toggleActive'])->name('whatsapp-numbers.toggle');
    Route::delete('/whatsapp-numbers/{account}', [WhatsAppNumberController::class, 'destroy'])->name('whatsapp-numbers.destroy');

    // PROSPECTION & SCRAPING IA ULTRA-CIBLÉ
    Route::get('/prospecting', [ProspectingController::class, 'index'])->name('prospecting.index');
    Route::post('/prospecting/search', [ProspectingController::class, 'search'])->name('prospecting.search');
    Route::post('/prospecting/{lead}/import', [ProspectingController::class, 'importSingle'])->name('prospecting.import-single');
    Route::post('/prospecting/import-all', [ProspectingController::class, 'importAll'])->name('prospecting.import-all');
    Route::delete('/prospecting/{lead}', [ProspectingController::class, 'destroy'])->name('prospecting.destroy');
    Route::post('/prospecting/clear', [ProspectingController::class, 'clearAll'])->name('prospecting.clear');

    // DIFFUSIONS MULTI-NUMÉROS ROTATION & SPINTAX
    Route::get('/broadcasts', [BroadcastController::class, 'index'])->name('broadcasts.index');
    Route::get('/broadcasts/create', [BroadcastController::class, 'create'])->name('broadcasts.create');
    Route::post('/broadcasts', [BroadcastController::class, 'store'])->name('broadcasts.store');
    Route::get('/broadcasts/{broadcast}', [BroadcastController::class, 'show'])->name('broadcasts.show');
    Route::delete('/broadcasts/{broadcast}', [BroadcastController::class, 'destroy'])->name('broadcasts.destroy');

    // BOÎTE DE RÉCEPTION UNIFIÉE CRM MULTI-NUMÉROS
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::post('/inbox/reply', [InboxController::class, 'reply'])->name('inbox.reply');

    // ABONNEMENT ET QUOTAS SAAS
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/switch', [SubscriptionController::class, 'switchPlan'])->name('subscription.switch');
});

