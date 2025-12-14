@extends('layouts.app')
@section('title','Jurnal Pendapatan')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Jurnal Pendapatan</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Jurnal Pendapatan</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="card card-success card-outline mb-4">
                <div class="card-header d-flex align-items-center">
                    <h5 class="mb-0">Jurnal Pendapatan</h5>
                    <div class="ms-auto">
                        @canAccess('pendapatan.view')
                        <button  class="btn btn-warning btn-sm cekDepositBtn">
                            <i class="fas fa-wallet"></i> Cek Deposito Customer
                        </button>
                        @endcanAccess
                        @canAccess('pendapatan.unposting')
                        <a href="{{ route('jurnal.pendapatan.unposting') }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-bolt"></i> Unposting
                        </a>
                        @endcanAccess
                        @canAccess('pendapatan.posting')
                        <a href="{{ route('jurnal.pendapatan.posting') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-bolt"></i> Posting
                        </a>
                        @endcanAccess
                        @canAccess('pendapatan.create')
                        <a href="{{ route('jurnal.pendapatan.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus-square"></i> Create New
                        </a>
                        @endcanAccess
                    </div>
                </div>

                <div class="card-body">

                    <!-- 🔍 Filter Periode -->
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filter_periode" class="form-label">Filter</label>
                            <div class="input-group date" id="periode_picker">
                                <input type="text" id="filter_from" class="form-control" placeholder="Date From" readonly />
                                <span class="input-group-text">s.d</span>
                                <input type="text" id="filter_to" class="form-control" placeholder="Date To" readonly />
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button id="btnFilter" class="btn btn-primary w-100 mt-2">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button id="btnReset" class="btn btn-secondary w-100 mt-2">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- 📊 Data Table -->
                    <table id="tb_data" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr class="text-center">
                                <th width="5%">No</th>
                                @canAccess('
                                    pendapatan.edit|
                                    pendapatan.delete|
                                    pendapatan.posting|
                                    pendapatan.view|
                                    pendapatan.unposting|
                                ')
                                <th width="5%">Aksi</th>
                                @endcanAccess
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Entitas</th>
                                <th>Partner</th>
                                <th>Cabang</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>  

        </div>
    </div>    
</div>

<!-- Modal Detail Deposito -->
<div class="modal fade" id="modalDeposit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Deposito Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    @if(auth()->user()->level != "entitas")
                    <div class="col-md-4">
                        <label>Entitas</label>
                        <select id="filterEntitas" class="form-select filterEntitas select2">
                            <option value="">-- Pilih Entitas --</option>
                        </select>
                    </div>
                    @endif
                    <div class="col-md-4">
                        <label>Customer</label>
                        <select id="filterCustomer" class="form-select filterCustomer select2">
                            <option value="">-- Pilih Customer --</option>
                        </select>
                    </div>

                </div>

                <hr>
                <table id="depositTable" class="table table-bordered table-striped w-100">
                    <thead>
                        <tr>
                            <th>Entitas</th>
                            <th>Customer</th>
                            <th>Akun Deposit</th>
                            <th>Total Masuk</th>
                            <th>Total Terpakai</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- Modal Detail Transaksi  -->
<div class="modal fade" id="DetailTransaksi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Detail Transaksi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="DetailTransaksiBody">
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-danger" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection
@section('js')
{{-- ✅ Flatpickr Month Picker --}}

<script>
$(document).on('click', '.cekDepositBtn', function() {
    $('#modalDeposit').modal('show');
    @if(auth()->user()->level != "entitas")
    $('#filterEntitas').select2({
        ajax: {
            url: '{{ route("entitas.select") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text:  q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        width: 'resolve',
        minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('#modalDeposit'),
        // placeholder: "-- Pilih Entitas --",
        // allowClear: true
    });
    @endif
    $('#filterCustomer').select2({
        ajax: {
            url: '{{ route("partner.select") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // teks yang diketik user
                    jenis: 'customer', // teks yang diketik user
                    @if(auth()->user()->level != "entitas")
                    entitas_id: $('#filterEntitas').val() || null // kirim data tambahan jika ada
                    @endif
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text:  q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        width: 'resolve',
        minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('#modalDeposit'),
        // placeholder: "-- Pilih Entitas --",
        // allowClear: true
    });
    loadDepositTable();  // kosong dulu
    @if(auth()->user()->level != "entitas")
    $("#filterCustomer, #filterEntitas").on('change', function() {
        let entitasId = $("#filterEntitas").val();
        let customerId = $("#filterCustomer").val();
        loadDepositTable(entitasId,customerId)
    });
    @else
        $("#filterCustomer").on('change', function() {
        let entitasId = "";
        let customerId = $("#filterCustomer").val();
        loadDepositTable(entitasId,customerId)
    });
    @endif
});

function loadDepositTable(entitasId = '', customerId = '') {

    // destroy table sebelum reload
    if ($.fn.DataTable.isDataTable('#depositTable')) {
        $('#depositTable').DataTable().clear().destroy();
    }

    $('#depositTable').DataTable({
        processing: true,
        responsive: true,
        serverSide: true,
        searching: false,
        paging: false,
        info: false,
        ajax: {
            url: "{{ route('jurnal.pendapatan.deposito') }}",
            data: {
                entitas_id: entitasId,
                customer_id: customerId
            }
        },
        columns: [
            { data: 'entitas', name: 'entitas_nama' },
            { data: 'customer', name: 'customer_nama' },
            { data: 'akun', name: 'akun' },
            { data: 'total_in', name: 'total_in' },
            { data: 'total_used', name: 'total_used' },
            { data: 'saldo', name: 'saldo' }
        ]
    });
}
document.addEventListener('DOMContentLoaded', function () {
    // ✅ Inisialisasi Flatpickr Month Picker
    flatpickr("#filter_from", {
        altInput: true,
        altFormat: "d F Y",   // tampilan di input: 10 Juli 2025
        dateFormat: "Y-m-d",  // format yang dikirim ke backend: 2025-07-10
        allowInput: false,
        locale: "id"
    });

    flatpickr("#filter_to", {
        altInput: true,
        altFormat: "d F Y",   // tampilan di input: 10 Juli 2025
        dateFormat: "Y-m-d",  // format yang dikirim ke backend: 2025-07-10
        allowInput: false,
        locale: "id"
    });

    // 🔄 Load DataTable
    load_data();

    // Filter tombol klik
    $('#btnFilter').on('click', function() {
        $('#tb_data').DataTable().ajax.reload();
    });

    // Reset filter
    $('#btnReset').on('click', function() {
        // Ambil instance Flatpickr dari elemen
        const picker = document.querySelector('#filter_to')._flatpickr;
        const picker1 = document.querySelector('#filter_from')._flatpickr;
        picker.clear(); // ✅ Kosongkan nilai flatpickr dengan benar
        picker1.clear(); // ✅ Kosongkan nilai flatpickr dengan benar
        $('#tb_data').DataTable().ajax.reload();
    });
});
@canAccess('pendapatan.view')
function detail_transaksi(id){
    $.ajax({
        url: "{{ route('jurnal.detail_transaksi') }}?id="+id,
        type: 'GET',
        success: function (res) {
            console.log(res);
            let modal = $("#DetailTransaksi");
            let html ="";
            html += "<div class='table-responsive'>";
            html += "<table class='table table-striped table-bordered'>";
                html += "<thead>";
                    html += "<tr>";
                        html += "<th>No</th>";
                        html += "<th>Akun GL</th>";
                        html += "<th>Deskripsi</th>";
                        html += "<th>Debet</th>";
                        html += "<th>Kredit</th>";
                    html += "</tr>";
                html += "</thead>";
                html += "<tbody>";
                    if (res.length === 0) {
                        tbody.append('<tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>');
                        return;
                    }
                    let no=1;
                    let totDebet=0;
                    let totKredit=0;
                    res.forEach(function(item) {
                        // pastikan debit & kredit selalu angka valid
                        let debit = item.debit;
                        let kredit = item.kredit;

                        // kalau berbentuk string (misalnya "1.000.000"), ubah ke number
                        if (typeof debit === 'string') {
                            debit = parseFloat(debit.replace(/\./g, '').replace(',', '.')) || 0;
                        }
                        if (typeof kredit === 'string') {
                            kredit = parseFloat(kredit.replace(/\./g, '').replace(',', '.')) || 0;
                        }
                        html += `
                            <tr>
                                <td>${no}</td>
                                <td>${item.akun_gl}</td>
                                <td>${item.deskripsi ?? '-'}</td>
                                <td class="text-end">${Number(item.debit).toLocaleString('id-ID')}</td>
                                <td class="text-end">${Number(item.kredit).toLocaleString('id-ID')}</td>
                            </tr>
                        `;
                        no++;                        
                        totDebet += debit;
                        totKredit += kredit;
                    });
                html += "</tbody>";
                html += "<tfoot>";
                    html += "<tr>";
                        html += "<th colspan='3' class='text-end'>TOTAL</th>";
                        html += "<th class='text-end'>"+Number(totDebet).toLocaleString('id-ID')+"</th>";
                        html += "<th class='text-end'>"+Number(totKredit).toLocaleString('id-ID')+"</th>";
                    html += "</tr>";
                html += "</tfoot>";
            html += "</table>";
            html += "<div>";
            $("#DetailTransaksiBody").html(html);
            modal.modal("show");
            console.log(totDebet);
        },
        error: function (err) {
            console.error(err);
            alert('Gagal mengambil data jurnal.');
        }
    });    
}
@endcanAccess
@canAccess('pendapatan.delete')
function hapusData(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data ini akan dihapus secara permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ route('jurnal.delete', ':id') }}".replace(':id', id),
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    _method: "DELETE"
                },
                success: function(response) {
                    Swal.fire('Deleted!', response.message, 'success');
                    $('#tb_data').DataTable().ajax.reload(null, false);
                },
                error: function(err) {
                    Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus data.', 'error');
                }
            });
        }
    });
}
@endcanAccess
@canAccess('pendapatan.posting')
function posting(id){
    Swal.fire({
        title: 'Posting Jurnal?',
        text: 'Setelah diposting, jurnal tidak bisa diubah atau dihapus!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Posting Sekarang',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("{{ route('jurnal.posting') }}", {id: id, _token: '{{ csrf_token() }}'}, function(res) {
                if (res.status) {
                    Swal.fire('Berhasil!', res.message, 'success');
                    $('#tb_data').DataTable().ajax.reload();
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            });
        }
    });
}
@endcanAccess
@canAccess('pendapatan.unposting')
function unposting(id){
    Swal.fire({
        title: 'Batalkan Posting?',
        text: 'Jurnal akan kembali ke status draft.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Unposting',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("{{ route('jurnal.unposting') }}", {id: id, _token: '{{ csrf_token() }}'}, function(res) {
                if (res.status) {
                    Swal.fire('Berhasil!', res.message, 'success');
                    $('#tb_data').DataTable().ajax.reload();
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            });
        }
    });
}
@endcanAccess

