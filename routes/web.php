<?php

use App\Http\Controllers\AdminDealershipLogController;
use App\Http\Controllers\AdminContentLogController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminNotificationLogController;
use App\Http\Controllers\AdminMonthlyMagazineController;
use App\Http\Controllers\AdminLogController;
use App\Http\Controllers\RoleViewerController;
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
    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::get('/agenda/usuarios/{user}', [UserController::class, 'show'])->name('agenda.users.show');
    Route::get('/agenda/contactos/{contact}', [ContactController::class, 'show'])->name('agenda.contacts.show');
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
    Route::get('/leaderboard/ventas', [LeaderboardController::class, 'sales'])->name('leaderboard.sales');
    Route::get('/leaderboard/compras', [LeaderboardController::class, 'purchases'])->name('leaderboard.purchases');
    Route::get('/leaderboard/coches', [LeaderboardController::class, 'vehicles'])->name('leaderboard.vehicles');
    Route::get('/delegaciones', [DealershipController::class, 'index'])->name('dealerships.index');
    Route::get('/delegaciones/{dealership}', [DealershipController::class, 'show'])
        ->whereNumber('dealership')
        ->name('dealerships.show');
    Route::get('/web', function () {
        abort_unless(app_can_access_web(), 403);

        return view('tools.web-hr-motor', [
            'hrMotorUrl' => 'https://hrmotor.com/gestor',
        ]);
    })->name('tools.web');

    Route::post('/visor-roles', [RoleViewerController::class, 'store'])->name('role-viewer.store');
    Route::delete('/visor-roles', [RoleViewerController::class, 'destroy'])->name('role-viewer.destroy');

    Route::get('/', function (LeaderboardTrendService $trendService) {
        $authUser = request()->user();
        $visibleRole = app_visible_role($authUser);
        $isStoreManager = $authUser?->isStoreManager() ?? false;
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
                        'label' => 'Google Drive',
                        'url' => config('portal.links.tools.google_drive'),
                        'image' => asset('images/tools/drive.png'),
                    ],
                    [
                        'label' => 'Occident',
                        'url' => 'https://cliente.occident.com/policies/32032289/B/fleets',
                        'image' => asset('images/tools/occident.jpg'),
                    ],
                    [
                        'label' => 'Calcular IVA',
                        'url' => 'https://calcular-iva.net/',
                        'image' => asset('images/tools/calcular-iva.jpg'),
                    ],
                    [
                        'label' => 'Tareas asignadas',
                        'url' => 'https://axiumsoluciones-my.sharepoint.com/:x:/r/personal/carlos_jimenez_hrmotor_es/_layouts/15/guestaccess.aspx?e=4%3AMLgEPu&at=9&share=IQCamQKzRFtvRryS0Dmv_eAtAYXxXXXxBKlQYJlfbB3-4WI',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Canva',
                        'url' => 'https://www.canva.com/',
                        'image' => asset('images/tools/canva.png'),
                    ],
                    [
                        'label' => 'ChatGPT',
                        'url' => 'https://chatgpt.com/',
                        'image' => asset('images/tools/chatgpt.png'),
                    ],
                    [
                        'label' => 'Envato',
                        'url' => 'https://app.envato.com/',
                        'image' => asset('images/tools/envato.jpg'),
                    ],
                    [
                        'label' => 'Trustpilot',
                        'url' => 'https://es.trustpilot.com',
                        'image' => asset('images/tools/trustpilot.jpg'),
                    ],
                    [
                        'label' => 'Brevo',
                        'url' => 'https://www.brevo.com/es/landing/products/?utm_source=adwords_brand&utm_medium=lastclick&utm_content=SendinBlue&utm_extension=&utm_term=brevo&utm_matchtype=e&utm_campaign=20027374472&utm_network=g&km_adid=656177194310&km_adposition=&km_device=c&utm_adgroupid=148424525436&gad_source=1&gad_campaignid=20027374472&gclid=CjwKCAjw46HPBhAMEiwASZpLRHPpa5cY5OVSvZ1nzM0F4-M6bkKkUJzkDHI18bUAHrlCCmLxFKCkxxoC9BAQAvD_BwE',
                        'image' => asset('images/tools/brevo.png'),
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

        if (! app_user_has_any_role($authUser, [
            User::ROLE_COMMERCIAL,
            User::ROLE_STORE_MANAGER,
            User::ROLE_AREA_MANAGER,
        ])) {
            $buttonSections = collect($buttonSections)
                ->map(function (array $section) use ($authUser, $visibleRole): array {
                    if (($section['title'] ?? null) !== 'Herramientas generales') {
                        return $section;
                    }

                    $section['buttons'] = collect($section['buttons'] ?? [])
                        ->filter(fn (array $button) => in_array($button['label'] ?? null, ['OneDrive', 'Woffu', 'Web HR Motor'], true) || (
                            ($button['label'] ?? null) === 'Tareas asignadas'
                            && app_user_has_any_role($authUser, [User::ROLE_MARKETING])
                        ) || (
                            in_array($button['label'] ?? null, ['Canva', 'ChatGPT', 'Envato', 'Trustpilot', 'Brevo'], true)
                            && app_user_has_any_role($authUser, [User::ROLE_MARKETING])
                        ) || (
                            in_array($button['label'] ?? null, ['Google Drive', 'Occident', 'Calcular IVA'], true)
                            && in_array($visibleRole, [User::ROLE_ADMINISTRATION], true)
                        ))
                        ->values()
                        ->all();

                    return $section;
                })
                ->all();
        }

        $otherResourcesSection = app_user_has_any_role($authUser, [User::ROLE_MARKETING])
            ? [
                'title' => 'Otros recursos',
                'buttons' => [
                    [
                        'label' => 'Jottacloud',
                        'url' => 'https://jottacloud.com/web/search/list/name/WVWZZZAUZLW072077',
                        'image' => asset('images/tools/jottacloud.png'),
                    ],
                    [
                        'label' => 'CarCutter',
                        'url' => 'https://hub.car-cutter.com/workspace/7ec7b15b-b8a4-47b1-b1b1-5d73d29b341b/',
                        'image' => asset('images/tools/carcutter.jpg'),
                    ],
                    [
                        'label' => 'Pendiente editar',
                        'url' => 'https://hrmotor.lightning.force.com/lightning/r/Report/00OQx00000W4PbjMAF/view?queryScope=userFolders',
                        'image' => asset('images/tools/salesforce.png'),
                    ],
                    [
                        'label' => 'Inventario.pro',
                        'url' => 'https://admin.inventario.pro/login',
                        'image' => asset('images/tools/Inventario-pro.jpg'),
                    ],
                    [
                        'label' => 'Informe fotografía',
                        'url' => 'https://docs.google.com/spreadsheets/d/1OMiDqfiTeHWagtXNJagFpNVqxZjuOa5i/edit?gid=374625686#gid=374625686',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                ],
            ]
            : null;

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

        return view('home', compact('buttonSections', 'otherResourcesSection', 'videos', 'homeLeaderboardEntries', 'homeLeaderboardMovements', 'itSupportUrl', 'magazine'));
    })->name('home');

    Route::get('/videos', function () {
        abort_unless(app_can_access_videos(), 403);

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
                    'kind' => 'management',
                    'icon' => 'users',
                ],
                [
                    'label' => 'Gestión de delegaciones',
                    'description' => 'Consulta, crea y organiza las delegaciones disponibles.',
                    'route' => 'dealerships.index',
                    'kind' => 'management',
                    'icon' => 'dealership',
                ],
                [
                    'label' => 'Contactos de agenda',
                    'description' => 'Mantiene los contactos externos que aparecen junto al directorio interno.',
                    'route' => 'admin.contacts.index',
                    'kind' => 'management',
                    'icon' => 'contacts',
                ],
                [
                    'label' => 'Tags del foro',
                    'description' => 'Crea, edita y elimina etiquetas para organizar las dudas del foro.',
                    'route' => 'admin.forum-tags.index',
                    'kind' => 'management',
                    'icon' => 'tags',
                ],
                [
                    'label' => 'Revista mensual',
                    'description' => 'Publica la edición mensual, actualiza el texto visible de la portada y gestiona el nombre del archivo.',
                    'route' => 'admin.magazine.edit',
                    'kind' => 'management',
                    'icon' => 'magazine',
                ],
                [
                    'label' => 'Notificaciones prioritarias',
                    'description' => 'Envía avisos destacados a los roles que elijas para que aparezcan por encima de las notificaciones del foro.',
                    'route' => 'admin.notifications.create',
                    'kind' => 'management',
                    'icon' => 'notifications',
                ],
                [
                    'label' => 'Logs de notificaciones',
                    'description' => 'Revisa qué notificaciones prioritarias se enviaron, a quién iban dirigidas y cuántos usuarios las recibieron.',
                    'route' => 'admin.notification-logs.index',
                    'kind' => 'logs',
                    'icon' => 'notification-log',
                ],
                [
                    'label' => 'Logs de usuarios',
                    'description' => 'Consulta el historial de altas, ediciones y eliminaciones de usuarios.',
                    'route' => 'admin.logs.index',
                    'kind' => 'logs',
                    'icon' => 'user-log',
                ],
                [
                    'label' => 'Logs de delegaciones',
                    'description' => 'Consulta el historial de altas, ediciones y eliminaciones de delegaciones.',
                    'route' => 'admin.dealership-logs.index',
                    'kind' => 'logs',
                    'icon' => 'dealership-log',
                ],
                [
                    'label' => 'Logs de contenidos',
                    'description' => 'Consulta el historial de la revista mensual, los tags del foro y los contactos en un único lugar.',
                    'route' => 'admin.content-logs.index',
                    'kind' => 'logs',
                    'icon' => 'content-log',
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

        Route::get('/delegaciones/crear', [DealershipController::class, 'create'])->name('dealerships.create');
        Route::post('/delegaciones', [DealershipController::class, 'store'])->name('dealerships.store');
        Route::get('/delegaciones/{dealership}/editar', [DealershipController::class, 'edit'])
            ->whereNumber('dealership')
            ->name('dealerships.edit');
        Route::put('/delegaciones/{dealership}', [DealershipController::class, 'update'])
            ->whereNumber('dealership')
            ->name('dealerships.update');
        Route::delete('/delegaciones/{dealership}', [DealershipController::class, 'destroy'])
            ->whereNumber('dealership')
            ->name('dealerships.destroy');
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
        Route::get('/admin/notificaciones', [AdminNotificationController::class, 'create'])->name('admin.notifications.create');
        Route::post('/admin/notificaciones', [AdminNotificationController::class, 'store'])->name('admin.notifications.store');
        Route::get('/admin/logs/notificaciones', [AdminNotificationLogController::class, 'index'])->name('admin.notification-logs.index');
        Route::get('/admin/logs/notificaciones/descargar', [AdminNotificationLogController::class, 'export'])->name('admin.notification-logs.export');
        Route::redirect('/admin/logs', '/admin/logs/usuarios');
        Route::get('/admin/logs/usuarios', [AdminLogController::class, 'index'])->name('admin.logs.index');
        Route::get('/admin/logs/usuarios/descargar', [AdminLogController::class, 'export'])->name('admin.logs.export');
        Route::get('/admin/logs/delegaciones', [AdminDealershipLogController::class, 'index'])->name('admin.dealership-logs.index');
        Route::get('/admin/logs/delegaciones/descargar', [AdminDealershipLogController::class, 'export'])->name('admin.dealership-logs.export');
        Route::get('/admin/contactos', [ContactController::class, 'index'])->name('admin.contacts.index');
        Route::get('/admin/contactos/crear', [ContactController::class, 'create'])->name('admin.contacts.create');
        Route::post('/admin/contactos', [ContactController::class, 'store'])->name('admin.contacts.store');
        Route::get('/admin/contactos/{contact}/editar', [ContactController::class, 'edit'])->name('admin.contacts.edit');
        Route::put('/admin/contactos/{contact}', [ContactController::class, 'update'])->name('admin.contacts.update');
        Route::delete('/admin/contactos/{contact}', [ContactController::class, 'destroy'])->name('admin.contacts.destroy');
    });
});
