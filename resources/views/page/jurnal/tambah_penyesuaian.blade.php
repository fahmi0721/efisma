@extends('layouts.app')
@section('title','Create New Jurnal Rupa-Rupa')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Create New Jurnal Rupa-Rupa</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('jurnal.penyesuaian') }}">Jurnal Rupa-Rupa</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create New Jurnal Rupa-Rupa</li>
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
                    <h5 class="mb-0">Create New Jurnal Rupa-Rupa</h5>
                    <div class="ms-auto">
                        <button id="btnCariUangMuka" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-invoice-dollar fa-regular"></i> Pertanggung Jawaban Uang Muka
                        </button>
                        <button id="btnCariInvoice" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-search fa-regular"></i> Cari Invoice Belum Lunas
                        </button>
                    </div>
                </div>
                
                <form action="javascript:void(0)" enctype="multipart/form-data" id="form_data">
                    @csrf
                    @method("post")
                    <input type="hidden" name='jurnal_id_jp' id="jurnal_id_jp">
                    <input type="hidden" name='jurnal_id_jkk' id="jurnal_id_jkk">
                    <div class="card-body">

                        <!-- 🔄 Ganti Tahun → Periode -->
                        <div class="row mb-3">
                            <label for="tanggal" class="col-sm-3 col-form-label">Tanggal <b class="text-danger">*</b></label>
                            <div class="col-sm-9">
                                <input type="text" id="tanggal" name="tanggal" class="form-control" 
                                       placeholder="Pilih periode (YYYY-MM-DD)" readonly required>
                            </div>
                        </div>
                        @if(auth()->user()->level != "entitas")
                        <div class="row mb-3">
                            <label for="entitas_id" class="col-sm-3 col-form-label">Entitas <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="entitas_id" id="entitas_id" class="form-control entitas">
                                    <option value="">-- Pilih Entitas --</option>
                                </select>
                            </div>
                        </div>
                        @endif
                        <div class="row mb-3">
                            <label for="cabang_id" class="col-sm-3 col-form-label">Cabang <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="cabang_id" id="cabang_id" class="form-control cabang">
                                    <option value="">-- Pilih Cabang --</option>
                                </select>
                            </div>
                        </div>
                        

                        <div class="row mb-3">
                            <label for="partner_id" class="col-sm-3 col-form-label">Partner</label>
                            <div class="col-sm-9">
                                <select name="partner_id" id="partner_id" class="form-control partner">
                                    <option value="">-- Pilih Partner --</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="no_invoice" class="col-sm-3 col-form-label">No Ref </label>
                            <div class="col-sm-9">
                                <input type="text"  class="form-control"  id="no_invoice_t" placeholder="No Ref" />
                                <input type="hidden"  class="form-control" id="no_invoice" name="no_invoice" placeholder="No Ref" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="keterangan" class="col-sm-3 col-form-label">Keterangan <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text"  class="form-control" id="keterangan" name="keterangan" placeholder="Keterangan" />
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="tableDetail">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30%">Akun</th>
                                        <th>Deskripsi</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Kredit</th>
                                        <th style="width: 5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select class="form-select akun-selectok" name="detail[0][akun_id]">
                                                <option value="">-- Pilih Akun --</option>
                                                @foreach(DB::table('m_akun_gl')->orderBy('no_akun')->get() as $akun)
                                                    <option value="{{ $akun->id }}">{{ $akun->no_akun }} - {{ $akun->nama }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="detail[0][deskripsi]" class="form-control"></td>
                                        <td><input type="text"  name="detail[0][debit]" onkeyup="formatRupiah(this)" class="form-control text-end debit-input" value="0"></td>
                                        <td><input type="text"  name="detail[0][kredit]" onkeyup="formatRupiah(this)" class="form-control text-end kredit-input" value="0"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm btn-hapus"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5">
                                            <button type="button" class="btn btn-success btn-sm" id="btnTambahBaris">
                                                <i class="fas fa-plus"></i> Tambah Baris
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="table-light">
                                        <th colspan="2" class="text-end">TOTAL</th>
                                        <th class="text-end" id="totalDebit">0</th>
                                        <th class="text-end" id="totalKredit">0</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                            

                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('jurnal.penyesuaian') }}" class="btn btn-danger btn-flat btn-sm">
                            <i class="fa fa-mail-reply"></i> Kembali
                        </a>
                        <button type="submit" id="btn-submit" class="btn btn-success btn-flat btn-sm float-end">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
                <!-- Template baris tersembunyi -->
                <table style="display:none;">
                    <tbody id="rowTemplate">
                        <tr>
                            <td>
                                <select class="form-select akun-select" name="detail[__INDEX__][akun_id]">
                                    <option value="">-- Pilih Akun --</option>
                                </select>
                            </td>
                            <td><input type="text" name="detail[__INDEX__][deskripsi]" class="form-control"></td>
                            <td><input type="text" name="detail[__INDEX__][debit]" onkeyup="formatRupiah(this)" class="form-control text-end debit-input" value="0"></td>
                            <td><input type="text" name="detail[__INDEX__][kredit]" onkeyup="formatRupiah(this)" class="form-control text-end kredit-input" value="0"></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-sm btn-hapus"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>  
        </div>
    </div>    
