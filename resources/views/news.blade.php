@extends('layouts.public')

@section('title', 'Berita Terkini')

@section('main-content')
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        /* === Custom Primary Colors === */
        .text-custom-primary {
            color: #0043da !important;
        }

        .bg-custom-primary {
            background-color: #0043da !important;
            border-color: #0043da !important;
            color: #fff !important;
        }

        .bg-custom-primary:hover,
        .bg-custom-primary:focus {
            background-color: #0032a8 !important;
            border-color: #0032a8 !important;
            color: #fff !important;
        }

        /* === Page Title === */
        .page-title-custom {
            font-weight: 700;
            color: #2c3e50;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }

        /* === Base News Card Styling === */
        .news-card-base {
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
            background-color: #ffffff;
            overflow: hidden;
        }

        .news-card-base:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12) !important;
        }

        .news-card-base .card-body {
            display: flex;
            flex-direction: column;
            padding: 1.25rem;
        }

        .news-card-base .card-title a {
            text-decoration: none;
            color: #0043da;
            transition: color 0.2s ease;
        }

        .news-card-base .card-title a:hover {
            color: #0032a8 !important;
            text-decoration: underline;
        }

        .news-card-base .meta-text {
            color: #5a6268;
            font-size: 0.825rem;
            margin-bottom: 0.75rem;
        }

        .news-card-base .meta-text .fas,
        .news-card-base .meta-text .far {
            margin-right: 0.3rem;
            color: #0043da;
        }

        .news-card-base .read-more-btn {
            font-weight: 500;
            margin-top: auto;
            align-self: flex-start;
            font-size: 0.85rem;
            padding: 0.45rem 0.9rem;
        }

        /* === Styling untuk Kartu Berita dalam Grid (news-card-item) === */
        .news-card-item .card-img-top-custom {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #e9ecef;
        }

        .news-card-item .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .news-card-item .excerpt-text {
            font-size: 0.9rem;
            color: #495057;
            line-height: 1.55;
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        .news-card-linkable-wrapper {
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .news-card-item .card-img-top-custom {
                height: 180px;
            }
        }

        @media (max-width: 767.98px) {
            .page-title-custom {
                font-size: 1.75rem;
            }

            .news-card-item .card-img-top-custom {
                height: 200px;
            }

            .news-card-item .card-title {
                font-size: 1.05rem;
            }
        }
    </style>

    <div class="container py-4 py-md-5">
        <div class="d-sm-flex align-items-center justify-content-between mb-4 pb-2">
            <h1 class="h3 mb-0 page-title-custom">Berita Terbaru</h1>
        </div>

        @php
            $beritaItems = $beritaItems ?? [];
            $apiError = $apiError ?? null;
            $originalBeritaItems = $beritaItems;

            if ($originalBeritaItems instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                $allItemsInGrid = collect($originalBeritaItems->items());
            } elseif (is_array($originalBeritaItems)) {
                $allItemsInGrid = collect($originalBeritaItems);
            } elseif ($originalBeritaItems instanceof \Illuminate\Support\Collection) {
                $allItemsInGrid = $originalBeritaItems;
            } else {
                $allItemsInGrid = collect();
            }
        @endphp

        @if (!empty($apiError))
            <div class="alert alert-danger text-center shadow-sm rounded-lg" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i> {{ $apiError }}
            </div>
        @endif

        @if (session('status'))
            <div class="alert alert-success border-left-success alert-dismissible fade show shadow-sm rounded-lg"
                role="alert">
                {{ session('status') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if ($allItemsInGrid->isNotEmpty())
            <div class="row">
                @foreach ($allItemsInGrid as $item)
                    <div class="col-lg-4 col-md-6 mb-4 d-flex">
                        <div class="news-card-base news-card-item">
                            {{-- Gambar Direct Link Ke Sumber Asli --}}
                            <a href="{{ $item['sumber_link'] ?? '#' }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ $item['gambar'] ?? 'https://placehold.co/300x200/E0E0E0/7F8C8D?text=Berita' }}"
                                    class="card-img-top-custom" alt="{{ $item['judul'] ?? 'Gambar berita' }}"
                                    onerror="this.onerror=null;this.src='https://placehold.co/300x200/E0E0E0/7F8C8D?text=Berita';this.alt='Gambar tidak dapat dimuat';">
                            </a>
                            <div class="card-body">
                                <h5 class="card-title">
                                    {{-- Judul Direct Link Ke Sumber Asli --}}
                                    <a href="{{ $item['sumber_link'] ?? '#' }}" target="_blank" rel="noopener noreferrer">
                                        {{ Str::limit($item['judul'] ?? 'Judul tidak tersedia', 55) }}
                                    </a>
                                </h5>
                                <p class="meta-text">
                                    <i class="far fa-calendar-alt"></i> {{ $item['tanggal'] ?? 'Tanggal tidak diketahui' }}
                                    @if (isset($item['penulis']) && !empty($item['penulis']))
                                        <span class="mx-2 d-none d-sm-inline">|</span>
                                        <i class="far fa-user d-none d-sm-inline"></i> <span
                                            class="d-none d-sm-inline">{{ Str::limit($item['penulis'], 20) }}</span>
                                    @endif
                                </p>
                                <p class="excerpt-text">
                                    {{ Str::limit($item['kutipan'] ?? 'Kutipan tidak tersedia.', 100) }}
                                </p>

                                <a href="{{ $item['sumber_link'] ?? '#' }}" target="_blank" rel="noopener noreferrer"
                                    class="btn bg-custom-primary read-more-btn">
                                    Selengkapnya <i class="fas fa-external-link-alt fa-sm ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif (empty($apiError))
            <div class="col-12">
                <div class="alert alert-info text-center shadow-sm rounded-lg" role="alert">
                    <i class="fas fa-info-circle mr-2"></i> Belum ada berita yang tersedia saat ini. Silakan kembali lagi
                    nanti.
                </div>
            </div>
        @endif

        {{-- Paginasi --}}
        @if ($originalBeritaItems instanceof \Illuminate\Pagination\LengthAwarePaginator && $originalBeritaItems->hasPages())
            <div class="row mt-4 mt-md-5">
                <div class="col-12 d-flex justify-content-center">
                    {{ $originalBeritaItems->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
