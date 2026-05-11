@extends('layouts.app')

@section('title', 'Informes mensuales')

@section('content')
    @include('reviews.partials.reports-hub', [
        'heading' => 'Informes mensuales',
        'description' => 'Portal de informes del periodo mensual.',
        'cards' => [
            [
                'title' => 'Comparativa delegaciones',
                'description' => 'Tabla consolidada por delegación y mes.',
                'url' => $comparisonUrl,
            ],
            [
                'title' => 'Volver a informes',
                'description' => 'Regresa al selector principal de informes.',
                'url' => $hubUrl,
                'cta' => 'Volver',
            ],
            [
                'title' => 'Informes semestrales',
                'description' => 'Accede al informe semestral.',
                'url' => $semiannualUrl,
            ],
        ],
    ])
@endsection
