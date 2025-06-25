@extends('layouts.public')

@section('title', 'RDR Forecast: Prediksi Penjualan Akurat, Keputusan Bisnis Makin Tepat!')

@push('styles')
    <style>
        /* Custom Styles for Bootstrap version */
        body {
            font-family: 'Inter', sans-serif;

            background-color: #f8f9fa;

            overflow-x: hidden;
        }

        .hero-section {
            color: white;
            position: relative;
            overflow: hidden;

            padding-top: 9rem;
            padding-bottom: 8rem;
        }

        .hero-section::before {

            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("{{ asset('img/herosection.png') }}");

            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            z-index: -2;

        }

        .hero-section::after {
            /* Untuk lapisan gelap (overlay) */
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: -1;

        }

        .btn-custom-primary {
            background-color: #ffffff;
            color: #0043da;
            font-weight: bold;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;

        }

        .btn-custom-primary:hover {
            background-color: #0043da;
            color: #ffffff;
            border-color: #0043da;
        }

        .feature-card .feature-icon-wrapper {
            width: 70px;
            height: 70px;
            background-color: #0043da;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 67, 218, 0.2);
        }

        .team-member-card .team-member-avatar {
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            width: 158px;
            height: 158px;
        }

        .email-address {
            font-size: 0.85em;
            word-break: break-all;
        }

        .hero-section .display-4 {
            font-size: 2.8rem;
        }

        .hero-section .lead {
            font-size: 1.1rem;
            color: #f0f0f0 !important;
        }

        @media (min-width: 768px) {
            .hero-section .display-4 {
                font-size: 3.5rem;
            }

            .hero-section .lead {
                font-size: 1.25rem;
            }
        }

        /* Styles for scroll animation */
        .scroll-animate {
            opacity: 0;
            transform: translateY(50px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
            will-change: opacity, transform;
        }

        .scroll-animate.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
@endpush


@section('main-content')
    <header class="hero-section text-center">
        <div class="container position-relative" style="z-index: 1;">
            <a href="{{ url('/') }}" class="d-inline-block mb-4 h2 fw-bold text-white text-decoration-none">
                <span style="color: #0043da;">RDR Forecast</span>
            </a>
            <h1 class="display-4 fw-bold mb-4" style="color: #ffffff;">
                Prediksi Penjualan Akurat, Keputusan Bisnis Makin Tepat!
            </h1>
            <p class="lead mb-4 mx-auto" style="max-width: 700px;">
                Platform Sales Forecasting kami membantu Anda memahami tren penjualan, berinteraksi dengan pelanggan secara
                cerdas, dan menganalisis sentimen pasar untuk pertumbuhan bisnis yang berkelanjutan.
            </p>
            <div>
                @guest
                    <a href="{{ route('register') }}" class="btn btn-custom-primary btn-lg px-4 shadow">Mulai Sekarang</a>
                @else
                    <a href="{{ url('/home') }}" class="btn btn-custom-primary btn-lg px-4 shadow">Masuk ke Dashboard</a>
                @endguest
            </div>
        </div>
    </header>

    <section id="features" class="py-5 bg-light scroll-animate">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Fitur Unggulan Kami</h2>
                <p class="lead text-muted mx-auto" style="max-width: 600px;">
                    Jelajahi berbagai alat canggih yang kami sediakan untuk mengoptimalkan strategi penjualan Anda.
                </p>
            </div>

            <div class="row g-4">
                {{-- Feature 1: Analisis Penjualan Harian --}}
                <div class="col-md-6 col-lg-4 d-flex">
                    <div class="card feature-card flex-fill h-100 shadow-sm text-center p-4">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <h3 class="card-title h5 fw-semibold mb-3">Analisis Penjualan Harian</h3>
                            <p class="card-text text-muted">
                                Dapatkan wawasan mendalam dari data penjualan harian Anda. Visualisasikan tren, identifikasi
                                pola, dan buat keputusan berdasarkan data yang akurat.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Feature 2: Chatbot Cerdas --}}
                <div class="col-md-6 col-lg-4 d-flex">
                    <div class="card feature-card flex-fill h-100 shadow-sm text-center p-4">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-robot fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <h3 class="card-title h5 fw-semibold mb-3">Chatbot Cerdas</h3>
                            <p class="card-text text-muted">
                                Interaksi otomatis dengan pelanggan 24/7. Chatbot kami siap membantu menjawab pertanyaan,
                                memberikan rekomendasi, dan meningkatkan engagement.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Feature 3: Analisis Sentimen --}}
                <div class="col-md-6 col-lg-4 d-flex">
                    <div class="card feature-card flex-fill h-100 shadow-sm text-center p-4">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-smile-beam fa-2x"></i>
                        </div>
                        <div class="card-body p-0">
                            <h3 class="card-title h5 fw-semibold mb-3">Analisis Sentimen Pasar</h3>
                            <p class="card-text text-muted">
                                Pahami persepsi pelanggan terhadap produk atau layanan Anda. Analisis sentimen membantu Anda
                                merespons feedback pasar dengan lebih efektif.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="team" class="py-5 bg-white scroll-animate">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Tim Kami</h2>
                <p class="lead text-muted mx-auto" style="max-width: 600px;">
                    Perkenalkan orang-orang hebat di balik platform ini.
                </p>
            </div>
            <div class="row g-4 justify-content-center">
                {{-- Anggota Tim 1 --}}
                <div class="col-sm-6 col-md-4 col-lg-3 d-flex">
                    <div class="card team-member-card text-center p-4 shadow-sm flex-fill h-100">
                        <img src="{{ asset('img/member1.jpg') }}" alt="Foto M Rizki Awaluddin M"
                            onerror="this.onerror=null;this.src='https://placehold.co/150x150/E0E0E0/909090?text=Rizki';"
                            class="team-member-avatar mx-auto img-fluid"
                            style="width: 150px; height: 150px; margin-top: 1.2rem;">
                        <div class="card-body p-0">
                            <h4 class="card-title h6 fw-semibold mb-1 mt-3">M Rizki Awaluddin M</h4>
                            <p class="text-muted small mb-0"> <a
                                    href="mailto:muhammad.rizki.awaluddin.mubin.tik22@mhsw.pnj.ac.id"
                                    class="text-muted email-address">muhammad.rizki.awaluddin.mubin.tik22@mhsw.pnj.ac.id</a>
                            </p>
                        </div>
                    </div>
                </div>
                {{-- Anggota Tim 2 --}}
                <div class="col-sm-6 col-md-4 col-lg-3 d-flex">
                    <div class="card team-member-card text-center p-4 shadow-sm flex-fill h-100">
                        <img src="{{ asset('img/member2.png') }}" alt="Foto M Dzaky Naufal Asadel"
                            onerror="this.onerror=null;this.src='https://placehold.co/150x150/E8E8E8/A0A0A0?text=Dzaky';"
                            class="team-member-avatar mx-auto img-fluid" style="width: 170px; height: 170px;">
                        <div class="card-body p-0">
                            <h4 class="card-title h6 fw-semibold mb-1 mt-3">M Dzaky Naufal Asadel</h4>
                            <p class="text-muted small mb-0">
                                <a href="mailto:muhammad.dzaky.naufal.asadel.tik22@mhsw.pnj.ac.id"
                                    class="text-muted email-address">muhammad.dzaky.naufal.asadel.tik22@mhsw.pnj.ac.id</a>
                            </p>
                        </div>
                    </div>
                </div>
                {{-- Anggota Tim 3 --}}
                <div class="col-sm-6 col-md-4 col-lg-3 d-flex">
                    <div class="card team-member-card text-center p-4 shadow-sm flex-fill h-100">

                        <img src="{{ asset('img/member3.png') }}" alt="Foto Teuku Rafly Fahrezy"
                            onerror="this.onerror=null;this.src='https://placehold.co/150x150/F0F0F0/B0B0B0?text=Rafly';"
                            class="team-member-avatar mx-auto img-fluid"
                            style="width: 160px; height: 160px; margin-top: 0.6rem; ">
                        <div class="card-body p-0">
                            <h4 class="card-title h6 fw-semibold mb-1 mt-3">Teuku Rafly Fahrezy</h4>
                            <p class="text-muted small mb-0">
                                <a href="mailto:teuku.rafli.fahrezy.tik22@mhsw.pnj.ac.id"
                                    class="text-muted email-address">teuku.rafli.fahrezy.tik22@mhsw.pnj.ac.id</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const animatedSections = document.querySelectorAll('.scroll-animate');

            if ("IntersectionObserver" in window) {
                let observer = new IntersectionObserver((entries, observerInstance) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observerInstance.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1
                });

                animatedSections.forEach(section => {
                    observer.observe(section);
                });
            } else {

                function checkVisibility() {
                    animatedSections.forEach(section => {
                        const rect = section.getBoundingClientRect();
                        if (rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
                            rect.bottom >= 0) {
                            section.classList.add('is-visible');
                        }
                    });
                }
                window.addEventListener('scroll', checkVisibility);
                window.addEventListener('resize', checkVisibility);
                checkVisibility();
            }

            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetElement = document.querySelector(this.getAttribute('href'));
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
@endpush
