@extends('layouts.navbar-only')

@section('content')
    <div class="h-full w-full min-h-0 overflow-hidden">
        <iframe
            src="{{ $chatBetaUrl }}"
            title="Chat beta"
            class="block h-full w-full border-0 bg-white"
            loading="eager"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen>
        </iframe>
    </div>
@endsection
