@extends('layouts.app')

@section('title', 'Informes semestrales')

@section('content')
    @include('reviews.partials.reports-hub', [
        'heading' => 'Informes semestrales',
        'description' => 'Portal de informes del periodo semestral.',
        'cards' => [
            [
                'title' => 'Resumen semestral',
                'description' => 'Bloque pendiente de desarrollo.',
                'url' => null,
                'cta' => 'Próximamente',
            ],
            [
                'title' => 'Volver a informes',
                'description' => 'Regresa al selector principal de informes.',
                'url' => $hubUrl,
                'cta' => 'Volver',
            ],
            [
                'title' => 'Informes mensuales',
                'description' => 'Accede al informe mensual.',
                'url' => $monthlyUrl,
            ],
        ],
    ])
@endsection
