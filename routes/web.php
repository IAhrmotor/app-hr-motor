<?php

use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesforceAuthController;
use App\Http\Controllers\SalesforceLeaderboardSyncController;
use App\Http\Controllers\UserController;
use App\Models\SalesLeaderboardEntry;
use App\Services\LeaderboardTrendService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/reset-password', function () {
    return redirect()->route('password.request');
});

Route::middleware('auth')->group(function () {
    Route::get('/mi-perfil', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
    Route::get('/leaderboard/ventas', [LeaderboardController::class, 'sales'])->name('leaderboard.sales');
    Route::get('/leaderboard/compras', [LeaderboardController::class, 'purchases'])->name('leaderboard.purchases');

    Route::get('/', function (LeaderboardTrendService $trendService) {
        $buttonSections = [
            [
                'title' => 'Herramientas generales',
                'buttons' => [
                    [
                        'label' => 'DocuSign',
                        'url' => config('portal.links.tools.docusign'),
                        'image' => asset('images/tools/docusign.jpg'),
                    ],
                    [
                        'label' => 'Google Drive',
                        'url' => config('portal.links.tools.google_drive'),
                        'image' => asset('images/tools/drive.png'),
                    ],
                    [
                        'label' => 'Web HR Motor',
                        'url' => config('portal.links.tools.web_hr_motor'),
                        'image' => asset('images/tools/hrmotor.png'),
                    ],
                    [
                        'label' => 'Woffu',
                        'url' => config('portal.links.tools.woffu'),
                        'image' => asset('images/tools/woffu.png'),
                    ],
                    [
                        'label' => 'Salesforce comunidad',
                        'url' => config('portal.links.tools.salesforce_comunidad'),
                        'image' => asset('images/tools/salesforce.png'),
                    ],
                    [
                        'label' => 'Lendismart',
                        'url' => config('portal.links.tools.lendismart'),
                        'image' => asset('images/tools/lendismart.png'),
                    ],
                    [
                        'label' => 'My Mutua',
                        'url' => config('portal.links.tools.my_mutua'),
                        'image' => asset('images/tools/mutua.png'),
                    ],
                    [
                        'label' => 'Formación Comerciales',
                        'url' => config('portal.links.tools.formacion_comerciales'),
                        'image' => asset('images/tools/logo-formacion-cuadrado.png'),
                    ],
                    [
                        'label' => 'ServiceForm',
                        'url' => config('portal.links.tools.serviceform'),
                        'image' => asset('images/tools/serviceform.png'),
                    ],
                ],
            ],
            [
                'title' => 'Office 365 online',
                'buttons' => [
                    [
                        'label' => 'Outlook',
                        'url' => config('portal.links.tools.outlook'),
                        'image' => asset('images/tools/outlook.webp'),
                    ],
                    [
                        'label' => 'Teams',
                        'url' => config('portal.links.tools.teams'),
                        'image' => asset('images/tools/teams.png'),
                    ],
                    [
                        'label' => 'OneDrive',
                        'url' => config('portal.links.tools.onedrive'),
                        'image' => asset('images/tools/onedrive.webp'),
                    ],
                    [
                        'label' => 'Word',
                        'url' => config('portal.links.tools.word'),
                        'image' => asset('images/tools/word.png'),
                    ],
                    [
                        'label' => 'Excel',
                        'url' => config('portal.links.tools.excel'),
                        'image' => asset('images/tools/excel.png'),
                    ],
                ],
            ],
            [
                'title' => 'Comunicación',
                'buttons' => [
                    [
                        'label' => 'WhatsApp Web',
                        'url' => config('portal.links.tools.whatsapp_web'),
                        'image' => asset('images/tools/whatsapp.jpg'),
                    ],
                    [
                        'label' => '3CX',
                        'url' => config('portal.links.tools.3cx'),
                        'image' => asset('images/tools/3cx.png'),
                    ],
                    [
                        'label' => 'Webmail',
                        'url' => config('portal.links.tools.webmail'),
                        'image' => asset('images/tools/webmail.png'),
                    ],
                ],
            ],
        ];

        $videos = [
            [
                'title' => 'Firma electrónica DocuSign',
                'youtube_id' => 'jibss9YUw8M',
            ],
            [
                'title' => 'Vista general Salesforce',
                'youtube_id' => 'ERovlZLtQbE',
            ],
            [
                'title' => 'Proceso Seguros Mutua',
                'youtube_id' => '7zVvaFasavY',
            ],
        ];

        $homeLeaderboardEntries = Schema::hasTable('sales_leaderboard_entries')
            ? SalesLeaderboardEntry::query()
                ->with('user')
                ->orderBy('ranking_position')
                ->limit(10)
                ->get()
            : new Collection();

        $homeLeaderboardMovements = $trendService->buildMovementMap($homeLeaderboardEntries);

        return view('home', compact('buttonSections', 'videos', 'homeLeaderboardEntries', 'homeLeaderboardMovements'));
    })->name('home');

    Route::get('/videos', function () {
        $videos = [
            [
                'title' => 'Vista general Salesforce',
                'youtube_id' => 'ERovlZLtQbE',
            ],
            [
                'title' => 'Creación y gestión de leads en Salesforce',
                'youtube_id' => 'alcUcfS5S4Y',
            ],
            [
                'title' => 'Conversión del lead en Salesforce',
                'youtube_id' => 'sGzVQy_Wh_4',
            ],
            [
                'title' => 'Solicitud de financiación en Salesforce',
                'youtube_id' => 'eujhUlpJ5Qk',
            ],
            [
                'title' => 'Venta con cambio en Salesforce',
                'youtube_id' => '8gZb9uKgdfE',
            ],
            [
                'title' => 'Proceso de reserva en Salesforce',
                'youtube_id' => 'w-_wLjFHzQE',
            ],
            [
                'title' => 'Seguimiento de las reservas en Salesforce',
                'youtube_id' => 'DiXNmle_pMs',
            ],
            [
                'title' => 'Proceso de tasación en Salesforce',
                'youtube_id' => 'kU4OQHZ8dNc',
            ],
            [
                'title' => 'Firma electrónica DocuSign',
                'youtube_id' => 'jibss9YUw8M',
            ],
            [
                'title' => 'Proceso de entrega en Salesforce',
                'youtube_id' => 'cP8RnVg3V0c',
            ],
            [
                'title' => 'Ficha del vehículo en Salesforce',
                'youtube_id' => '0KF5hs-lBlk',
            ],
            [
                'title' => 'Proceso Seguros Mutua',
                'youtube_id' => '7zVvaFasavY',
            ],
            [
                'title' => 'Gestión de reseñas',
                'youtube_id' => 'cGDOABxjNbs',
            ],
        ];

        return view('videos', compact('videos'));
    })->name('videos');

    Route::middleware('role:admin,gestor')->group(function () {
        Route::get('/integraciones/salesforce/conectar', [SalesforceAuthController::class, 'redirect'])->name('salesforce.connect');
        Route::get('/integraciones/salesforce/callback', [SalesforceAuthController::class, 'callback'])->name('salesforce.callback');
        Route::post('/leaderboard/sync', SalesforceLeaderboardSyncController::class)->name('leaderboard.sync');

        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::get('/usuarios/crear', [UserController::class, 'create'])->name('users.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::get('/usuarios/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
