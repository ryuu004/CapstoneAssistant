<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-gray-100 flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
    <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
        <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row">
            <div class="text-[13px] leading-[20px] flex-1 p-6 pb-12 lg:p-20 bg-gray-800 text-gray-100 rounded-lg shadow-none">
                <h1 class="mb-1 font-medium">Authentication</h1>

                <div data-controller="auth-visibility" data-auth-visibility-user-value="{{ Auth::check() }}">
                    <div data-auth-visibility-target="authForm">
                        @if (session('status'))
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <span class="block sm:inline">{{ session('status') }}</span>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <p class="text-center text-gray-300 mb-4">
                            <a href="{{ route('login') }}" class="text-blue-400 hover:underline">Login</a> or
                            <a href="{{ route('register') }}" class="text-blue-400 hover:underline">Register</a>
                        </p>
                    </div>

                    @if (Auth::check())
                        <div class="mt-8">
                            <p class="text-center text-gray-300">You are logged in as {{ Auth::user()->name }}.</p>
                            <form action="{{ route('logout') }}" method="POST" class="mt-4" data-controller="logout">
                                @csrf
                                <button type="submit" data-action="logout#logout" data-logout-target="logoutButton" class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                    <span data-logout-target="logoutButtonText">Logout</span>
                                    <span data-logout-target="logoutButtonSpinner" class="hidden">
                                        <svg class="animate-spin h-5 w-5 text-white mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</body>
</html>