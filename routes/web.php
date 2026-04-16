<?php

use App\Http\Controllers\AdminDealershipLogController;
use App\Http\Controllers\AdminContentLogController;
use App\Http\Controllers\AdminMonthlyMagazineController;
use App\Http\Controllers\AdminLogController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\DealershipController;
use App\Http\Controllers\ForumThreadController;
use App\Http\Controllers\ForumTagController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesforceAuthController;
use App\Http\Controllers\SalesforceLeaderboardSyncController;
use App\Http\Controllers\UserController;
use App\Models\MonthlyMagazineSetting;
use App\Models\SalesLeaderboardEntry;
use App\Models\User;
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
    Route::get('/foro', [ForumThreadController::class, 'index'])->name('forum.index');
    Route::get('/foro/crear', [ForumThreadController::class, 'create'])->name('forum.create');
    Route::post('/foro', [ForumThreadController::class, 'store'])->name('forum.store');
    Route::get('/foro/{thread}', [ForumThreadController::class, 'show'])->whereNumber('thread')->name('forum.show');
    Route::post('/foro/{thread}/respuestas', [ForumThreadController::class, 'reply'])->whereNumber('thread')->name('forum.reply');
    Route::patch('/foro/{thread}/estado', [ForumThreadController::class, 'updateStatus'])->whereNumber('thread')->name('forum.status.update');
    Route::delete('/foro/{thread}', [ForumThreadController::class, 'destroy'])->whereNumber('thread')->name('forum.destroy');
    Route::get('/notificaciones/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
    Route::get('/leaderboard/ventas', [LeaderboardController::class, 'sales'])->name('leaderboard.sales');
    Route::get('/leaderboard/compras', [LeaderboardController::class, 'purchases'])->name('leaderboard.purchases');
    Route::get('/leaderboard/coches', [LeaderboardController::class, 'vehicles'])->name('leaderboard.vehicles');
    Route::get('/web', function () {
        return view('tools.web-hr-motor', [
            'hrMotorUrl' => 'https://hrmotor.com/gestor',
        ]);
    })->name('tools.web');

    Route::get('/', function (LeaderboardTrendService $trendService) {
        $authUser = request()->user();
        $isStoreManager = $authUser?->role === User::ROLE_STORE_MANAGER;
        $salesforceUrl = $isStoreManager
            ? 'https://hrmotor.lightning.force.com/lightning/n/Veh_culos'
            : config('portal.links.tools.salesforce_comunidad');
        $salesforceLabel = $isStoreManager ? 'Salesforce' : 'Salesforce comunidad';
        $itSupportUrl = $isStoreManager
            ? 'https://hrmotor.lightning.force.com/lightning/o/Tareas_Departamento_Informatico__c/list?filterName=__Recent'
            : config('portal.links.it_support');

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
                        'label' => 'OneDrive',
                        'url' => config('portal.links.tools.onedrive'),
                        'image' => asset('images/tools/onedrive.webp'),
                    ],
                    [
                        'label' => 'Web HR Motor',
                        'url' => route('tools.web'),
                        'image' => asset('images/tools/hrmotor.png'),
                        'open_in_new_tab' => false,
                    ],
                    [
                        'label' => 'Woffu',
                        'url' => config('portal.links.tools.woffu'),
                        'image' => asset('images/tools/woffu.png'),
                    ],
                    [
                        'label' => $salesforceLabel,
                        'url' => $salesforceUrl,
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
                ->when(config('services.salesforce.excluded_leaderboard_user_ids', []) !== [], function ($query) {
                    $query->whereNotIn('salesforce_user_id', config('services.salesforce.excluded_leaderboard_user_ids', []));
                })
                ->orderBy('ranking_position')
                ->limit(10)
                ->get()
            : new Collection();

        $magazine = MonthlyMagazineSetting::current();
        $homeLeaderboardMovements = $trendService->buildMovementMap($homeLeaderboardEntries);

        return view('home', compact('buttonSections', 'videos', 'homeLeaderboardEntries', 'homeLeaderboardMovements', 'itSupportUrl', 'magazine'));
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
        Route::get('/admin', function () {
            $adminSections = [
                [
                    'label' => 'Gestión de usuarios',
                    'description' => 'Altas, edición de perfiles, roles y seguimiento del equipo.',
                    'route' => 'users.index',
                ],
                [
                    'label' => 'Gestión de delegaciones',
                    'description' => 'Consulta, crea y organiza las delegaciones disponibles.',
                    'route' => 'dealerships.index',
                ],
                [
                    'label' => 'Tags del foro',
                    'description' => 'Crea, edita y elimina etiquetas para organizar las dudas del foro.',
                    'route' => 'admin.forum-tags.index',
                ],
                [
                    'label' => 'Revista mensual',
                    'description' => 'Publica la edición mensual, actualiza el texto visible de la portada y gestiona el nombre del archivo.',
                    'route' => 'admin.magazine.edit',
                ],
                [
                    'label' => 'Logs de usuarios',
                    'description' => 'Consulta el historial de altas, ediciones y eliminaciones de usuarios.',
                    'route' => 'admin.logs.index',
                ],
                [
                    'label' => 'Logs de delegaciones',
                    'description' => 'Consulta el historial de altas, ediciones y eliminaciones de delegaciones.',
                    'route' => 'admin.dealership-logs.index',
                ],
                [
                    'label' => 'Logs de contenidos',
                    'description' => 'Consulta el historial de la revista mensual y de los tags del foro en un único lugar.',
                    'route' => 'admin.content-logs.index',
                ],
            ];

            return view('admin.index', compact('adminSections'));
        })->name('admin.index');

        Route::get('/integraciones/salesforce/conectar', [SalesforceAuthController::class, 'redirect'])->name('salesforce.connect');
        Route::get('/integraciones/salesforce/callback', [SalesforceAuthController::class, 'callback'])->name('salesforce.callback');
        Route::post('/leaderboard/sync', SalesforceLeaderboardSyncController::class)->name('leaderboard.sync');

        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::get('/usuarios/crear', [UserController::class, 'create'])->name('users.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::post('/usuarios/{user}/reenviar-invitacion', [UserController::class, 'resendInvitation'])->name('users.resend-invitation');
        Route::get('/usuarios/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/delegaciones', [DealershipController::class, 'index'])->name('dealerships.index');
        Route::get('/delegaciones/crear', [DealershipController::class, 'create'])->name('dealerships.create');
        Route::post('/delegaciones', [DealershipController::class, 'store'])->name('dealerships.store');
        Route::get('/delegaciones/{dealership}', [DealershipController::class, 'show'])->name('dealerships.show');
        Route::get('/delegaciones/{dealership}/editar', [DealershipController::class, 'edit'])->name('dealerships.edit');
        Route::put('/delegaciones/{dealership}', [DealershipController::class, 'update'])->name('dealerships.update');
        Route::delete('/delegaciones/{dealership}', [DealershipController::class, 'destroy'])->name('dealerships.destroy');
        Route::get('/foro/tags', [ForumTagController::class, 'index'])->name('admin.forum-tags.index');
        Route::get('/foro/tags/crear', [ForumTagController::class, 'create'])->name('admin.forum-tags.create');
        Route::post('/foro/tags', [ForumTagController::class, 'store'])->name('admin.forum-tags.store');
        Route::get('/foro/tags/{forumTag}/editar', [ForumTagController::class, 'edit'])->name('admin.forum-tags.edit');
        Route::put('/foro/tags/{forumTag}', [ForumTagController::class, 'update'])->name('admin.forum-tags.update');
        Route::delete('/foro/tags/{forumTag}', [ForumTagController::class, 'destroy'])->name('admin.forum-tags.destroy');
        Route::get('/admin/logs/contenidos', [AdminContentLogController::class, 'index'])->name('admin.content-logs.index');
        Route::get('/admin/logs/contenidos/descargar', [AdminContentLogController::class, 'export'])->name('admin.content-logs.export');
        Route::redirect('/admin/logs/tags', '/admin/logs/contenidos?content_type=' . \App\Models\ContentActivityLog::CONTENT_TYPE_FORUM_TAG);
        Route::redirect('/admin/logs/tags/descargar', '/admin/logs/contenidos/descargar?content_type=' . \App\Models\ContentActivityLog::CONTENT_TYPE_FORUM_TAG);
        Route::get('/admin/revista-mensual', [AdminMonthlyMagazineController::class, 'edit'])->name('admin.magazine.edit');
        Route::put('/admin/revista-mensual', [AdminMonthlyMagazineController::class, 'update'])->name('admin.magazine.update');
        Route::redirect('/admin/logs', '/admin/logs/usuarios');
        Route::get('/admin/logs/usuarios', [AdminLogController::class, 'index'])->name('admin.logs.index');
        Route::get('/admin/logs/usuarios/descargar', [AdminLogController::class, 'export'])->name('admin.logs.export');
        Route::get('/admin/logs/delegaciones', [AdminDealershipLogController::class, 'index'])->name('admin.dealership-logs.index');
        Route::get('/admin/logs/delegaciones/descargar', [AdminDealershipLogController::class, 'export'])->name('admin.dealership-logs.export');
    });
});
