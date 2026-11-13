@extends('layouts.app')
@section('title','Generate Saldo Awal Bulanan Massal')

@push('styles')
<style>
    #modalDetailGenerateMassal .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    #modalDetailGenerateMassal .modal-title {
        flex: 1;
        margin-right: 15px;
    }

    #modalDetailGenerateMassal .modal-header .close {
        position: static;
        margin: -1rem -1rem -1rem auto;
        padding: 1rem;
        float: none;
        opacity: 0.85;
        color: #fff !important;
        font-size: 1.5rem;
        line-height: 1;
        background: transparent;
        border: 0;
        box-shadow: none;
    }

    #modalDetailGenerateMassal .modal-header .close:hover {
        opacity: 1;
    }
</style>
@endpush

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Generate Saldo Awal Bulanan Massal</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Generate Saldo Awal Bulanan Massl</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid py-3">

    <div class="mb-3">
        <h2 class="mb-1">Generate Saldo Massal</h2>
        <p class="text-muted">
            Generate saldo bulanan seluruh akun transaksi
            berdasarkan entitas dan periode.
        </p>
    </div>

    <div class="card card-outline card-primary mb-3">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-cogs mr-2"></i>
                Parameter Generate
            </h3>
        </div>

        <div class="card-body">
            <form id="formGenerateMassal"
                  action="{{ route('akuntansi.saldo-generate-massal.store') }}"
                  method="POST">

                @csrf

                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Periode</label>
                            <input
                                type="month"
                                name="periode"
                                id="periodeMassal"
                                class="form-control"
                                value="{{ now()->format('Y-m') }}"
                                required>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Entitas</label>
                            <select
                                name="entitas_id"
                                id="entitasMassal"
                                class="form-control select2"
                                required>

                                <option value="">Pilih Entitas</option>

                                @foreach($entitas as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-group w-100">
                            <button type="submit"
                                    id="btnGenerateMassal"
                                    class="btn btn-success btn-block">
                                <i class="fas fa-play mr-1"></i>
                                Generate
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="cardProgressMassal"
         class="card card-outline card-info d-none mb-3">

        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-tasks mr-2"></i>
                Monitoring Generate Massal
            </h3>
        </div>

        <div class="card-body">
            <div class="row text-center mb-3">
                <div class="col-md-3">
                    <div class="small text-muted">Total Akun</div>
                    <h4 id="totalAkunMassal">0</h4>
                </div>

                <div class="col-md-3">
                    <div class="small text-muted">Berhasil</div>
                    <h4 class="text-success" id="completedMassal">0</h4>
                </div>

                <div class="col-md-3">
                    <div class="small text-muted">Dilewati</div>
                    <h4 class="text-info" id="skippedMassal">0</h4>
                </div>

                <div class="col-md-3">
                    <div class="small text-muted">Gagal</div>
                    <h4 class="text-danger" id="failedMassal">0</h4>
                </div>

            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <strong id="statusMassal">Pending</strong>
                </div>

                <div class="col-md-4">
                    Diproses: <strong id="processedMassal">0</strong>
                </div>

                <div class="col-md-4">
                    Gagal: <strong id="failedMassal2">0</strong>
                </div>
            </div>

            <div class="progress" style="height:25px">
                <div id="progressMassal"
                     class="progress-bar bg-info progress-bar-striped"
                     role="progressbar"
                     style="width:0%"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-valuenow="0">
                    0%
                </div>
            </div>

            <div id="infoProgressMassal"
                 class="small text-muted mt-2">
                Menunggu proses queue...
            </div>

            <div class="mt-3 text-right">
                <button
                    type="button"
                    id="btnDetailGenerateMassal"
                    class="btn btn-primary d-none"
                    data-toggle="modal"
                    data-target="#modalDetailGenerateMassal">

                    <i class="fas fa-list-alt mr-1"></i>
                    Lihat Detail Generate
                </button>
            </div>

        </div>
    </div>

    

</div>

<div class="modal fade"
     id="modalDetailGenerateMassal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="judulDetailGenerateMassal"
     aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-scrollable"
         role="document">

        <div class="modal-content">

            <div class="modal-header bg-primary align-items-center">

                <h5 class="modal-title text-white mb-0"
                    id="judulDetailGenerateMassal">
                    <i class="fas fa-list-alt mr-2"></i>
                    Detail Hasil Generate Saldo Massal
                </h5>

                <button
                    type="button"
                    class="close text-white ml-auto btnCloseDetailMassal"
                    aria-label="Tutup">

                    <span aria-hidden="true">&times;</span>
                </button>

            </div>

            <div class="modal-body">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Entitas:</strong>
                        <div id="detailNamaEntitas">-</div>
                    </div>

                    <div class="col-md-4">
                        <strong>Periode:</strong>
                        <div id="detailPeriode">-</div>
                    </div>

                    <div class="col-md-4">
                        <strong>Status:</strong>
                        <div id="detailStatus">-</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="filterStatusDetail">
                        Filter Status Akun
                    </label>

                    <select id="filterStatusDetail"
                            class="form-control"
                            style="max-width:250px">
                        <option value="">Semua Status</option>
                        <option value="failed">Failed</option>
                        <option value="completed">Completed</option>
                        <option value="skipped">Skipped</option>
                        <option value="processing">Processing</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>

                <div class="table-responsive">
                    <table id="tableDetailGenerateMassal"
                           class="table table-bordered table-striped table-sm"
                           style="width:100%">

                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="12%">No Akun</th>
                                <th>Nama Akun GL</th>
                                <th width="12%">Status</th>
                                <th width="10%">Baris</th>
                                <th>Keterangan / Error</th>
                                <th width="15%">Waktu Selesai</th>
                            </tr>
                        </thead>

                        <tbody></tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary btnCloseDetailMassal">

                    <i class="fas fa-times mr-1"></i>
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {

    $('#entitasMassal').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    let monitorTimer = null;
    let sedangMonitor = false;

    const statusUrlTemplate = @json(
        route('akuntansi.saldo-generate-massal.status', ['id' => '__ID__'])
    );

    function monitorProgress(id) {

        if (sedangMonitor) return;
        sedangMonitor = true;

        const url = statusUrlTemplate.replace('__ID__', id);

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',

            success: function (res) {

                const progress = Math.min(
                    100,
                    Math.max(0, Number(res.progress || 0))
                );
                const completed = Number(res.completed || 0);
                const skipped = Number(res.skipped || 0);
                const failed = Number(res.failed || 0);

                const processed = completed + skipped + failed;
                
                $('#statusMassal').text(res.status);
                $('#processedMassal').text(
                    processed + ' / ' + res.total_akun
                );
                $('#failedMassal').text(failed);

                $('#progressMassal')
                    .css('width', progress + '%')
                    .attr('aria-valuenow', progress)
                    .text(progress + '%');
                $('#totalAkunMassal').text(res.total_akun);
                $('#completedMassal').text(completed);
                $('#skippedMassal').text(skipped);
                $('#failedMassal2').text(failed);

                const semuaAkunDiproses =
                    Number(res.total_akun) > 0 &&
                    Number(res.remaining) === 0;

                const statusAkhir = [
                    'completed',
                    'partial',
                    'failed',
                    'incomplete'
                ].includes(res.status);

                const selesai = semuaAkunDiproses && statusAkhir;
                

                if (selesai) {

                    tampilkanTombolDetail(id);

                    clearInterval(monitorTimer);
                    monitorTimer = null;

                    $('#btnGenerateMassal').prop('disabled', false);
                    
                    $('#infoProgressMassal').text(
                        'Proses sudah mencapai status akhir. ' +
                        'Klik Lihat Detail Generate untuk melihat hasil per akun.'
                    );

                    $('#progressMassal')
                        .removeClass('bg-info bg-warning bg-success')
                        .addClass(
                            res.status === 'completed'
                                ? 'bg-success'
                                : 'bg-warning'
                        );
                }
            },
            error: function (xhr) {

                console.error(
                    'Monitoring Generate Massal gagal:',
                    xhr.responseJSON || xhr.responseText
                );

                $('#infoProgressMassal').text(
                    'Gagal memperbarui monitoring. ' +
                    'Proses Queue mungkin masih berjalan.'
                );
            },

            complete: function () {
                sedangMonitor = false;
            }
        });
    }

    $('#formGenerateMassal').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);

        if (!$('#periodeMassal').val()
            || !$('#entitasMassal').val()) {

            Swal.fire({
                icon: 'warning',
                title: 'Parameter Belum Lengkap',
                text: 'Pilih periode dan entitas.'
            });

            return;
        }

        Swal.fire({
            title: 'Generate Saldo Massal?',
            text: 'Seluruh akun transaksi aktif pada entitas dan periode ini akan dimasukkan ke antrean.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Generate',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745'

        }).then(function (result) {

            if (!result.isConfirmed) return;

            $('#btnGenerateMassal').prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                headers: {
                    Accept: 'application/json'
                },

                success: function (res) {
                    setProsesDetail(res.id);
                    $('#cardProgressMassal')
                        .removeClass('d-none');

                    $('#progressMassal')
                        .removeClass('bg-success bg-warning')
                        .addClass('bg-info')
                        .css('width', '0%')
                        .text('0%');

                    $('#infoProgressMassal').text(
                        'Proses masuk antrean Queue.'
                    );

                    if (monitorTimer) {
                        clearInterval(monitorTimer);
                    }

                    monitorProgress(res.id);

                    monitorTimer = setInterval(function () {
                        monitorProgress(res.id);
                    }, 3000);
                },

                error: function (xhr) {

                    $('#btnGenerateMassal')
                        .prop('disabled', false);

                    const errors = xhr.responseJSON?.errors || {};

                    const message =
                        Object.values(errors).flat().join('\n')
                        || xhr.responseJSON?.message
                        || 'Gagal membuat proses generate.';

                    Swal.fire({
                        icon: 'error',
                        title: 'Generate Gagal',
                        text: message
                    });
                }
            });
        });
    });


    let prosesDetailId = null;
    let detailTable = null;

    // Endpoint detail, mengikuti route Laravel.
    const detailUrlTemplate = @json(
        route(
            'akuntansi.saldo-generate-massal.detail',
            ['id' => '__ID__']
        )
    );

    function escapeHtml(value) {
        return $('<div>')
            .text(value == null ? '' : String(value))
            .html();
    }

    function formatStatus(status) {
        const labels = {
            completed: 'Completed',
            skipped: 'Skipped',
            failed: 'Failed',
            processing: 'Processing',
            pending: 'Pending'
        };

        const colors = {
            completed: 'success',
            skipped: 'info',
            failed: 'danger',
            processing: 'warning',
            pending: 'secondary'
        };

        const color = colors[status] || 'secondary';
        const label = labels[status] || status || '-';

        return '<span class="badge bg-' + color + '">' +
            escapeHtml(label) +
            '</span>';
    }

    // Simpan ID proses saat generate baru berhasil.
    // Fungsi ini dipanggil dari AJAX generate.
    function setProsesDetail(id) {
        prosesDetailId = id;

        $('#btnDetailGenerateMassal')
            .addClass('d-none');
    }

    // Tampilkan tombol saat batch selesai.
    function tampilkanTombolDetail(id) {
        prosesDetailId = id;

        $('#btnDetailGenerateMassal')
            .removeClass('d-none');
    }

    // Buka modal dan ambil data terbaru.
    $('#modalDetailGenerateMassal').on('show.bs.modal', function () {

        if (!prosesDetailId) return;

        $('#detailNamaEntitas').text('Memuat...');
        $('#detailPeriode').text('-');
        $('#detailStatus').text('-');

        $('#filterStatusDetail').val('');

        if (detailTable) {
            detailTable.clear().draw();
        }

        $.ajax({
            url: detailUrlTemplate.replace(
                '__ID__',
                String(prosesDetailId)
            ),
            type: 'GET',
            dataType: 'json',

            success: function (res) {

                $('#detailNamaEntitas')
                    .text(res.proses.entitas || '-');

                $('#detailPeriode')
                    .text(res.proses.periode || '-');

                $('#detailStatus')
                    .html(formatStatus(res.proses.status));

                const rows = (res.details || []).map(function (item) {

                    return [
                        '',
                        escapeHtml(item.no_akun || '-'),
                        escapeHtml(item.nama_akun || '-'),
                        item.status,
                        Number(item.jumlah_baris || 0),
                        '<span style="white-space:pre-wrap;overflow-wrap:anywhere;">' +
                            escapeHtml(item.message || '-') +
                        '</span>',
                        escapeHtml(item.finished_at || '-')
                    ];
                });

                if (!detailTable) {
                    detailTable = $('#tableDetailGenerateMassal')
                        .DataTable({
                            data: rows,
                            pageLength: 10,
                            lengthMenu: [10, 25, 50, 100],
                            autoWidth: false,
                            order: [],
                            columnDefs: [
                                {
                                    targets: 0,
                                    orderable: false,
                                    searchable: false,
                                    render: function (data, type, row, meta) {
                                        return meta.row + meta.settings._iDisplayStart + 1;
                                    }
                                },
                                {
                                    targets: 3,
                                    render: function (data, type) {
                                        if (type === 'display') {
                                            return formatStatus(data);
                                        }

                                        return data || '';
                                    }
                                }
                            ],
                            language: {
                                search: 'Cari:',
                                lengthMenu: 'Tampilkan _MENU_ data',
                                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ akun',
                                infoEmpty: 'Tidak ada data',
                                zeroRecords: 'Tidak ditemukan akun',
                                paginate: {
                                    next: 'Berikutnya',
                                    previous: 'Sebelumnya'
                                }
                            }
                        });
                } else {
                    detailTable.clear();
                    detailTable.rows.add(rows).draw();
                }

            },

            error: function (xhr) {

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mengambil Detail',
                    text: xhr.responseJSON?.message ||
                        'Detail generate tidak dapat dimuat.'
                });
            }
        });
    });

    // Filter status melalui kolom ke-4.
    // Strip HTML badge saat pencocokan status.
    $('#filterStatusDetail').on('change', function () {

        if (!detailTable) return;

        const status = $(this).val();

        detailTable
            .column(3)
             .search(
                status ? '^' + status + '$' : '',
                true,
                false,
                true
            )
            .draw();
    });

    // Perbaiki lebar DataTables ketika modal tampil.
    $('#modalDetailGenerateMassal').on('shown.bs.modal', function () {
        if (detailTable) {
            detailTable.columns.adjust();
        }
    });

    $(document).on('click', '#btnDetailGenerateMassal', function (e) {

        e.preventDefault();

        console.log('Tombol Detail diklik');
        console.log('Proses ID:', prosesDetailId);

        if (!prosesDetailId) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Proses Tidak Ditemukan',
                text: 'ID proses generate belum tersedia.'
            });
            return;
        }

        $('#modalDetailGenerateMassal').modal('show');
    });

    $(document).on('click', '.btnCloseDetailMassal', function (e) {

        e.preventDefault();

        $('#modalDetailGenerateMassal').modal('hide');

    });


});
</script>
@endpush