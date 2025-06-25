@extends('layouts.admin')

@section('main-content')
    {{-- Analisis Sentimen --}}
    <div class="col-lg-12 mb-2">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Analisis Sentimen</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Card Sentimen Positif --}}
                    <div class="col-lg-4 col-md-6 mb-1">
                        <div class="card sentiment-card positive-card shadow-sm h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                                <div class="text-uppercase text-muted mb-1" style="font-size: 1.0rem;">Sentimen Positif</div>
                                <div class="h4 font-weight-bold text-success mb-0">
                                    {{ number_format($jumlahPositif ?? 0, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card Sentimen Negatif --}}
                    <div class="col-lg-4 col-md-6 mb-1">
                        <div class="card sentiment-card negative-card shadow-sm h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                                <div class="text-uppercase text-muted mb-1" style="font-size: 1.0rem;">Sentimen Negatif
                                </div>
                                <div class="h4 font-weight-bold text-danger mb-0">
                                    {{ number_format($jumlahNegatif ?? 0, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card Sentimen Netral --}}
                    <div class="col-lg-4 col-md-12 mb-1">
                        <div class="card sentiment-card neutral-card shadow-sm h-100">
                            <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                                <div class="text-uppercase text-muted mb-1" style="font-size: 1.0rem;">Sentimen Netral</div>
                                <div class="h4 font-weight-bold text-warning mb-0">
                                    {{ number_format($jumlahNetral ?? 0, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <small class="text-muted">
                    Showing data for Sentiment Analysis │ Last updated:
                    {{ $sentimentLastUpdateDisplay ?? 'N/A' }}
                </small>
            </div>
        </div>

    {{-- Input Komentar --}}
    <div class="row">
        <div class="col-lg-12 mb-2">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Sentimen Analisis Input Komentar</h6>
                </div>
                <div class="card-body">
                    <form id="sentimenForm">
                        @csrf
                        <div class="form-group">
                            <label for="review_text">Tulis Komentar:</label>
                            <textarea class="form-control" name="review_text" id="review_text" rows="4" required minlength="3"></textarea>
                            <div class="invalid-feedback">
                                Komentar minimal 3 karakter.
                            </div>
                        </div>
                        <button type="submit" id="btnAnalisis" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm d-none" id="analisisSpinner" role="status"
                                aria-hidden="true"></span>
                            Analisis Sentimen
                        </button>
                    </form>

                    <div class="mt-4 d-none" id="hasilBox">
                        <h5>Prediksi Sentimen: <span id="prediksiLabel" class="font-weight-bold"></span></h5>
                        <p>Komentar Anda: <em id="originalCommentText"></em></p>
                        <button class="btn btn-success" id="btnYes">
                            <span class="spinner-border spinner-border-sm d-none" id="simpanSpinner" role="status"
                                aria-hidden="true"></span>
                            Simpan Hasil Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript --}}
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            let predictedLabel = '';
            let originalComment = '';

            const sentimenForm = document.getElementById('sentimenForm');
            const reviewTextarea = document.getElementById('review_text');
            const btnAnalisis = document.getElementById('btnAnalisis');
            const analisisSpinner = document.getElementById('analisisSpinner');
            const hasilBox = document.getElementById('hasilBox');
            const prediksiLabelSpan = document.getElementById('prediksiLabel');
            const originalCommentSpan = document.getElementById('originalCommentText');
            const btnYes = document.getElementById('btnYes');
            const simpanSpinner = document.getElementById('simpanSpinner');

            sentimenForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (!sentimenForm.checkValidity()) {
                    sentimenForm.classList.add('was-validated');
                    return;
                }
                sentimenForm.classList.remove('was-validated');

                const review = reviewTextarea.value;
                originalComment = review;
                analisisSpinner.classList.remove('d-none');
                btnAnalisis.disabled = true;
                hasilBox.classList.add('d-none');

                try {
                    const response = await fetch("{{ route('sentimen.predict') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            review_text: review
                        })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        predictedLabel = data.label_sentimen
                            .toLowerCase();


                        prediksiLabelSpan.innerText = predictedLabel;
                        originalCommentSpan.innerText = data.original_comment || review;

                        prediksiLabelSpan.classList.remove('text-success', 'text-danger', 'text-warning',
                            'text-primary');
                        if (predictedLabel === 'positif') {
                            prediksiLabelSpan.classList.add('text-success');
                        } else if (predictedLabel === 'negatif') {
                            prediksiLabelSpan.classList.add('text-danger');
                        } else if (predictedLabel === 'netral') {
                            prediksiLabelSpan.classList.add('text-warning');
                        } else {
                            prediksiLabelSpan.classList.add('text-primary');
                        }

                        hasilBox.classList.remove('d-none');
                    } else {
                        let errorMessage = data.error || 'Gagal mendapatkan prediksi sentimen.';
                        if (data.details && typeof data.details === 'object') {
                            errorMessage += ` (Detail: ${data.details.message || JSON.stringify(data.details)})`;
                        } else if (data.details) {
                            errorMessage += ` (Detail: ${data.details})`;
                        }
                        Swal.fire({
                            title: 'Error Prediksi!',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'Oke'
                        });
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    Swal.fire({
                        title: 'Koneksi Error!',
                        text: 'Tidak dapat terhubung ke server untuk prediksi. Periksa koneksi internet Anda.',
                        icon: 'error',
                        confirmButtonText: 'Oke'
                    });
                } finally {

                    analisisSpinner.classList.add('d-none');
                    btnAnalisis.disabled = false;
                }
            });

            btnYes.addEventListener('click', async function() {
                if (!originalComment || !predictedLabel) {
                    Swal.fire('Data Tidak Lengkap', 'Tidak ada komentar atau prediksi untuk disimpan.', 'warning');
                    return;
                }

                simpanSpinner.classList.remove('d-none');
                btnYes.disabled = true;

                try {
                    const response = await fetch("{{ route('sentimen.save') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            review_text: originalComment,
                            label_sentimen: predictedLabel
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: data.message || 'Komentar berhasil disimpan!',
                            icon: 'success',
                            confirmButtonText: 'Oke'
                        }).then(() => {
                            reviewTextarea.value = '';
                            hasilBox.classList.add('d-none');
                            predictedLabel = '';
                            originalComment = '';
                            sentimenForm.classList.remove('was-validated');
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal Menyimpan!',
                            text: data.error || 'Terjadi kesalahan saat menyimpan data.',
                            icon: 'error',
                            confirmButtonText: 'Oke'
                        });
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    Swal.fire({
                        title: 'Koneksi Error!',
                        text: 'Tidak dapat terhubung ke server untuk menyimpan. Periksa koneksi internet Anda.',
                        icon: 'error',
                        confirmButtonText: 'Oke'
                    });
                } finally {

                    simpanSpinner.classList.add('d-none');
                    btnYes.disabled = false;
                }
            });

            reviewTextarea.addEventListener('input', function() {
                if (reviewTextarea.value.length < 3) {
                    reviewTextarea.classList.add('is-invalid');
                } else {
                    reviewTextarea.classList.remove('is-invalid');
                    reviewTextarea.classList.add('is-valid');
                }
            });

            reviewTextarea.addEventListener('focus', function() {
                reviewTextarea.classList.remove('is-invalid', 'is-valid');
                sentimenForm.classList.remove('was-validated');
            });
        </script>
    @endpush
@endsection
