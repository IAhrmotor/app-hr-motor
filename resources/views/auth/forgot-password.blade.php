<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - HR Motor</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-cover bg-center bg-no-repeat"
    style="background-image: url('{{ asset('images/login/login-background.jpg') }}');">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="mb-6 flex justify-center">
                <img src="{{ asset('images/logo-horizontal.svg') }}" alt="HR Motor" class="h-12 w-auto">
            </div>

            <div class="w-full rounded-2xl bg-white p-8 shadow-lg">
                <div class="mb-8 text-center">
                    <h1 class="text-2xl font-bold text-brand-secondary">Recuperar contraseña</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        Introduce tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
                    </p>
                </div>

                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-green-100 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-gray-700">
                            Correo electrónico
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full cursor-pointer rounded-lg bg-brand-primary px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                    >
                        Enviar enlace de recuperación
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    <a href="{{ route('login') }}" class="font-medium text-brand-primary hover:underline">
                        Volver al inicio de sesión
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>