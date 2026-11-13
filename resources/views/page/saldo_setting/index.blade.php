@extends('layouts.app')
@section('title','Saldo Awal Setting')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Master Pengaturan Saldo</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Konfigurasi Metode Saldo dan Perlakuan Tahun Buku</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid py-3" id="saldo-setting-page">
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Filter Akun</h3>
        </div>

        <div class="card-body">
            <form id="formFilterSaldo"
                  method="GET"
                  action="{{ route('akuntansi.saldo-setting.index') }}">

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Entitas</label>

                        <select id="filterEntitas"
                                name="entitas_id"
                                class="form-select">
                            <option value="">Pilih Entitas</option>

                            @foreach($entitas as $item)
                                <option value="{{ $item->id }}"
                                    @selected($entitasId == $item->id)>
                                    {{ $item->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">
                            Pencarian Akun GL
                        </label>

                        <input type="text"
                               id="filterSearch"
                               name="search"
                               class="form-control"
                               value="{{ $search }}"
                               placeholder="Nomor atau nama akun">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit"
                                id="btnFilterSaldo"
                                class="btn btn-primary w-100">
                            <i class="ti ti-search me-1"></i>
                            Tampilkan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="saldo-results">
        @if($entitasId)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Daftar Akun Transaksi
                    </h3>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter table-hover">
                        <thead>
                            <tr>
                                <th>No. Akun</th>
                                <th>Nama Akun</th>
                                <th>Saldo Normal</th>
                                <th>Metode Saldo</th>
                                <th>Perlakuan Tahun</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($akuns as $akun)
                                @php
                                    $formId = 'setting-' . $akun->id;
                                    $terkunci = (bool) $akun->memiliki_saldo;
                                @endphp

                                <tr id="row-akun-{{ $akun->id }}">
                                    <td>
                                        <strong>{{ $akun->no_akun }}</strong>
                                    </td>

                                    <td>{{ $akun->nama }}</td>

                                    <td>
                                        {{ ucfirst($akun->saldo_normal) }}
                                    </td>

                                    <td>
                                        <select name="metode_saldo"
                                                form="{{ $formId }}"
                                                class="form-select form-select-sm"
                                                @disabled($terkunci)>
                                            <option value="">Pilih Metode</option>

                                            <option value="akun"
                                                @selected($akun->metode_saldo === 'akun')>
                                                Per Akun
                                            </option>

                                            <option value="partner"
                                                @selected($akun->metode_saldo === 'partner')>
                                                Per Partner
                                            </option>
                                        </select>
                                    </td>

                                    <td>
                                        <select name="perlakuan_tahun_buku"
                                                form="{{ $formId }}"
                                                class="form-select form-select-sm"
                                                @disabled($terkunci)>
                                            <option value="">Pilih Perlakuan</option>

                                            <option value="carry_forward"
                                                @selected($akun->perlakuan_tahun_buku === 'carry_forward')>
                                                Carry Forward
                                            </option>

                                            <option value="reset_tahunan"
                                                @selected($akun->perlakuan_tahun_buku === 'reset_tahunan')>
                                                Reset Tahunan
                                            </option>
                                        </select>
                                    </td>

                                    <td class="status-setting">
                                        @if($terkunci)
                                            <span class="badge bg-warning text-dark">
                                                Ada Saldo Existing
                                            </span>
                                        @elseif($akun->metode_saldo && $akun->perlakuan_tahun_buku)
                                            <span class="badge bg-success">
                                                Terkonfigurasi
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Belum Diatur
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <form id="{{ $formId }}"
                                              action="{{ route('akuntansi.saldo-setting.update') }}"
                                              method="POST"
                                              class="form-setting-saldo d-inline">

                                            @csrf

                                            <input type="hidden"
                                                   name="entitas_id"
                                                   value="{{ $entitasId }}">

                                            <input type="hidden"
                                                   name="akun_gl_id"
                                                   value="{{ $akun->id }}">

                                            <button type="submit"
                                                    class="btn btn-primary btn-sm btn-simpan"
                                                    @disabled($terkunci)>
                                                <i class="ti ti-device-floppy me-1"></i>
                                                Simpan
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="7"
                                        class="text-center text-muted py-4">
                                        Tidak ada akun transaksi ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                        <div class="text-muted small">
                            Menampilkan
                            {{ $akuns->firstItem() ?? 0 }}
                            -
                            {{ $akuns->lastItem() ?? 0 }}
                            dari
                            {{ $akuns->total() }}
                            akun
                        </div>

                        <div class="saldo-pagination">
                            {{ $akuns->appends(request()->only(['entitas_id', 'search']))->links('pagination::bootstrap-5') }}
                        </div>

                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                Akun dengan saldo existing dikunci sementara.
                Konfigurasi awalnya harus direkonsiliasi
                sebelum dapat diubah atau diaktifkan.
            </div>
        @endif
    </div>
</div>
@endsection
@section('js')
<script>
$(function () {

    const indexUrl = @json(route('akuntansi.saldo-setting.index'));

    let searchTimer = null;
    let filterRequest = null;

    // SweetAlert2 notification
    function notify(icon, title, text = '') {
        Swal.fire({
            icon: icon,
            title: title,
            text: text,
            timer: icon === 'success' ? 1800 : undefined,
            showConfirmButton: icon !== 'success'
        });
    }

    // AJAX filter and pagination
    function loadSaldo(url) {

        if (filterRequest) {
            filterRequest.abort();
        }

        const $button = $('#btnFilterSaldo');
        const $result = $('#saldo-results');

        $button.prop('disabled', true);
        $result.css('opacity', '0.5');

        filterRequest = $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',

            success: function (html) {
                const $document = $('<div>').append(
                    $.parseHTML(html)
                );

                const $newContent = $document.find('#saldo-results');

                if (!$newContent.length) {
                    notify('error', 'Gagal', 'Format halaman tidak sesuai.');
                    return;
                }

                $result.html($newContent.html());
                window.history.replaceState({}, '', url);
            },

            error: function (xhr, status) {
                if (status === 'abort') return;

                notify(
                    'error',
                    'Gagal Memuat Data',
                    'Terjadi kesalahan saat mengambil daftar akun.'
                );
            },

            complete: function (xhr, status) {
                if (status !== 'abort') {
                    $button.prop('disabled', false);
                    $result.css('opacity', '1');
                }
                filterRequest = null;
            }
        });
    }

    // Filter submit
    $('#formFilterSaldo').on('submit', function (e) {
        e.preventDefault();

        const entitasId = $('#filterEntitas').val();

        if (!entitasId) {
            notify('warning', 'Pilih Entitas', 'Silakan pilih entitas.');
            return;
        }

        const params = $(this).serialize();
        loadSaldo(indexUrl + '?' + params);
    });

    // Automatic filtering when entity changes
    $('#filterEntitas').on('change', function () {
        if ($(this).val()) {
            $('#formFilterSaldo').trigger('submit');
        } else {
            $('#saldo-results').empty();
        }
    });

    // Search debounce
    $('#filterSearch').on('input', function () {
        clearTimeout(searchTimer);

        if (!$('#filterEntitas').val()) return;

        searchTimer = setTimeout(function () {
            $('#formFilterSaldo').trigger('submit');
        }, 500);
    });

    // AJAX pagination
    $(document).on(
        'click',
        '#saldo-results .pagination a',
        function (e) {
            e.preventDefault();

            const url = $(this).attr('href');

            if (url) {
                loadSaldo(url);
            }
        }
    );

    // AJAX save per account
    $(document).on(
        'submit',
        '.form-setting-saldo',
        function (e) {
            e.preventDefault();

            const $form = $(this);
            const formId = $form.attr('id');
            const $row = $form.closest('tr');

            const $metode = $(
                '[name="metode_saldo"][form="' + formId + '"]'
            );

            const $perlakuan = $(
                '[name="perlakuan_tahun_buku"][form="' + formId + '"]'
            );

            const metode = $metode.val();
            const perlakuan = $perlakuan.val();

            if (!metode || !perlakuan) {
                notify(
                    'warning',
                    'Konfigurasi Belum Lengkap',
                    'Pilih metode saldo dan perlakuan tahun buku.'
                );
                return;
            }

            const $button = $form.find('.btn-simpan');

            $button.prop('disabled', true)
                .html('Menyimpan...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: $form.find('[name="_token"]').val(),
                    entitas_id: $form.find('[name="entitas_id"]').val(),
                    akun_gl_id: $form.find('[name="akun_gl_id"]').val(),
                    metode_saldo: metode,
                    perlakuan_tahun_buku: perlakuan
                },

                success: function (response) {
                    
                    if (response.success === false) {
                        notify(
                            'error',
                            'Gagal',
                            response.message || 'Gagal menyimpan data.'
                        );
                        return;
                    }

                    $row.find('.status-setting').html(
                        '<span class="badge bg-success">' +
                        'Terkonfigurasi</span>'
                    );

                    notify(
                        'success',
                        'Berhasil',
                        response.message || 'Pengaturan saldo disimpan.'
                    );
                },

                error: function (xhr) {
                    console.log(xhr);
                    let message = 'Terjadi kesalahan saat menyimpan.';

                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors || {};

                        const messages = Object.values(errors).flat();

                        if (messages.length) {
                            message = messages.join('\n');
                        }
                    } else if (xhr.responseJSON?.message) {
                        message = xhr.responseJSON.message;
                    }

                    notify('error', 'Gagal Menyimpan', message);
                },

                complete: function () {
                    $button.prop('disabled', false)
                        .html(
                            '<i class="ti ti-device-floppy me-1"></i>' +
                            'Simpan'
                        );
                }
            });
        }
    );

});
</script>
@endsection