</div>
<!-- 🧾 Modal Cari Invoice Piutang -->
<div class="modal fade" id="modalInvoice" tabindex="-1" aria-labelledby="modalInvoiceLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title" id="modalInvoiceLabel">Daftar Invoice Belum Lunas</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div class="table-responsive">
          <table id="tb_invoice" class="table table-bordered table-striped">
            <thead class="table-light">
              <tr class="text-center">
                <th width="5%">#</th>
                <th>Kode Jurnal</th>
                <th>Tanggal</th>
                <th>Total Tagihan</th>
                <th>Total Bayar</th>
                <th>Sisa Piutang</th>
                <th width="5%">Aksi</th>
              </tr>
            </thead>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- 🧾 Modal Cari Uang MUka -->
<div class="modal fade" id="modalUangMuka" tabindex="-1" aria-labelledby="modalUangMuka" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title" id="modalUangMuka">Daftar Invoice Belum Lunas</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div class="table-responsive">
          <table id="tb_uang_muka" class="table table-bordered table-striped">
            <thead class="table-light">
              <tr class="text-center">
                <th width="5%">#</th>
                <th>Kode Jurnal</th>
                <th>Entitas</th>
                <th>Partner<br /><small>(Vendor/Pegawai)</small></th>
                <th>Akun Uang Muka</th>
                <th>Tanggal</th>
                <th>Nominal</th>
                <th>Terpakai</th>
                <th>Sisa</th>
                <th>Umur</th>
                <th width="5%">Aksi</th>
              </tr>
            </thead>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@section('js')

