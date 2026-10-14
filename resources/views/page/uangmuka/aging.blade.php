@extends('layouts.app')
@section('title','Aging Uang Muka')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Aging Uang Muka</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Aging Uang Muka</li>
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
            <h5 class="mb-0">Aging Uang Muka</h5>

            <div class="d-flex align-items-center gap-2 ms-auto">
                @if(auth()->user()->level != "entitas")
                {{-- 🔽 Filter Entitas --}}
                <select id="filter_entitas" class="form-select form-select-sm entitas" style="width:180px">
                    <option value="">Semua Entitas</option>
                </select>
                @endif

                <select id="filter_cabang" class="form-select form-select-sm cabang" style="width:180px">
                    <option value="">Semua Cabang</option>
                </select>

                @canAccess('uangmuka.aging.export')
                {{-- 📤 Tombol Export Excel --}}
                <button id="btnExportExcel" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                @endcanAccess
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tb_data" class="table table-bordered table-striped align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th>Entitas</th>
                            <th>Partner<br><small>(Vendor/Pegawai)</small></th>
                            <th class="text-end">0–7 Hari</th>
                            <th class="text-end">8–15 Hari</th>
                            <th class="text-end">16–30 Hari</th>
                            <th class="text-end">> 30 Hari</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    @if(auth()->user()->level != "entitas")
    $('.entitas').select2({
        ajax: {
            url: '{{ route("entitas.select") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text:q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        // width: '100%',
         width: 'resolve',
        minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('.card-header'), // pastikan dropdown tidak nyasar
        // placeholder: "-- Pilih Entitas --",
        // allowClear: true
    });
    @endif
    @canAccess('piutang.aging.view')
    $('.cabang').select2({
        ajax: {
            url: '{{ route("cabang.select") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text:q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        width: 'resolve',
        minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('.card-header'), // pastikan dropdown tidak nyasar
        // placeholder: "-- Pilih Entitas --",
        // allowClear: true
    });
    const tb = $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
         ajax: {
            url: "{{ route('uangmuka.aging') }}",
            data: function (d) {
                d.entitas_id = $('#filter_entitas').val();
                d.cabang_id = $('#filter_cabang').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', className: 'text-center', orderable: false,searchable: false },
            { data: 'entitas_nama' ,orderable: false,searchable: false },
            { data: 'partner_nama' ,orderable: false },
            // { data: 'partner_nama', name: 'partner_nama' },
            { data: 'aging_0_7', name: 'aging_0_7', className: 'text-end' },
            { data: 'aging_8_14', name: 'aging_8_14', className: 'text-end' },
            { data: 'aging_15_30', name: 'aging_15_30', className: 'text-end' },
            { data: 'aging_30_up', name: 'aging_30_up', className: 'text-end' },
            
        ],
        // order: [[1, 'desc']],
        // footerCallback: function (row, data, start, end, display) {
        //     let api = this.api();

        //     // fungsi konversi string ke angka
        //     let intVal = function (i) {
        //         return typeof i === 'string'
        //             ? i.replace(/\./g, '').replace(/,/g, '.') * 1
        //             : typeof i === 'number'
        //                 ? i
        //                 : 0;
        //     };

        //     // hitung total tiap kolom
        //     let totalTagihan = api.column(4, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
        //     let totalPelunasan = api.column(5, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
        //     let totalSisa = api.column(6, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);

        //     // tampilkan hasil di footer
        //     $(api.column(4).footer()).html(totalTagihan.toLocaleString('id-ID'));
        //     $(api.column(5).footer()).html(totalPelunasan.toLocaleString('id-ID'));
        //     $(api.column(6).footer()).html(totalSisa.toLocaleString('id-ID'));
        // },
        language: {
            searchPlaceholder: 'Cari partner...',
            processing: '<i class="fa fa-spinner fa-spin"></i> Loading...'
        }
    });

    // 🔄 Reload ketika filter berubah
    $('#filter_entitas,#filter_cabang').on('change', function() {
        tb.ajax.reload();
    });
    @endcanAccess
    @canAccess('uangmuka.aging.export')
    // 📤 Export Excel
    $('#btnExportExcel').click(function() {
        const entitas = $('#filter_entitas').val();
        const cabang = $('#filter_cabang').val(); // ← ambil pilihan cabang

        const url = "{{ route('piutang.daftar.export') }}"
            + "&entitas_id=" + encodeURIComponent(entitas ?? '')
            + "&cabang_id=" + encodeURIComponent(cabang ?? '');

        window.location.href = url;
    });
    @endcanAccess
});
</script>
@endsection
