@extends('layouts.app')

@section('title', 'Informes de reseñas')

@section('content')
    @include('reviews.partials.reports-hub', [
        'heading' => 'Informes',
        'description' => 'Accede a los distintos informes de reseñas disponibles.',
        'cards' => [
            [
                'title' => 'Informes mensuales',
                'description' => 'Resumen detallado por mes.',
                'url' => $monthlyReportsUrl,
            ],
            [
                'title' => 'Informes semestrales',
                'description' => 'Resumen comparativo por semestre.',
                'url' => $semiannualReportsUrl,
            ],
        ],
    ])
@endsection