<script>
$(document).ready(function() {
/** Button Cari Uang Muka Open */
    $('#btnCariUangMuka').click(function() {
        const entitas = $('#entitas_id').val();
        if (!entitas) {
            Swal.fire('Oops', 'Pilih entitas terlebih dahulu!', 'warning');
            return;
        }
        $('#modalUangMuka').modal('show');
        loadUangMukaTable(entitas);
    });
    /** Buttton Cari Piutang */
    $('#btnCariInvoice').click(function() {
        const entitas = $('#entitas_id').val();
        const partner = $('#partner_id').val();
        if (!entitas) {
            Swal.fire('Oops', 'Pilih entitas terlebih dahulu!', 'warning');
            return;
        }
        if (!partner) {
            Swal.fire('Oops', 'Pilih partner terlebih dahulu!', 'warning');
            return;
        }
        $('#modalInvoice').modal('show');
        loadInvoiceTable(partner);
    });
    flatpickr("#tanggal", {
        altInput: true,
        altFormat: "d F Y",   // tampilan di input: 10 Juli 2025
        dateFormat: "Y-m-d",  // format yang dikirim ke backend: 2025-07-10
        allowInput: false,
        locale: "id"
    });

    // 🔽 Select2 Akun GL
    $('.akun-selectok').select2({
        ajax: {
            url: '{{ route("saldo_awal.akun_gl") }}',
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
        width: '100%',
        placeholder: "-- Pilih Akun GL --",
        allowClear: true
    });
    @if(auth()->user()->level != "entitas")
    // 🔽 Select2 Akun GL
    $('.entitas').select2({
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
        width: '100%',
        placeholder: "-- Pilih Entitas --",
        allowClear: true
    });
    @endif

    $('.cabang').select2({
        ajax: {
            url: '{{ route("cabang.select") }}',
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
        width: '100%',
        placeholder: "-- Pilih Cabang --",
        allowClear: true
    });

    // 🔽 Select2 Customer
    $('.partner').select2({
        ajax: {
            url: '{{ route("partner.select") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // teks yang diketik user
                    jenis: 'all', // teks yang diketik user
                    @if(auth()->user()->level != "entitas")
                    entitas_id: $('#entitas_id').val() || null // kirim data tambahan jika ada
                    @endif
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text: q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        width: '100%',
        placeholder: "-- Pilih Partner --",
        allowClear: true
    });
    @if(auth()->user()->level != "entitas")
    $('.entitas').on('change', function () {
        $('.partner').val(null).trigger('change'); // kosongkan value dulu
    });
    @endif

    // 🧠 Submit Form
    $("#form_data").submit(function(e){
        e.preventDefault();
        proses_data();
    });
});

// tambah baris baru
$('#btnTambahBaris').click(function() {
    let idx = $('#tableDetail tbody tr').length;
    let row = $('#rowTemplate tr').clone();

    // Ganti placeholder index
    row.find('select, input').each(function() {
        let name = $(this).attr('name').replace('__INDEX__', idx);
        $(this).attr('name', name).val('');
        if ($(this).hasClass('debit-input') || $(this).hasClass('kredit-input')) {
            $(this).val('0');
        }
    });

    // Tambahkan ke tabel utama
    $('#tableDetail tbody').append(row);

    // Inisialisasi select2 untuk baris baru saja
    row.find('.akun-select').select2({
        ajax: {
            url: '{{ route("saldo_awal.akun_gl") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text: q.no_akun + " - " + q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        width: '100%',
        placeholder: "-- Pilih Akun GL --",
        allowClear: true
    });

    reIndexRows();
});


// hapus baris
$(document).on('click', '.btn-hapus', function() {
    if ($('#tableDetail tbody tr').length > 1) {
        $(this).closest('tr').remove();
        reIndexRows();
        hitungTotal();
    }
});

// hitung total otomatis
$(document).on('input', '.debit-input, .kredit-input', function() {
    hitungTotal();
});

/** Load Datatable Uang Muka */
function loadUangMukaTable(entitas_id) {
    if ($.fn.DataTable.isDataTable('#tb_uang_muka')) {
        $('#tb_uang_muka').DataTable().destroy();
    }

    $('#tb_uang_muka').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('jurnal.uangmuka.datatable') }}",
            data: { entitas_id: entitas_id },
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false },
            { 
                data: 'kode_jurnal', 
                name: 'kode_jurnal',
                render: function(data,type, row) {
                     if (type === 'display') {
                        let html = row.kode_jurnal;
                        if (row.no_invoice) {
                            html += `<br><b><small>No Ref : ${row.no_invoice}</small></b>`;
                        }
                        return html;
                    }

                    // 🔹 Untuk search / export — pakai teks polos gabungan
                    return `${row.kode_jurnal} ${row.no_invoice ?? ''}`;
                },orderable:false, 
            },
            { data: 'entitas_nama', name: 'entitas_nama' },
            { data: 'partner_nama', name: 'partner_nama' },
            { data: 'akun_uang_muka', name: 'akun_uang_muka' },
            { data: 'tanggal', name: 'tanggal' },
            { data: 'nominal', name: 'nominal', className: 'text-end',orderable: false, searchable: false,
                render: data => new Intl.NumberFormat('id-ID').format(data)
            },
            { data: 'terpakai', name: 'terpakai', className: 'text-end',orderable: false, searchable: false,
                render: data => new Intl.NumberFormat('id-ID').format(data)
            },
            { data: 'sisa', name: 'sisa', className: 'text-end',orderable: false, searchable: false,
                render: data => new Intl.NumberFormat('id-ID').format(data)
            },
            { data: 'umur', name: 'umur', 
                render: data => data + " Hari"
            },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
        // order: [[, 'asc']]
    });
}

