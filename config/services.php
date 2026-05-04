<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'salesforce' => [
        'client_id' => env('SALESFORCE_CLIENT_ID'),
        'client_secret' => env('SALESFORCE_CLIENT_SECRET'),
        'authorize_url' => env('SALESFORCE_AUTHORIZE_URL', 'https://login.salesforce.com/services/oauth2/authorize'),
        'token_url' => env('SALESFORCE_TOKEN_URL', 'https://login.salesforce.com/services/oauth2/token'),
        'redirect_uri' => env('SALESFORCE_REDIRECT_URI'),
        'scope' => env('SALESFORCE_SCOPE', 'api refresh_token offline_access'),
        'leaderboard_soql' => env(
            'SALESFORCE_LEADERBOARD_SOQL',
            'SELECT OwnerId ownerId, Owner.Name ownerName, COUNT(Id) totalSales FROM Opportunity WHERE OPO_CAS_Contrato_CV_firmado__c = true AND Fecha_firma_contrato__c = THIS_MONTH AND StageName NOT IN (\'Cerrada ganada\', \'Cerrada perdida\') AND Gestion_de_venta__c = false AND RecordType.Name IN (\'Venta\', \'Cambio\') GROUP BY OwnerId, Owner.Name ORDER BY COUNT(Id) DESC, Owner.Name ASC'
        ),
        'purchase_leaderboard_soql' => env(
            'SALESFORCE_PURCHASE_LEADERBOARD_SOQL',
            'SELECT OwnerId ownerId, Owner.Name ownerName, COUNT(Id) totalPurchases FROM Opportunity WHERE CreatedDate = THIS_MONTH AND StageName NOT IN (\'Cerrada ganada\', \'Cerrada perdida\') AND Gestion_de_venta__c = false AND RecordType.Name IN (\'Compra\') GROUP BY OwnerId, Owner.Name ORDER BY COUNT(Id) DESC, Owner.Name ASC'
        ),
        'vehicle_hot_leaderboard_soql' => env(
            'SALESFORCE_VEHICLE_HOT_LEADERBOARD_SOQL',
            'SELECT LEA_BUS_Vehiculo_de_interes__c vehicleId, LEA_BUS_Vehiculo_de_interes__r.Name vehicleName, LEA_BUS_Vehiculo_de_interes__r.NombreComercial__c vehicleCommercialName, LEA_BUS_Vehiculo_de_interes__r.PRO_TEX_Matricula__c vehiclePlate, COUNT(Id) totalLeads FROM Lead WHERE LEA_BUS_Vehiculo_de_interes__c != null AND LEA_BUS_Vehiculo_de_interes__r.PRO_SEL_Estado__c = \'Disponible\' AND LEA_BUS_Vehiculo_de_interes__r.PRO_CAS_Garantia__c = true GROUP BY LEA_BUS_Vehiculo_de_interes__c, LEA_BUS_Vehiculo_de_interes__r.Name, LEA_BUS_Vehiculo_de_interes__r.NombreComercial__c, LEA_BUS_Vehiculo_de_interes__r.PRO_TEX_Matricula__c ORDER BY COUNT(Id) DESC, LEA_BUS_Vehiculo_de_interes__r.Name ASC LIMIT 10'
        ),
        'vehicle_cold_leaderboard_soql' => env(
            'SALESFORCE_VEHICLE_COLD_LEADERBOARD_SOQL',
            'SELECT LEA_BUS_Vehiculo_de_interes__c vehicleId, LEA_BUS_Vehiculo_de_interes__r.Name vehicleName, LEA_BUS_Vehiculo_de_interes__r.NombreComercial__c vehicleCommercialName, LEA_BUS_Vehiculo_de_interes__r.PRO_TEX_Matricula__c vehiclePlate, COUNT(Id) totalLeads FROM Lead WHERE LEA_BUS_Vehiculo_de_interes__c != null AND LEA_BUS_Vehiculo_de_interes__r.PRO_SEL_Estado__c = \'Disponible\' AND LEA_BUS_Vehiculo_de_interes__r.PRO_CAS_Garantia__c = true AND LEA_BUS_Vehiculo_de_interes__r.Fecha_Listo_para_publicar__c <= N_DAYS_AGO:30 GROUP BY LEA_BUS_Vehiculo_de_interes__c, LEA_BUS_Vehiculo_de_interes__r.Name, LEA_BUS_Vehiculo_de_interes__r.NombreComercial__c, LEA_BUS_Vehiculo_de_interes__r.PRO_TEX_Matricula__c ORDER BY COUNT(Id) ASC, LEA_BUS_Vehiculo_de_interes__r.Name ASC LIMIT 10'
        ),
        'excluded_leaderboard_user_ids' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('SALESFORCE_EXCLUDED_LEADERBOARD_USER_IDS', '0057R00000B2SGHQA3'))
        ))),
    ],

    'google_business_profile' => [
        'client_id' => env('GOOGLE_BUSINESS_PROFILE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_BUSINESS_PROFILE_CLIENT_SECRET'),
        'authorize_url' => env('GOOGLE_BUSINESS_PROFILE_AUTHORIZE_URL', 'https://accounts.google.com/o/oauth2/v2/auth'),
        'token_url' => env('GOOGLE_BUSINESS_PROFILE_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'redirect_uri' => env('GOOGLE_BUSINESS_PROFILE_REDIRECT_URI'),
        'scope' => env('GOOGLE_BUSINESS_PROFILE_SCOPE', 'https://www.googleapis.com/auth/business.manage'),
        'account_group_name' => env('GOOGLE_BUSINESS_PROFILE_ACCOUNT_GROUP_NAME', 'Tiendas HR Motor'),
    ],

];
