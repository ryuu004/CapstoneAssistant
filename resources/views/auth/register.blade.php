<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-gray-100 flex items-center justify-center min-h-screen p-4">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse delay-1000"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-xl opacity-10 animate-pulse delay-500"></div>
    </div>
    
    <div class="relative bg-gray-900/80 backdrop-blur-lg border border-gray-700/50 rounded-2xl shadow-2xl p-8 max-w-md w-full mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-100 to-gray-300 bg-clip-text text-transparent">Create Account</h1>
            <p class="text-gray-400 text-sm mt-1">Join us today and get started</p>
        </div>

        <main>
            <div data-controller="auth-visibility" data-auth-visibility-user-value="{{ Auth::check() }}">
                <div data-auth-visibility-target="authForm">
                    @if (session('status'))
                        <div class="bg-gradient-to-r from-green-500/10 to-emerald-500/10 border border-green-500/20 text-green-400 px-4 py-3 rounded-lg relative mb-6 backdrop-blur-sm" role="alert">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="block sm:inline text-sm">{{ session('status') }}</span>
                            </div>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="bg-gradient-to-r from-red-500/10 to-pink-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-lg relative mb-6 backdrop-blur-sm" role="alert">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <ul class="text-sm space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('register') }}" method="POST" class="space-y-6" data-action="submit->auth-visibility#submitRegisterForm">
                        @csrf
                        <div class="group">
                            <label for="register-name" class="block text-sm font-medium text-gray-300 mb-2 group-focus-within:text-purple-400 transition-colors">Full Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-500 group-focus-within:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <input type="text" name="name" id="register-name" 
                                    class="block w-full pl-10 pr-3 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-500 
                                           focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500/50 
                                           transition-all duration-200 hover:bg-gray-800/70" 
                                    placeholder="Enter your full name" required>
                            </div>
                        </div>

                        <div class="group">
                            <label for="register-email" class="block text-sm font-medium text-gray-300 mb-2 group-focus-within:text-purple-400 transition-colors">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-500 group-focus-within:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                    </svg>
                                </div>
                                <input type="email" name="email" id="register-email" 
                                    class="block w-full pl-10 pr-3 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-500 
                                           focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500/50 
                                           transition-all duration-200 hover:bg-gray-800/70" 
                                    placeholder="Enter your email" required>
                            </div>
                        </div>

                        <div class="group">
                            <label for="register-password" class="block text-sm font-medium text-gray-300 mb-2 group-focus-within:text-purple-400 transition-colors">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-500 group-focus-within:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input type="password" name="password" id="register-password" 
                                    class="block w-full pl-10 pr-3 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-100 placeholder-gray-500 
                                           focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500/50 
                                           transition-all duration-200 hover:bg-gray-800/70" 
                                    placeholder="Create a password" required>
                            </div>
                        </div>

                        <button type="submit" data-auth-visibility-target="registerButton" 
                            class="w-full py-3 px-4 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 
                                   text-white font-medium rounded-lg shadow-lg hover:shadow-purple-500/25 
                                   focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:ring-offset-2 focus:ring-offset-gray-900 
                                   transform hover:scale-[1.02] transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                            <span data-auth-visibility-target="registerButtonText" class="flex items-center justify-center">
                                Create Account
                                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                            </span>
                            <span data-auth-visibility-target="registerButtonSpinner" class="hidden">
                                <svg class="animate-spin h-5 w-5 text-white mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </form>

                    <div class="mt-6 pt-6 border-t border-gray-700/50">
                        <p class="text-center text-sm text-gray-400">
                            Already have an account? 
                            <a href="{{ route('login') }}" class="text-purple-400 hover:text-purple-300 font-medium transition-colors">
                                Sign in here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>