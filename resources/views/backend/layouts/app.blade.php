<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - @yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Toastify JS -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <!-- Additional styles for backend -->
    <style>
        .sidebar {
            min-height: 100vh;
        }
        .main-content {
            flex: 1;
            padding: 20px;
        }
        /* Mobile menu toggle */
        .mobile-menu-button {
            display: none;
        }
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .sidebar.active {
            transform: translateX(0);
        }
        @media (max-width: 768px) {
            .mobile-menu-button {
                display: block;
            }
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 50;
                transform: translateX(-100%) !important;
                display: block !important;
            }
            .sidebar.active {
                transform: translateX(0) !important;
            }
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 40;
            }
            .overlay.active {
                display: block;
            }
            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            @if(App\Helpers\SettingsHelper::get('app_logo'))
                                <img src="{{ App\Helpers\SettingsHelper::get('app_logo') }}" alt="{{ config('app.name', 'Laravel') }}" 
                                    class="h-[20px] max-w-xs">
                            @else
                                <div class="w-12 h-20 bg-white/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9 9h-7z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center">
                        <!-- Mobile menu button -->
                        <button type="button" class="mobile-menu-button ml-4 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none" aria-controls="mobile-menu" aria-expanded="false">
                            <span class="sr-only">Open main menu</span>
                            <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                            @csrf
                            <button type="submit" class="ml-4 bg-white text-sm font-medium text-gray-500 hover:text-gray-700">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Overlay for mobile menu -->
        <div class="overlay hidden md:hidden" id="overlay"></div>

        <!-- Page Content -->
        <div class="flex">
            <!-- Sidebar -->
            <aside class="w-64 bg-white border-r border-gray-200 fixed h-full z-40 hidden md:block" id="sidebar">
                <div class="p-4">
                    <div class="flex justify-between items-center mb-4">
                        <button type="button" class="mobile-menu-close md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <ul class="mt-4 space-y-2">
                        <li>
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded {{ request()->routeIs('admin.dashboard') ? 'bg-gray-200' : '' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.social-accounts.index') }}" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded {{ request()->routeIs('admin.social-accounts*') ? 'bg-gray-200' : '' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Social Accounts
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.posts.index') }}" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded {{ request()->routeIs('admin.posts*') ? 'bg-gray-200' : '' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Posts
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.leads.index') }}" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded {{ request()->routeIs('admin.leads*') ? 'bg-gray-200' : '' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Leads
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.engagements.index') }}" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded {{ request()->routeIs('admin.engagements*') ? 'bg-gray-200' : '' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905a3.61 3.61 0 01-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                </svg>
                                Engagements
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.automation-rules.index') }}" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded {{ request()->routeIs('admin.automation-rules*') ? 'bg-gray-200' : '' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Automation Rules
                            </a>
                        </li>

                        @if(Auth::user() && Auth::user()->isAdmin())
                        <li>
                            <a href="{{ route('admin.monitoring.index') }}" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded {{ request()->routeIs('admin.monitoring*') ? 'bg-gray-200' : '' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <span class="flex items-center">
                                    Performance Monitoring
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.settings.index') }}" class="flex items-center p-2 text-gray-700 hover:bg-gray-100 rounded {{ request()->routeIs('admin.settings*') ? 'bg-gray-200' : '' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span class="flex items-center">
                                    Settings
                                </span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </aside>

            <!-- Main content -->
            <main class="main-content md:ml-64 flex-1 p-4 md:p-6">
                @yield('content')
            </main>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const mobileMenuButton = document.querySelector('.mobile-menu-button');
                const mobileMenuClose = document.querySelector('.mobile-menu-close');
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('overlay');
                
                function openMobileMenu() {
                    console.log('Opening mobile menu');
                    sidebar.classList.add('active');
                    sidebar.classList.remove('hidden');
                    overlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
                
                function closeMobileMenu() {
                    console.log('Closing mobile menu');
                    sidebar.classList.remove('active');
                    sidebar.classList.add('hidden');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
                
                mobileMenuButton.addEventListener('click', openMobileMenu);
                mobileMenuClose.addEventListener('click', closeMobileMenu);
                overlay.addEventListener('click', closeMobileMenu);
                
                // Close menu when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    if (window.innerWidth <= 768 && 
                        !sidebar.contains(event.target) && 
                        !mobileMenuButton.contains(event.target) &&
                        sidebar.classList.contains('active')) {
                        closeMobileMenu();
                    }
                });
                
                // Update menu on window resize
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 768) {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });
            
            // Toast notification helper
            window.showToast = function(message, type = 'info') {
                const toastConfig = {
                    info: {
                        background: '#3B82F6',
                        gravity: 'topRight',
                        position: 'right',
                        stopOnFocus: false,
                        duration: 3000
                    },
                    success: {
                        background: '#10B981',
                        gravity: 'topRight',
                        position: 'right',
                        stopOnFocus: false,
                        duration: 3000
                    },
                    error: {
                        background: '#EF4444',
                        gravity: 'topRight',
                        position: 'right',
                        stopOnFocus: false,
                        duration: 5000
                    },
                    warning: {
                        background: '#F59E0B',
                        gravity: 'topRight',
                        position: 'right',
                        stopOnFocus: false,
                        duration: 4000
                    }
                };
                
                Toastify({
                    text: message,
                    ...toastConfig[type] || toastConfig.info
                }).showToast();
            };
        </script>
        
        <!-- Flash Messages Handler -->
        @if(session()->has('success') || session()->has('error') || session()->has('warning') || session()->has('info'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    @if(session()->has('success'))
                        window.showToast('{{ session('success') }}', 'success');
                    @endif
                    
                    @if(session()->has('error'))
                        window.showToast('{{ session('error') }}', 'error');
                    @endif
                    
                    @if(session()->has('warning'))
                        window.showToast('{{ session('warning') }}', 'warning');
                    @endif
                    
                    @if(session()->has('info'))
                        window.showToast('{{ session('info') }}', 'info');
                    @endif
                });
            </script>
        @endif
        
        @stack('scripts')
    </div>
</body>
</html>