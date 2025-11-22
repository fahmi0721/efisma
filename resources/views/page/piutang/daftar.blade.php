@extends('layouts.app')
@section('title','Daftar Piutang')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Daftar Piutang</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Daftar Piutang</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
@section('css')
<style>
.select2-container--bootstrap-5 .select2-selection--single {
    height: calc(1.5em + .5rem + 2px); /* tinggi sama dengan btn-sm */
    padding: .25rem .5rem;
    font-size: .875rem;
    line-height: 1.5;
    border-radius: .2rem;
}

.select2-container--bootstrap-5 .select2-selection__arrow {
    height: 100%;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    line-height: 1.5;
    padding-left: .25rem;
}

.select2-container {
    min-width: 160px !important;
}

.card-header .select2 {
    margin-bottom: 0 !important;
}
</style>

@endsection
@section('content')
<div class="container-fluid">
    <div class="card card-success card-outline">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Daftar Piutang</h5>

            <div class="d-flex align-items-center gap-2 ms-auto">
                {{-- 🔽 Filter Entitas --}}
                <select id="filter_entitas" class="form-select form-select-sm entitas" style="width:180px">
                    <option value="">Semua Entitas</option>
                </select>

                {{-- 🔽 Filter Tipe Partner --}}
                <select id="filter_tipe" class="form-select " style="width:180px">
                    <option value="all">Semua Partner</option>
                    <option value="customer">Customer</option>
                    <option value="vendor">Vendor</option>
                </select>

                {{-- 📤 Tombol Export Excel --}}
                <button id="btnExportExcel" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tb_data" class="table table-bordered table-striped align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th>Tanggal</th>
                            <th>Invoice</th>
                            <th>Partner</th>
                            <th class="text-end">Tagihan</th>
                            <th class="text-end">Pelunasan</th>
                            <th class="text-end">Sisa Piutang</th>
                            <th class="text-center">Umur (Hari)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot class="table-light fw-bold text-end">
                        <tr>
                            <th colspan="4" class="text-center">TOTAL</th>
                            <th id="total_tagihan">0</th>
                            <th id="total_pelunasan">0</th>
                            <th id="total_sisa">0</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    $('.entitas').select2({
        ajax: {
            url: '{{ route("entitas.select") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text: q.id + " - " + q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        // width: '100%',
         width: 'resolve',
        minimumResultsForSearch: Infinity, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('.card-header'), // pastikan dropdown tidak nyasar
        // placeholder: "-- Pilih Entitas --",
        allowClear: true
    });
    const tb = $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
         ajax: {
            url: "{{ route('piutang.daftar') }}",
            data: function (d) {
                d.filter = $('#filter_tipe').val();
                d.entitas_id = $('#filter_entitas').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', className: 'text-center', orderable: false,searchable: false },
            { 
                data: 'tanggal', 
                searchable: false,
                className: 'text-center',
                render: function(data) {
                    if (!data) return '-';
                    const tgl = new Date(data);
                    return tgl.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                }
            },
            { data: 'no_invoice', className: 'text-center',orderable: false, 
                render: function(data,c,row) {
                    let html = `<b>${row.kode_jurnal}</b>`;
                    if (row.no_invoice && row.no_invoice !== '') {
                        html += `<br><small>No Invoice: ${row.no_invoice}</small>`;
                    }
                    return html;
                }
            },
            { data: 'partner_nama' ,orderable: false },
            { data: 'total_tagihan', className: 'text-end',orderable: false,searchable: false },
            { data: 'total_pelunasan', className: 'text-end',orderable: false,searchable: false },
            { data: 'sisa_piutang', className: 'text-end fw-bold',orderable: false,searchable: false },
            { data: 'umur_piutang', className: 'text-center',orderable: false,searchable: false },
        ],
        order: [[1, 'desc']],
        footerCallback: function (row, data, start, end, display) {
            let api = this.api();

            // fungsi konversi string ke angka
            let intVal = function (i) {
                return typeof i === 'string'
                    ? i.replace(/\./g, '').replace(/,/g, '.') * 1
                    : typeof i === 'number'
                        ? i
                        : 0;
            };

            // hitung total tiap kolom
            let totalTagihan = api.column(4, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
            let totalPelunasan = api.column(5, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
            let totalSisa = api.column(6, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);

            // tampilkan hasil di footer
            $(api.column(4).footer()).html(totalTagihan.toLocaleString('id-ID'));
            $(api.column(5).footer()).html(totalPelunasan.toLocaleString('id-ID'));
            $(api.column(6).footer()).html(totalSisa.toLocaleString('id-ID'));
        },
        language: {
            searchPlaceholder: 'Cari partner...',
            processing: '<i class="fa fa-spinner fa-spin"></i> Loading...'
        }
    });

    // 🔄 Reload ketika filter berubah
    $('#filter_tipe, #filter_entitas').on('change', function() {
        tb.ajax.reload();
    });

    // 📤 Export Excel
    $('#btnExportExcel').click(function() {
        const tipe = $('#filter_tipe').val();
        const entitas = $('#filter_entitas').val();
        window.location.href = "{{ route('piutang.daftar.export') }}?filter=" + tipe + "&entitas_id=" + (entitas ?? '');
    });
});
</script>
@endsection