/** Load Datatable piutang */
function loadInvoiceTable(partner_id) {
    if ($.fn.DataTable.isDataTable('#tb_invoice')) {
        $('#tb_invoice').DataTable().destroy();
    }

    $('#tb_invoice').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('jurnal.piutang.datatable') }}",
            data: { partner_id: partner_id },
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false },
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
            { data: 'total_tagihan', name: 'total_tagihan', className: 'text-end',orderable: false, searchable: false,
                render: data => new Intl.NumberFormat('id-ID').format(data)
            },
            { data: 'total_bayar', name: 'total_bayar', className: 'text-end',orderable: false, searchable: false,
                render: data => new Intl.NumberFormat('id-ID').format(data)
            },
            { data: 'sisa_piutang', name: 'sisa_piutang', className: 'text-end',orderable: false, searchable: false,
                render: data => new Intl.NumberFormat('id-ID').format(data)
            },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
        order: [[2, 'asc']]
    });
}
function hitungTotal() {
    let totalDebit = 0, totalKredit = 0;

    $('.debit-input').each(function() {
        let val = $(this).val().replace(/\./g, '').replace(/,/g, '.'); // hilangkan . ribuan, ubah , ke .
        totalDebit += parseFloat(val) || 0;
    });

    $('.kredit-input').each(function() {
        let val = $(this).val().replace(/\./g, '').replace(/,/g, '.');
        totalKredit += parseFloat(val) || 0;
    });

    $('#totalDebit').text(totalDebit.toLocaleString('id-ID'));
    $('#totalKredit').text(totalKredit.toLocaleString('id-ID'));
}
$(document).on('click','.pilihUangMuka', function() {
    const data = $(this).data();
    const id_jkk = $("#jurnal_id_jkk").val();
    if (id_jkk && id_jkk.trim() !== "") {
        Swal.fire('Oops', 'Anda sudah memilih satu uang muka!, Hapus dulu dari data detail jurnal', 'warning');
        return;
    }
    $("#jurnal_id_jkk").val(data.id);
    var option = new Option(data.cabang, data.cabang_id, true, true);
    $(".cabang").append(option).trigger('change');
    $(".cabang").prop("disabled",true);

    var option = new Option(data.partner, data.partner_id, true, true);
    $(".partner").append(option).trigger('change');
    $(".partner").prop("disabled",true);
    $(".entitas").prop("disabled",true);

    // Kirim ke fungsi pembuat baris jurnal
    insertDetailJurnalUangMuka({
        id: data.id,
        kode: data.kode,
        tanggal: data.tanggal,
        total: data.total,
        sisa: data.sisa,
        akun_id: data.akun_id,
        akun_nama: data.akun_nama
    });
    $("#btnCariInvoice").prop("disabled",true);
    $('#modalUangMuka').modal('hide');
});
$(document).on('click', '.btn-pilih-invoice', function() {
    const data = $(this).data();
    // Jika sudah ada invoice di form, blokir
    const id_jp = $("#jurnal_id_jp").val();
    if (id_jp && id_jp.trim() !== "") {
        Swal.fire('Oops', 'Anda sudah memilih satu invoice!, Hapus dulu dari data detail jurnal', 'warning');
        return;
    }

    $("#jurnal_id_jp").val(data.id);
    var option = new Option(data.cabang, data.cabang_id, true, true);
    $(".cabang").append(option).trigger('change');
    $(".cabang").prop("disabled",true);
    $(".entitas").prop("disabled",true);

    
   

    $("#no_invoice").val(data.no_invoice);
    $("#no_invoice_t").val(data.no_invoice);
    $("#no_invoice_t").prop("disabled",true);
    // Kirim ke fungsi pembuat baris jurnal
    insertDetailJurnal({
        id: data.id,
        kode: data.kode,
        tanggal: data.tanggal,
        total: data.total,
        sisa: data.sisa,
        akun_piutang_id: data.akun_piutang_id,
        akun_piutang_nama: data.akun_piutang_nama
    });

    $('#modalInvoice').modal('hide');
});

function insertDetailJurnalUangMuka(data) {
    
    function formatIDR(angka) {
        if (!angka || isNaN(angka)) return '0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(angka).replace('Rp', '').trim(); // tanpa simbol "Rp"
    }
    // Ambil index baris terakhir
    let idx = $('#tableDetail tbody tr').length;

    // 🔹 Baris kedua: Piutang (Kredit)
    let rowPiutang = `
        <tr>
            <td>
                <select class="form-select akun-select" name="detail[${idx}][akun_id]" required>
                    <option value="${data.akun_id ?? ''}">
                        ${data.akun_nama ?? '(Akun Uang Muka)'}
                    </option>
                </select>
            </td>
            <td><input readonly type="text" name="detail[${idx}][deskripsi]" value='PJ Uang Muka ${data.kode}' class="form-control"></td>
            <td><input type="text" readonly name="detail[${idx}][debit]" onkeyup="formatRupiah(this)" class="form-control text-end debit-input" value="0"></td>
            <td><input type="text" name="detail[${idx}][kredit]" onkeyup="formatRupiah(this)" class="form-control text-end kredit-input" value="${formatIDR(data.sisa)}"></td>
            <td class="text-center">
                <button type="button" data-toggle='tooltip' title='tidak boleh dihapus' data-piutang='ada' class="btn btn-warning btn-sm btn-piutang"><i class="fas fa-times"></i></button>
            </td>
        </tr>
    `;

    // Masukkan ke tabel
    $('#tableDetail tbody').append(rowPiutang);
    $("[data-toggle='tooltip']").tooltip();
    // Hitung ulang total
    hitungTotal();
}

