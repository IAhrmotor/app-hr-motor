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
        $isDirectSalesforceRole = in_array($visibleRole, [User::ROLE_STORE_MANAGER, User::ROLE_AREA_MANAGER], true);
        $isExternalWebUser = in_array($visibleRole, [User::ROLE_LEGAL, User::ROLE_ADMINISTRATION], true);
        $salesforceUrl = app_salesforce_url_for($authUser);
        $salesforceLabel = $isDirectSalesforceRole ? 'Salesforce' : 'Salesforce comunidad';
        $callCenterSalesforceUrl = 'https://hrmotor.lightning.force.com';
        $itSupportUrl = app_it_support_url_for($authUser);

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
                        'url' => $isExternalWebUser ? 'https://www.hrmotor.com/' : route('tools.web'),
                        'image' => asset('images/tools/hrmotor.png'),
                        'open_in_new_tab' => $isExternalWebUser,
                    ],
                    [
                        'label' => 'Google Drive',
                        'url' => app_user_has_any_role($authUser, [User::ROLE_RENTING])
                            ? 'https://drive.google.com/drive/folders/1mMTTZxsiAyasIgeEcE6wvefIHEaPH1aPq'
                            : config('portal.links.tools.google_drive'),
                        'image' => asset('images/tools/drive.png'),
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
                        'url' => app_user_has_any_role($authUser, [User::ROLE_RENTING])
                            ? 'https://www.canva.com/folder/FAFxRzs7QD0'
                            : 'https://www.canva.com/',
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
                        'label' => 'Contact Center Motorflash',
                        'url' => 'https://callcenter.motorflash.com/',
                        'image' => asset('images/tools/contact-center-motorflash.png'),
                    ],
                    [
                        'label' => 'Salesforce comunidad',
                        'url' => 'https://hrmotor.my.site.com/hrmotorcommunity/s/login/',
                        'image' => asset('images/tools/salesforce.png'),
                    ],
                    [
                        'label' => 'HR Renting',
                        'url' => 'https://hrrenting.com/renting/',
                        'image' => asset('images/tools/hrrenting.png'),
                    ],
                    [
                        'label' => 'Flit2GO',
                        'url' => 'https://manager.flit2go.com/#/s/vehicles/contracts',
                        'image' => asset('images/tools/flit2go.jpg'),
                    ],
                    [
                        'label' => 'Salesforce',
                        'url' => app_user_has_any_role($authUser, [User::ROLE_RENTING])
                            ? 'https://hrmotor.my.salesforce.com'
                            : $callCenterSalesforceUrl,
                        'image' => asset('images/tools/salesforce.png'),
                    ],
                    [
                        'label' => 'Axesor',
                        'url' => 'https://login.axesor.es/account/login?ReturnUrl=%2Fconnect%2Fauthorize%2Fcallback%3Fclient_id%3DWarsImplicitSTS%26redirect_uri%3Dhttps%253A%252F%252Fwww.axesor.es%252F%26response_type%3Did_token%2520token%26scope%3Dopenid%2520profile%2520email%2520BackOffice%2520ApiGestoria%2520ApiRestAxesor%26state%3DOpenIdConnect.AuthenticationProperties%253D9n7EF3VQALVdy7LAzGo41YYYFMLfXwPDfhrFyrwZhMjSE8ON0eSx2X8NTwlDRiVrYmyEswRsBCCPd5iDoRO12x3KpIX9kSGw-owqmKbBBYQ9Lx1TkiKcBQGDgu3uvn5fszKqo5FLGmheZsOnhyng3nWh34X3BvoTLHU5myd0rpiC9KB2LJXUgEr4ShNkwcTJ44X__S1ryzofJfTfs2AO7XXOV22vMOkIQr_-SJuS9sM%26response_mode%3Dform_post%26nonce%3D638996572989165092.NmJjOWQxNDUtZTVjOC00MzE5LWEyNDMtMDlmZGQ1YjRkNzEyNDEwZTdhYWQtMTZmMC00MTY5LWFmNjgtYzQ5NmRiOTRmYmY4%26acr_values%3Dtenant%253Dmonitoriza%26prompt%3Dlogin%26x-client-SKU%3DID_NET472%26x-client-ver%3D7.6.2.0%26suppressed_prompt%3Dlogin',
                        'image' => asset('images/tools/axesor.jpg'),
                    ],
                    [
                        'label' => 'Incofisa',
                        'url' => 'https://incofisa-digital.web.app/incofisadigital/auth/login',
                        'image' => asset('images/tools/incofisa.png'),
                    ],
                    [
                        'label' => 'Caixa',
                        'url' => 'https://loc26.caixabank.es/GPeticiones;WebLogicSession=1YvYZ9P4si6mlmZiPgbjyPGiP_gJIAjWNv3wsLDgRlvsL3cjV7U9!118285288!866939666',
                        'image' => asset('images/tools/caixabank.png'),
                    ],
                    [
                        'label' => 'Drive Financiaciones',
                        'url' => 'https://docs.google.com/spreadsheets/d/1fPr7brHdculz7zGGGL9kND306EvpaRrT/edit?gid=1992833401#gid=1992833401',
                        'image' => asset('images/tools/drive.png'),
                    ],
                    [
                        'label' => 'Caixa todas operaciones',
                        'url' => 'https://autos.caixabankpc.com/apw5/fncWebPrescriptores/VerTodasOperaciones.do',
                        'image' => asset('images/tools/caixabank.png'),
                    ],
                    [
                        'label' => 'BBVA Financiaciones',
                        'url' => 'https://operaciones.bbvaconsumerfinance.es/finanzianet/pro/vulcanize/index.html',
                        'image' => asset('images/tools/bbva.jpg'),
                    ],
                    [
                        'label' => 'Banco Santander',
                        'url' => 'https://www.bancosantander.es/particulares',
                        'image' => asset('images/tools/santander.png'),
                    ],
                    [
                        'label' => 'Pagos',
                        'url' => 'https://www.bbva.es/empresas.html',
                        'image' => asset('images/tools/bbva.jpg'),
                    ],
                    [
                        'label' => 'Wiuse',
                        'url' => 'https://wiuse.net/',
                        'image' => asset('images/tools/wiuse.png'),
                    ],
                    [
                        'label' => 'Caixabank',
                        'url' => 'https://www.caixabank.es/particular/home/particulares_es.html',
                        'image' => asset('images/tools/caixabank.png'),
                    ],
                    [
                        'label' => 'Excel Recambios',
                        'url' => 'https://docs.google.com/spreadsheets/d/181wJehtjfuXl0fS-Rhbnol4SS7tRJPGazu8IUJVpXj0/edit?gid=0#gid=0',
                        'image' => asset('images/tools/excel.png'),
                    ],
                    [
                        'label' => 'VISA',
                        'url' => 'https://docs.google.com/spreadsheets/d/11gJdeRYSWrRX7Uej5JK_g0gUOmD5vvSM/edit?gid=1895952355#gid=1895952355',
                        'image' => asset('images/tools/visa.jpg'),
                    ],
                    [
                        'label' => 'Microsoft Teams',
                        'url' => 'https://teams.microsoft.com/v2/',
                        'image' => asset('images/tools/teams.png'),
                    ],
                    [
                        'label' => $salesforceLabel,
                        'url' => $salesforceUrl,
                        'image' => asset('images/tools/salesforce.png'),
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
                    [
                        'label' => 'Coches de cortesía',
                        'url' => 'https://docs.google.com/spreadsheets/d/1_fiL4TyclqhOtkBijSqaxAcc8c6XmmSy/edit?pli=1&gid=590253332#gid=590253332',
                        'image' => asset('images/tools/coches-cortesia.png'),
                    ],
                    [
                        'label' => 'Citas garantías HR',
                        'url' => 'https://docs.google.com/spreadsheets/d/16uY7SOshvkNKOti7BfLfIgWkJNyNM__8/edit?pli=1&gid=2129562621#gid=2129562621',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Pólizas activas Caser OK',
                        'url' => 'https://docs.google.com/spreadsheets/d/1Q6iDEW_dhR47MwVR3t-omI-zV-c0W72x/edit?pli=1&gid=714492361#gid=714492361',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Envío documentación',
                        'url' => 'https://docs.google.com/spreadsheets/d/1ZN-ej468hjsZM-Aqb5Mgdk_hocXSkkcM/edit?pli=1&gid=1194646592#gid=1194646592',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Ventas caídas y excesos 2026',
                        'url' => 'https://docs.google.com/spreadsheets/d/1Ovm_KJr2JAumJ1KngBT3rYopFYDjk2W4/edit?pli=1&gid=36299908#gid=36299908',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Seguro coche cortesía',
                        'url' => 'https://docs.google.com/spreadsheets/d/15w1ELzuEQsG3zq79y2w_6t_BRD8uZjx8/edit?pli=1&gid=2118661665#gid=2118661665',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Salesforce',
                        'url' => $callCenterSalesforceUrl,
                        'image' => asset('images/tools/salesforce.png'),
                    ],
                    [
                        'label' => 'Onlogist',
                        'url' => 'https://portal.onlogist.com/#myBuynows',
                        'image' => asset('images/tools/onlogist.png'),
                    ],
                    [
                        'label' => 'FaciliteaCoches',
                        'url' => 'https://www.admin.faciliteacoches.com/login',
                        'image' => asset('images/tools/stock-facilitea.jpg'),
                    ],
                    [
                        'label' => 'Tickelia',
                        'url' => 'https://cloud.tickelia.com/web/#/login',
                        'image' => asset('images/tools/tickelia.png'),
                    ],
                    [
                        'label' => 'COC Online',
                        'url' => 'https://www.coc-online.com/es',
                        'image' => asset('images/tools/coc-online.jpg'),
                    ],
                    [
                        'label' => 'Euro COC',
                        'url' => 'https://www.eurococ.eu/it/coc-vin-verifica//?checkvin=VF3CU9HP0KY038844',
                        'image' => asset('images/tools/euro-coc.png'),
                    ],
                    [
                        'label' => 'Encheres VO',
                        'url' => 'https://pro.encheres-vo.com/portail.html',
                        'image' => asset('images/tools/encheres-vo.png'),
                    ],
                    [
                        'label' => 'UPS',
                        'url' => 'https://www.ups.com/track?loc=es_ES&requester=ST/',
                        'image' => asset('images/tools/ups.jpg'),
                    ],
                    [
                        'label' => 'Occident',
                        'url' => 'https://cliente.occident.com/overall-position',
                        'image' => asset('images/tools/occident.jpg'),
                    ],
                    [
                        'label' => 'Facilitea',
                        'url' => 'https://www.admin.faciliteacoches.com/admin/orders',
                        'image' => asset('images/tools/stock-facilitea.jpg'),
                    ],
                    [
                        'label' => 'ChatGPT',
                        'url' => 'https://chatgpt.com/',
                        'image' => asset('images/tools/chatgpt.png'),
                    ],
                        [
                            'label' => 'Stock Facilitea',
                            'url' => 'https://stockyleads.motorflash.com/login.php?urlBack=%2Findex.php',
                            'image' => asset('images/tools/stock-facilitea.jpg'),
                        ],
                    [
                        'label' => 'Enreach',
                        'url' => 'https://omnichannel.masvoz.es',
                        'image' => asset('images/tools/enreach.png'),
                    ],
                    [
                        'label' => 'GarantiAuto',
                        'url' => 'https://www.gsonline.es/login',
                        'image' => asset('images/tools/garantiauto.png'),
                    ],
                    [
                        'label' => 'Coches.net',
                        'url' => 'https://www.coches.net/',
                        'image' => asset('images/tools/cochesnet.jpg'),
                    ],
                    [
                        'label' => 'Norauto',
                        'url' => 'https://www.fleetvalidation.com/login?redirect=%2F1031%2F',
                        'image' => asset('images/tools/norauto.png'),
                    ],
                    [
                        'label' => 'Chat ServiceForm',
                        'url' => 'https://dash.serviceform.com/chat?sid=3466609922234647151187',
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

        $buttonSections = collect($buttonSections)
            ->map(function (array $section) use ($authUser, $salesforceLabel): array {
                if (($section['title'] ?? null) !== 'Herramientas generales') {
                    return $section;
                }

                $commercialButtonLabels = [
                    'DocuSign',
                    $salesforceLabel,
                    'Lendismart',
                    'My Mutua',
                    'Formación Comerciales',
                    'ServiceForm',
                ];

                $callCenterGeneralButtonLabels = [
                    'Salesforce',
                    'Salesforce comunidad',
                    'Facilitea',
                    'ChatGPT',
                    'Contact Center Motorflash',
                    'Microsoft Teams',
                    'Stock Facilitea',
                    'Enreach',
                    'GarantiAuto',
                    'Coches.net',
                    'Norauto',
                    'Chat ServiceForm',
                ];

                $rentingButtonLabels = [
                    'Rent2click',
                    'HR Renting',
                    'Flit2GO',
                    'Salesforce',
                    'Axesor',
                    'Incofisa',
                    'Canva',
                    'Google Drive',
                ];

                $logisticsButtonLabels = [
                    'Salesforce',
                    'Onlogist',
                    'FaciliteaCoches',
                    'Tickelia',
                    'COC Online',
                    'Euro COC',
                    'Encheres VO',
                    'UPS',
                    'Occident',
                ];

                $legalButtonLabels = [
                    'Citas garantías HR',
                    'Pólizas activas Caser OK',
                    'Envío documentación',
                    'Ventas caídas y excesos 2026',
                    'Seguro coche cortesía',
                ];

                $sparePartsGeneralButtonLabels = [
                    'Wiuse',
                    'Caixabank',
                    'Excel Recambios',
                    'VISA',
                ];

                $financingButtonLabels = [
                    'Caixa',
                    'Drive Financiaciones',
                    'Caixa todas operaciones',
                    'BBVA Financiaciones',
                    'Banco Santander',
                    'Pagos',
                ];

                $financingOtherResourcesLabels = [
                    'Occident',
                    'Soyou',
                    'Lendismart',
                    'BitGest PRO',
                ];

                $fixedGeneralButtonLabels = [
                    'Woffu',
                    'Web HR Motor',
                    'OneDrive',
                ];

                $filteredButtons = collect($section['buttons'] ?? [])
                    ->filter(fn (array $button) => in_array($button['label'] ?? null, ['OneDrive', 'Woffu', 'Web HR Motor'], true) || (
                        in_array($button['label'] ?? null, $commercialButtonLabels, true)
                        && app_user_has_any_role($authUser, [User::ROLE_COMMERCIAL, User::ROLE_STORE_MANAGER, User::ROLE_AREA_MANAGER])
                    ) || (
                        in_array($button['label'] ?? null, $callCenterGeneralButtonLabels, true)
                        && app_user_has_any_role($authUser, [User::ROLE_CALL_CENTER])
                    ) || (
                        ($button['label'] ?? null) === 'Tareas asignadas'
                        && app_user_has_any_role($authUser, [User::ROLE_MARKETING])
                    ) || (
                        in_array($button['label'] ?? null, ['Canva', 'ChatGPT', 'Envato', 'Trustpilot', 'Brevo'], true)
                        && app_user_has_any_role($authUser, [User::ROLE_MARKETING])
                    ) || (
                        in_array($button['label'] ?? null, ['Google Drive', 'Occident', 'Calcular IVA'], true)
                        && app_user_has_any_role($authUser, [User::ROLE_ADMINISTRATION])
                    ) || (
                        in_array($button['label'] ?? null, $legalButtonLabels, true)
                        && app_user_has_any_role($authUser, [User::ROLE_LEGAL])
                    ) || (
                        in_array($button['label'] ?? null, $sparePartsGeneralButtonLabels, true)
                        && app_user_has_any_role($authUser, [User::ROLE_SPARE_PARTS])
                    ) || (
                        in_array($button['label'] ?? null, $financingButtonLabels, true)
                        && app_user_has_any_role($authUser, [User::ROLE_FINANCING])
                    ) || (
                        in_array($button['label'] ?? null, $financingOtherResourcesLabels, true)
                        && app_user_has_any_role($authUser, [User::ROLE_FINANCING])
                    ) || (
                        in_array($button['label'] ?? null, $rentingButtonLabels, true)
                        && app_user_has_any_role($authUser, [User::ROLE_RENTING])
                    ) || (
                        in_array($button['label'] ?? null, $logisticsButtonLabels, true)
                        && app_user_has_any_role($authUser, [User::ROLE_LOGISTICS])
                    ))
                    ->unique(fn (array $button) => $button['label'] ?? null);

                $section['buttons'] = collect($fixedGeneralButtonLabels)
                    ->map(fn (string $label) => $filteredButtons->firstWhere('label', $label))
                    ->filter()
                    ->merge(
                        $filteredButtons->reject(fn (array $button) => in_array($button['label'] ?? null, $fixedGeneralButtonLabels, true))
                    )
                    ->values()
                    ->all();

                return $section;
            })
            ->all();

        $callCenterResourcesSection = app_user_has_any_role($authUser, [User::ROLE_CALL_CENTER])
            ? [
                'title' => 'Otros recursos',
                'buttons' => [
                    [
                        'label' => 'Coches de cortesía',
                        'url' => 'https://docs.google.com/spreadsheets/d/1_fiL4TyclqhOtkBijSqaxAcc8c6XmmSy/edit?pli=1&gid=590253332#gid=590253332',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Citas garantías HR',
                        'url' => 'https://docs.google.com/spreadsheets/d/16uY7SOshvkNKOti7BfLfIgWkJNyNM__8/edit?pli=1&gid=2129562621#gid=2129562621',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Pólizas activas Caser OK',
                        'url' => 'https://docs.google.com/spreadsheets/d/1Q6iDEW_dhR47MwVR3t-omI-zV-c0W72x/edit?pli=1&gid=714492361#gid=714492361',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Envío documentación',
                        'url' => 'https://docs.google.com/spreadsheets/d/1ZN-ej468hjsZM-Aqb5Mgdk_hocXSkkcM/edit?pli=1&gid=1194646592#gid=1194646592',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Ventas caídas y excesos 2026',
                        'url' => 'https://docs.google.com/spreadsheets/d/1Ovm_KJr2JAumJ1KngBT3rYopFYDjk2W4/edit?pli=1&gid=36299908#gid=36299908',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Seguro coche cortesía',
                        'url' => 'https://docs.google.com/spreadsheets/d/15w1ELzuEQsG3zq79y2w_6t_BRD8uZjx8/edit?pli=1&gid=2118661665#gid=2118661665',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                ],
            ]
            : null;

        $financingOtherResourcesSection = app_user_has_any_role($authUser, [User::ROLE_FINANCING])
            ? [
                'title' => 'Otros recursos',
                'buttons' => [
                    [
                        'label' => 'Occident',
                        'url' => 'https://cliente.occident.com/policies/27201113/B/fleets',
                        'image' => asset('images/tools/occident.jpg'),
                    ],
                    [
                        'label' => 'Soyou',
                        'url' => 'https://colabora.soyou.es/#/login',
                        'image' => asset('images/tools/soyou.jpg'),
                    ],
                    [
                        'label' => 'Lendismart',
                        'url' => 'https://hrmotor.lendismart.com/app/search-applications',
                        'image' => asset('images/tools/lendismart.png'),
                    ],
                    [
                        'label' => 'BitGest PRO',
                        'url' => 'https://bitgestprofesionales.com/mi-cuenta/mis-tramites',
                        'image' => asset('images/tools/bitgest.jpg'),
                    ],
                ],
            ]
            : null;

        $logisticsResourcesSection = app_user_has_any_role($authUser, [User::ROLE_LOGISTICS])
            ? [
                'title' => 'Hojas de cálculo',
                'buttons' => [
                    [
                        'label' => 'Transporte 24-25',
                        'url' => 'https://docs.google.com/spreadsheets/d/1U2fuEEPgY26ylsoVOrHbVOZ41pQ5N0VvW-MJIffkPI4/edit?pli=1&gid=0#gid=0',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Control Tickelia Internos',
                        'url' => 'https://docs.google.com/spreadsheets/d/1XG7LlCaao9ueoBpGibMGY3t1ka_btB_LnlWqTNuXzhk/edit?gid=0#gid=0',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Docs Extranjeras',
                        'url' => 'https://docs.google.com/spreadsheets/d/1qNlbHsxXN03UXzrWMozhizRSbkzjepN6/edit?gid=903951598#gid=903951598',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Envío Documentación',
                        'url' => 'https://docs.google.com/spreadsheets/d/1ZN-ej468hjsZM-Aqb5Mgdk_hocXSkkcM/edit?gid=1186450835#gid=1186450835',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Cargas Extranjeras',
                        'url' => 'https://docs.google.com/spreadsheets/d/1Y3RcOKWtw7WXYKKtEjDZx8mkz0Gw8OvA/edit?gid=138774793#gid=138774793',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Cargas Nacionales',
                        'url' => 'https://docs.google.com/spreadsheets/d/1gR3Lx9AdZxXVAdLVJYLmChrwsZdojYJD_r5UoLmXUCY/edit?gid=0#gid=0',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Pendientes Crear Salesforce',
                        'url' => 'https://docs.google.com/spreadsheets/d/1vT3jlH_xmBLBPUPrKQnjrzfBXt1QMFHfB0A6EUTG9mI/edit?gid=876360531#gid=876360531',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Vehículos Sin Ubicación',
                        'url' => 'https://docs.google.com/spreadsheets/d/1T3s-ftYq3MvLDU1iIYuo8QGGpGCOKYD5/edit?gid=1273017528#gid=1273017528',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Envío CMR',
                        'url' => 'https://docs.google.com/spreadsheets/d/1GViw3gWAOGdzgGX65z_U08x8IDmMjtYb/edit?gid=1796310716#gid=1796310716',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Control de Vehículos',
                        'url' => 'https://docs.google.com/spreadsheets/d/1x0qBp4M5S_oVDoLlq3EETurRNTlekRyp/edit?gid=1415421642#gid=1415421642',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                    [
                        'label' => 'Hoja de Transporte',
                        'url' => 'https://docs.google.com/spreadsheets/d/1nC6iC2m8kcUlDzaRzAadmUd9LrgoZd_f6fgGMHmc3tY/edit?gid=0#gid=0',
                        'image' => asset('images/tools/tareas-asignadas.png'),
                    ],
                ],
            ]
            : null;

        $sparePartsResourcesSection = app_user_has_any_role($authUser, [User::ROLE_SPARE_PARTS])
            ? [
                'title' => 'Recambios',
                'buttons' => [
                    [
                        'label' => 'Recambio Fácil',
                        'url' => 'https://www.recambiofacil.com/login/index',
                        'image' => asset('images/tools/recambio-facil.jpg'),
                    ],
                    [
                        'label' => 'Top Recambios',
                        'url' => 'https://toprecambios.com/profesional/login.php',
                        'image' => asset('images/tools/top-recambios.jpg'),
                    ],
                    [
                        'label' => 'Amazon',
                        'url' => 'https://www.amazon.es/',
                        'image' => asset('images/tools/amazon.webp'),
                    ],
                    [
                        'label' => 'partslink24',
                        'url' => 'https://www.partslink24.com/partslink24/user/login.do',
                        'image' => asset('images/tools/partslink24.jpg'),
                    ],
                    [
                        'label' => 'Aliexpress',
                        'url' => 'https://es.aliexpress.com/',
                        'image' => asset('images/tools/aliexpress.webp'),
                    ],
                    [
                        'label' => 'Taros Trade',
                        'url' => 'https://www.tarostrade.es/',
                        'image' => asset('images/tools/taros-trade.jpg'),
                    ],
                    [
                        'label' => 'Ovoko',
                        'url' => 'https://ovoko.es/buscar',
                        'image' => asset('images/tools/ovoko.png'),
                    ],
                    [
                        'label' => 'Ebay',
                        'url' => 'https://www.ebay.es/',
                        'image' => asset('images/tools/ebay.jpg'),
                    ],
                    [
                        'label' => 'Lyreco',
                        'url' => 'https://www.lyreco.com/webshop/SPSP/welcome?lc=SPSP',
                        'image' => asset('images/tools/lyreco.png'),
                    ],
                ],
            ]
            : null;

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

        return view('home', compact('buttonSections', 'otherResourcesSection', 'callCenterResourcesSection', 'sparePartsResourcesSection', 'financingOtherResourcesSection', 'logisticsResourcesSection', 'videos', 'homeLeaderboardEntries', 'homeLeaderboardMovements', 'itSupportUrl', 'magazine'));
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
