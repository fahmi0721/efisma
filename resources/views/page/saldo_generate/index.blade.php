@extends('layouts.app')
@section('title','Generate Saldo Awal Bulanan')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Generate Saldo Awal Bulanan</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Generate Saldo Awal Bulanan</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="text-muted">
                Preview perhitungan saldo berdasarkan master
                pengaturan akun.
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Parameter Generate</h3>
        </div>

        <div class="card-body">
            <form id="formPreviewSaldo"
                  method="POST"
                  action="{{ route('akuntansi.saldo-generate.preview') }}">

                @csrf

                <div class="row g-3">

                    <!-- Periode -->
                    <div class="col-md-4">
                        <label class="form-label required">
                            Periode
                        </label>

                        <input type="month"
                               id="periode"
                               name="periode"
                               value="{{ old('periode', now()->format('Y-m')) }}"
                               class="form-control"
                               required>
                    </div>

                    <!-- Entitas -->
                    <div class="col-md-4">
                        <label class="form-label required">
                            Entitas
                        </label>

                        <select id="entitas_id"
                                name="entitas_id"
                                class="form-select select2"
                                required>

                            <option value="">Pilih Entitas</option>

                            @foreach($entitas as $item)
                                <option value="{{ $item->id }}"
                                    @selected(old('entitas_id') == $item->id)>
                                    {{ $item->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Akun GL -->
                    <div class="col-md-4">
                        <label class="form-label required">
                            Akun GL
                        </label>

                        <select id="akun_gl_id"
                                name="akun_gl_id"
                                class="form-select select2"
                                required>

                            <option value="">Pilih Akun GL</option>

                            @foreach($akuns as $akun)
                                <option value="{{ $akun->id }}"
                                    @selected(old('akun_gl_id') == $akun->id)>
                                    {{ $akun->no_akun }} - {{ $akun->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="mt-3">
                    <button type="submit"
                            id="btnPreviewSaldo"
                            class="btn btn-primary">

                        <i class="ti ti-calculator me-1"></i>
                        Preview Perhitungan
                    </button>

                    @canAccess('saldo-generate.generate')
                        <button
                            type="button"
                            id="btnGenerateSaldo"
                            class="btn btn-success"
                            data-url="{{ route('akuntansi.saldo-generate.generate') }}"
                            disabled>

                            <i class="fas fa-cogs mr-1"></i>
                            Generate Saldo
                        </button>
                    @endcanAccess

                    <button type="button"
                            id="btnResetSaldo"
                            class="btn btn-outline-secondary ms-2">

                        <i class="ti ti-refresh me-1"></i>
                        Reset Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading -->
    <div id="loadingPreview"
         class="card mb-3 d-none">
        <div class="card-body text-center py-4">
            <div class="spinner-border text-primary mb-2"
                 role="status"></div>

            <div class="text-muted">
                Sedang menghitung preview saldo...
            </div>
        </div>
    </div>

    <!-- Hasil Preview AJAX -->
    <div id="hasilPreviewSaldo"
         class="mb-3"
         aria-live="polite"></div>

    

</div>
@endsection

@push('scripts')
<script>

$(function () {

    $(document).on('click', '#btnGenerateSaldo', function () {

        const $button = $(this);

        if ($button.prop('disabled')) return;

        const periode = $('#periode').val();
        const entitasId = $('#entitas_id').val();
        const akunId = $('#akun_gl_id').val();

        if (!periode || !entitasId || !akunId) {
            Swal.fire({
                icon: 'warning',
                title: 'Parameter Belum Lengkap',
                text: 'Pilih periode, entitas, dan akun GL.'
            });
            return;
        }

        Swal.fire({
            title: 'Generate Saldo Bulanan?',
            html: `
                <p>Periode: <strong>${$('<div>').text(periode).html()}</strong></p>
                <p>
                    Saldo bulan berjalan akan dihitung
                    berdasarkan mutasi buku besar.
                </p>
                <p class="text-warning">
                    Opening bulan berikutnya akan dibuat otomatis.
                </p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Generate',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745'
        }).then(function (result) {

            if (!result.isConfirmed) return;

            const originalHtml = $button.html();

            $button.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm mr-1"></span>' +
                'Generating...'
            );

            $.ajax({
                url: $button.data('url'),
                type: 'POST',
                dataType: 'json',
                headers: {
                    'Accept': 'application/json'
                },
                data: {
                    _token: "{{ csrf_token() }}",
                    periode: periode,
                    entitas_id: entitasId,
                    akun_gl_id: akunId
                },

                success: function (response) {

                    if (response.success === false) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Generate Gagal',
                            text: response.message ||
                                'Tidak dapat melakukan generate.'
                        });
                        return;
                    }

                    $('#hasilPreviewSaldo').empty();

                    Swal.fire({
                        icon: 'success',
                        title: 'Generate Berhasil',
                        text: response.message +
                            ' Opening periode berikutnya telah dibuat.'
                    });

                },

                error: function (xhr) {

                    const errors = xhr.responseJSON?.errors || {};

                    const message =
                        Object.values(errors).flat().join('\n') ||
                        xhr.responseJSON?.message ||
                        'Terjadi kesalahan saat generate saldo.';

                    Swal.fire({
                        icon: 'error',
                        title: 'Generate Gagal',
                        text: message
                    });
                },

                complete: function () {
                    $button.html(originalHtml);

                    // Tetap disabled, wajib Preview ulang.
                    $button.prop('disabled', true);
                }
            });
        });
    });

});

$(function () {

    // ====================================================
    // SELECT2
    // ====================================================

    $('#entitas_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Pilih Entitas',
        allowClear: true
    });

    $('#akun_gl_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Pilih Akun GL',
        allowClear: true
    });

    // ====================================================
    // HELPER
    // ====================================================

    let previewRequest = null;

    function clearPreview() {
        $('#hasilPreviewSaldo').empty();
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: message
        });
    }

    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    // Jika parameter berubah, kosongkan preview lama
    $('#periode, #entitas_id, #akun_gl_id').on(
        'change',
        function () {
            clearPreview();
            $('#btnGenerateSaldo').prop('disabled', true);
        }
    );

    // ====================================================
    // PREVIEW AJAX
    // ====================================================

    $('#formPreviewSaldo').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);

        const periode = $('#periode').val();
        const entitasId = $('#entitas_id').val();
        const akunGlId = $('#akun_gl_id').val();

        if (!periode || !entitasId || !akunGlId) {
            Swal.fire({
                icon: 'warning',
                title: 'Parameter Belum Lengkap',
                text: 'Pilih periode, entitas dan akun GL.'
            });
            return;
        }

        if (previewRequest) {
            previewRequest.abort();
        }

        const $button = $('#btnPreviewSaldo');

        $button.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-1"></span>' +
            'Menghitung...'
        );

        $('#loadingPreview').removeClass('d-none');
        clearPreview();

        previewRequest = $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },

            success: function (response) {

                if (response.success === false) {
                    showError(
                        response.message ||
                        'Preview perhitungan gagal.'
                    );
                    return;
                }

                // Kontrak respons JSON:
                // {
                //   success: true,
                //   message: "...",
                //   html: "<div>...</div>"
                // }

                if (typeof response.html === 'string') {
                    // HTML harus berasal dari partial Blade
                    // server yang terpercaya, bukan input mentah.
                    $('#hasilPreviewSaldo').html(response.html);
                    $('#btnGenerateSaldo').prop('disabled', false);

                } else {
                    $('#hasilPreviewSaldo').html(`
                        <div class="alert alert-success">
                            <i class="ti ti-check me-1"></i>
                            ${escapeHtml(
                                response.message ||
                                'Preview berhasil dihitung.'
                            )}
                        </div>
                    `);
                    $('#btnGenerateSaldo').prop('disabled', true);
                }
            },

            error: function (xhr, status) {

                if (status === 'abort') return;

                let message =
                    'Terjadi kesalahan saat menghitung saldo.';

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    const messages = Object.values(errors).flat();

                    if (messages.length) {
                        message = messages.join('\n');
                    }

                } else if (xhr.responseJSON?.message) {
                    message = xhr.responseJSON.message;
                }

                showError(message);
            },

            complete: function (xhr, status) {
                if (status !== 'abort') {
                    $('#loadingPreview').addClass('d-none');

                    $button.prop('disabled', false).html(
                        '<i class="ti ti-calculator me-1"></i>' +
                        'Preview Perhitungan'
                    );
                }

                previewRequest = null;
            }
        });

    });

    // ====================================================
    // RESET FILTER
    // ====================================================

    $('#btnResetSaldo').on('click', function () {

        if (previewRequest) {
            previewRequest.abort();
        }

        $('#periode').val(
            @json(now()->format('Y-m'))
        );

        $('#entitas_id').val(null).trigger('change');
        $('#akun_gl_id').val(null).trigger('change');

        clearPreview();
        $('#loadingPreview').addClass('d-none');

        $('#btnPreviewSaldo')
            .prop('disabled', false)
            .html(
                '<i class="ti ti-calculator me-1"></i>' +
                'Preview Perhitungan'
            );
    });

});
</script>
@endpush