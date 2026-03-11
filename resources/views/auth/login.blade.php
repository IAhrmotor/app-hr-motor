<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - HR Motor</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="min-h-screen bg-cover bg-center bg-no-repeat"
    style="background-image: url('{{ asset('images/login/login-background.jpg') }}');"
>
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-lg">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold text-[#1F2944]">Iniciar sesión</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Accede a la plataforma de HR Motor
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

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Correo electrónico</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-[#E51A2E] focus:ring-2 focus:ring-[#E51A2E]/20"
                    >
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Contraseña</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-[#E51A2E] focus:ring-2 focus:ring-[#E51A2E]/20"
                    >
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input
                            type="checkbox"
                            name="remember"
                            class="rounded border-gray-300 text-[#E51A2E] focus:ring-[#E51A2E]"
                        >
                        Recordarme
                    </label>

                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-[#E51A2E] hover:underline">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <button
                    type="submit"
                    class="w-full cursor-pointer rounded-lg bg-[#E51A2E] px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                >
                    Entrar
                </button>
            </form>
        </div>
    </div>
</body>
</html>