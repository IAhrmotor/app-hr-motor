<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $buttons = [
        [
            'label' => 'DocuSign',
            'url' => 'https://account.docusign.com/oauth/auth?response_type=code&scope=all%20click.manage%20me_profile%20room_forms%20room_fields%20inproductcommunication_read%20data_explorer_signing_insights%20notary_read%20notary_write%20search_read%20search_write%20webforms_manage%20dtr%20valmod_manage%20spring_read%20spring_write%20signature&client_id=2CC56DC9-4BCD-4B55-8AB0-8BA60BAE1065&redirect_uri=https://apps.docusign.com/authenticate&state=%7b%22widgetId%22:%22%40ds/send%22%2c%22xsrfToken%22:%22IYIogBvwZtz%2BEZviKPqyJlr915WEM8vmFZ14xMfBM5jq%2BBofLnLQDzRb37YARzOUNCSgjxu5e3ub3V0hoZVtzDbLaz7JvwviWui6HrM8DIybiaUP6kTN162yJKtVehy4Vm5nUroRdbTTg0cV3qpGirh%2BH3owkhvZb9GPMPNPPCE%3D%22%2c%22redirectUri%22:%22/send/authentication?back%3D%252Fhome%22%2c%22authTxnId%22:%2217f86b96-dd9d-4f8d-8d26-f741b689b5c2%22%7d',
            'description' => 'Acceso al portal donde se firman documentos',
        ],
        [
            'label' => 'Google Drive',
            'url' => 'https://drive.google.com',
            'description' => 'Documentación compartida',
        ],
        [
            'label' => 'Web de HR Motor',
            'url' => 'https://www.hrmotor.com/',
            'description' => 'Web de HR Motor',
        ],
        [
            'label' => 'Woffu',
            'url' => 'https://hrmotor.woffu.com/v2/personal/dashboard/user',
            'description' => 'Plataforma de gestión de fichajes',
        ],
        [
            'label' => 'Salesforce comunidad',
            'url' => 'https://hrmotor.my.site.com/hrmotorcommunity/s/login/?ec=302&startURL=%2Fhrmotorcommunity%2Fs%2F',
            'description' => 'Nuestro CRM',
        ],
        [
            'label' => 'Lendismart',
            'url' => 'https://hrmotor.lendismart.com/login',
            'description' => 'No se ni lo que es',
        ],
        [
            'label' => 'My Mutua',
            'url' => 'https://access.mutua.es/auth/realms/mymutua/protocol/openid-connect/auth?response_type=code&client_id=appseguros-front-canal-mediacion&scope=openid&state=kmbjXkQ7kJGvwZLQYHuo6lrnl1g7xDf-l2dCRIdr04M%3D&redirect_uri=https://www.mymutua.es/front-canal-mediacion/login/oauth2/code/sso&nonce=L0XFsW7iFaW1ZhX6dGXDl-tQm1y89v-5n7mKoEBgpv4',
            'description' => 'Seguros de la Mutua',
        ],
        [
            'label' => 'WhatsApp Web',
            'url' => 'https://web.whatsapp.com/',
            'description' => 'Chat en directo',
        ],
        [
            'label' => 'Formación Comerciales HR Motor',
            'url' => 'https://formacion.hrmotor.com/my/courses.php',
            'description' => 'Plataforma de formación interna',
        ],
    ];

    return view('home', compact('buttons'));
});