@canAccess('pendapatan.view')
// DataTables
function load_data() {
    $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('jurnal.pendapatan') }}",
            data: function (d) {
                d.from = $('#filter_from').val(); // kirim periode ke backend
                d.to = $('#filter_to').val(); // kirim periode ke backend
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            @canAccess('
                        pendapatan.edit|
                        pendapatan.delete|
                        pendapatan.posting|
                        pendapatan.view|
                        pendapatan.unposting
                    ')
            { data: 'aksi', name: 'aksi', orderable:false, searchable:false },
            @endcanAccess
            { 
                data: 'kode_jurnal', 
                name: 'kode_jurnal',
                render: function(data,type, row) {
                     if (type === 'display') {
                        let html = row.kode_jurnal;
                        if (row.no_invoice) {
                            html += `<br><b><small>No Invoice : ${row.no_invoice}</small></b>`;
                        }
                        return html;
                    }

                    // 🔹 Untuk search / export — pakai teks polos gabungan
                    return `${row.kode_jurnal} ${row.no_invoice ?? ''}`;
                },orderable:false, 
            },
            
            { data: 'tanggal', name: 'tanggal' },
            { data: 'entitas', name: 'entitas', orderable:false },
            { data: 'partner', name: 'partner', orderable:false },
            { data: 'cabang', name: 'cabang', orderable:false },
            { 
                data: 'total_debit', 
                name: 'total_debit',
                className: 'text-end',
                render: function(data) {
                    if (!data) return '-';
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR',minimumFractionDigits: 0, }).format(data);
                },orderable:false, 
            },
            { data: 'status', name: 'status',orderable:false,  },
            { data: 'keterangan', name: 'keterangan', orderable:false }
           
        ],
        // order: [[2, 'desc']],
    });
    // Init tooltip setiap setelah table redraw
    $('#tb_data').on('draw.dt', function () {
        $('[data-toggle="tooltip"]').tooltip();
    });

    // Init pertama kali
    $('[data-toggle="tooltip"]').tooltip();
}
@endcanAccess
</script>
@endsection