@extends('layouts.embedded')

@section('content')
    <div class="h-full w-full min-h-0 overflow-hidden">
        <iframe
            src="{{ $hrMotorUrl }}"
            title="Web HR Motor"
            class="h-full w-full border-0 bg-white"
            loading="eager"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen>
        </iframe>
    </div>
@endsection
