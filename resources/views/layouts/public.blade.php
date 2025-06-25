<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Selamat Datang di Website Kami')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link href="{{ asset('img/favicon.png') }}" rel="icon" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        xintegrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            body {
                font-family: 'Instrument Sans', sans-serif;
                margin: 0;
                background-color: #f7fafc;
                color: #1a202c;
                font-size: 15px;
            }

            .container {
                max-width: 1200px;
                margin: auto;
                padding: 20px;
            }

            .header-nav {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.9rem 1.5rem;
                background-color: white;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                position: relative;
            }

            .app-logo {
                display: flex;
                align-items: center;
                text-decoration: none;
            }

            .app-logo img {
                height: 28px;
                margin-right: 8px;
            }

            .app-logo-text {
                font-size: 1rem;
                font-weight: 600;
                color: #0043da;
            }

            .app-logo:hover {
                text-decoration: none;
                color: #0043da;
            }


            .nav-links-container {
                display: flex;
                align-items: center;
            }

            .header-nav .nav-links-container a {
                margin-left: 0.9rem;
                text-decoration: none;
                color: #2d3748;
                font-weight: 500;
                font-size: 0.9rem;
                padding: 0.4rem 0;
            }

            .header-nav .nav-links-container a:hover {
                color: #0043da;
            }

            .navbar-toggler {
                display: none;
                background: none;
                border: none;
                font-size: 1.3rem;
                cursor: pointer;
                padding: 0.5rem;
                color: #2d3748;
            }

            .btn-custom-primary {
                display: inline-block;
                padding: 0.65rem 1.3rem;
                background-color: #0043da;
                color: white;
                text-decoration: none;
                border-radius: 0.375rem;
                font-weight: 500;
                font-size: 0.875rem;
                transition: background-color 0.3s ease;
            }

            .btn-custom-primary:hover {
                background-color: #0043da;
            }

            .hero-section {
                text-align: center;
                padding: 3rem 1rem;
            }

            .hero-section h1 {
                font-size: 2rem;
                font-weight: 600;
                margin-bottom: 0.8rem;
                color: #1a202c;
            }

            .hero-section p {
                font-size: 1rem;
                color: #4a5568;
                margin-bottom: 1.8rem;
            }

            @media (max-width: 768px) {
                .app-logo img {
                    height: 24px;
                }

                .app-logo-text {
                    font-size: 0.9rem;
                    text-decoration: none;
                }

                .navbar-toggler {
                    display: block;
                }

                .nav-links-container {
                    display: none;
                    flex-direction: column;
                    position: absolute;
                    top: 100%;
                    left: 0;
                    width: 100%;
                    background-color: white;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    z-index: 1000;
                    padding: 0.5rem 0;
                }

                .nav-links-container.active {
                    display: flex;
                }

                .header-nav .nav-links-container a {
                    margin-left: 0;
                    padding: 0.7rem 1.5rem;
                    width: 100%;
                    text-align: left;
                    border-top: 1px solid #f0f0f0;
                    font-size: 0.9rem;
                }

                .header-nav .nav-links-container a:first-child {
                    border-top: none;
                }

                .hero-section h1 {
                    font-size: 1.75rem;
                }

                .hero-section p {
                    font-size: 0.95rem;
                }
            }
        </style>
    @endif
    @stack('styles')
</head>

<body class="antialiased">
    <div class="header-nav">
        <div>
            {{-- Logo sekarang berisi gambar dan teks --}}
            <a href="{{ route('welcome') }}" class="app-logo">
                <img src="{{ asset('img/favicon.png') }}">
                <span class="app-logo-text">{{ env('APP_NAME') }}</span>
            </a>
        </div>

        <button class="navbar-toggler" type="button" aria-label="Toggle navigation">
            <i class="fas fa-bars"></i>
        </button>

        <div class="nav-links-container">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/home') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}">Register</a>
                    @endif
                @endauth
            @endif
            <a href="{{ route('news') }}">Berita</a>
            <a href="{{ route('welcome') }}">Home</a>
        </div>
    </div>

    @yield('main-content')

    <footer
        style="text-align: center; padding: 1.8rem; margin-top: 1.8rem; margin-bottom: 0.8rem; color: #718096; font-size: 0.8rem;">
        &copy; {{ now()->year }} Maintained by Kelompok Forecast.
    </footer>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>

    <script>
        // JavaScript untuk toggle navbar di mobile
        document.addEventListener('DOMContentLoaded', function() {
            const toggler = document.querySelector('.navbar-toggler');
            const navLinksContainer = document.querySelector('.nav-links-container');

            if (toggler && navLinksContainer) {
                toggler.addEventListener('click', function() {
                    navLinksContainer.classList.toggle('active');
                });
            }
        });
    </script>

    @stack('scripts')
</body>

</html>