function insertDetailJurnal(data) {
    
    function formatIDR(angka) {
        if (!angka || isNaN(angka)) return '0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(angka).replace('Rp', '').trim(); // tanpa simbol "Rp"
    }
    // Ambil index baris terakhir
    let idx = $('#tableDetail tbody tr').length;

    // 🔹 Baris kedua: Piutang (Kredit)
    let rowPiutang = `
        <tr>
            <td>
                <select class="form-select akun-select" name="detail[${idx}][akun_id]" required>
                    <option value="${data.akun_piutang_id ?? ''}">
                        ${data.akun_piutang_nama ?? '(Akun Piutang)'}
                    </option>
                </select>
            </td>
            <td><input readonly type="text" name="detail[${idx}][deskripsi]" value='Pelunasan Invoice ${data.kode}' class="form-control"></td>
            <td><input type="text" name="detail[${idx}][debit]" onkeyup="formatRupiah(this)" class="form-control text-end debit-input" value="0"></td>
            <td><input type="text" name="detail[${idx}][kredit]" onkeyup="formatRupiah(this)" class="form-control text-end kredit-input" value="${formatIDR(data.sisa)}"></td>
            <td class="text-center">
                <button type="button" data-toggle='tooltip' title='tidak boleh dihapus' data-piutang='ada' class="btn btn-warning btn-sm btn-piutang"><i class="fas fa-times"></i></button>
            </td>
        </tr>
    `;

    // Masukkan ke tabel
    $('#tableDetail tbody').append(rowPiutang);
    $("[data-toggle='tooltip']").tooltip();
    // Hitung ulang total
    hitungTotal();
}


function reIndexRows() {
    $('#tableDetail tbody tr').each(function(i, tr) {
        $(tr).find('select, input').each(function() {
            let name = $(this).attr('name');
            $(this).attr('name', name.replace(/\d+/, i));
        });
    });
}

function proses_data(){
    $(".cabang").prop("disabled",false);
    $(".entitas").prop("disabled",false);
    $(".partner").prop("disabled",false);
    $(".no_invoice_t").prop("disabled",false);
    let iData = new FormData(document.getElementById("form_data"));
    $.ajax({
        type: "POST",
        url: "{{ route('jurnal.penyesuaian.save') }}",
        data: iData,
        cache: false,
        processData: false,
        contentType: false,
        beforeSend: function (){
            $("#btn-submit").html("<i class='fa fa-spinner fa-spin'></i>  Simpan..");
            $("#btn-submit").prop("disabled", true);
        },
        success: function(result){
            console.log(result);
            if(result.status == "success"){
                position = "bottom-left";
                icons = result.status;
                pesan = result.messages;
                title = "Saved!";
                info(title, pesan, icons, position);
                $("#btn-submit").html("<i class='fa fa-save'></i> Simpan");
                $("#btn-submit").prop("disabled", false);
                setTimeout(() => {
                    window.location.href = "{{ route('jurnal.penyesuaian') }}";
                }, 1500);
            }else{
                $(".cabang").prop("disabled",true);
                $(".entitas").prop("disabled",true);
                $(".partner").prop("disabled",true);
                $(".no_invoice_t").prop("disabled",true);
            }
        },
        error: function(e){
            console.log(e);
            $("#btn-submit").html("<i class='fa fa-save'></i> Simpan");
            $("#btn-submit").prop("disabled", false);
            error_message(e,'Proses Data Error');
            $(".cabang").prop("disabled",true);
            $(".entitas").prop("disabled",true);
            $(".partner").prop("disabled",true);
            $(".no_invoice_t").prop("disabled",true);
        }
    });
}
</script>
@endsection
