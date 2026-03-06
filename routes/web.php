<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $buttons = [
        [
            'label' => 'Portal RRHH',
            'url' => 'https://example.com/rrhh',
            'description' => 'Acceso al portal de recursos humanos',
        ],
        [
            'label' => 'Google Drive',
            'url' => 'https://drive.google.com',
            'description' => 'Documentación compartida',
        ],
        [
            'label' => 'Soporte IT',
            'url' => 'https://example.com/soporte',
            'description' => 'Abrir incidencias técnicas',
        ],
        [
            'label' => 'Manual interno',
            'url' => 'https://example.com/manual',
            'description' => 'Consulta procesos y guías',
        ],
    ];

    return view('home', compact('buttons'));
});