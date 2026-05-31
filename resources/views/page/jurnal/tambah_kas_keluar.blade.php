@extends('layouts.app')
@section('title','Create New Jurnal Kas Keluar')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Create New Jurnal Kas Keluar</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('jurnal.kaskeluar') }}">Jurnal Kas Keluar</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create New Jurnal Kas Keluar</li>
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
                    <h5 class="mb-0">Create New Jurnal Kas Keluar</h5>
                    <div class="ms-auto">
                        <button id="btnCariHutang" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-invoice-dollar fa-regular"></i> Pelunasan Hutang
                        </button>
                        
                    </div>
                </div>
                
                <form action="javascript:void(0)" enctype="multipart/form-data" id="form_data">
                    @csrf
                    @method("post")
                    <input type="hidden" name="is_multi_cabang" id="is_multi_cabang" value='0'>
                    <div class="card-body">
                        <!-- 🔄 Ganti Tahun → Periode -->
                        <div class="row mb-3">
                            <label for="tanggal" class="col-sm-3 col-form-label">Tanggal <b class="text-danger">*</b></label>
                            <div class="col-sm-9">
                                <input type="text" id="tanggal" name="tanggal" class="form-control" 
                                       placeholder="Pilih Tanggal (YYYY-MM-DD)" readonly required>
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
                        
                        <div class="row mb-3 dis-cabang">
                            <label for="cabang_id" class="col-sm-3 col-form-label">Cabang</label>
                            <div class="col-sm-9">
                                <select name="cabang_id" id="cabang_id" class="form-control cabang">
                                    <option value="">-- Pilih Cabang --</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3 dis-partner">
                            <label for="partner_id" class="col-sm-3 col-form-label">Partner</label>
                            <div class="col-sm-9">
                                <select name="partner_id" id="partner_id" class="form-control partner">
                                    <option value="">-- Pilih Partner --</option>
                                </select>
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
                                        <th class="col-cabang d-none">Cabang</th>
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
                                        <td>
                                            <input type=""hidden  name="detail[0][jurnal_id]" class="form-control" value="">
                                            <input type="text" name="detail[0][deskripsi]" class="form-control">
                                        </td>
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
                                        <th  colspan="2" class="text-end">TOTAL</th>
                                        <th class="text-end" id="totalDebit">0</th>
                                        <th class="text-end" id="totalKredit">0</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                            

                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('jurnal.kaskeluar') }}" class="btn btn-danger btn-flat btn-sm">
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
                            <td>
                                <input type="hidden"  name="detail[__INDEX__][jurnal_id]" class="form-control" value="">
                                <input type="text" name="detail[__INDEX__][deskripsi]" class="form-control">
                            </td>
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



<!-- 🧾 Modal Cari Hutang -->
<div class="modal fade" id="modalHutang" tabindex="-1" aria-labelledby="modalHutang" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title" id="modalHutang">Daftar Hutang</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div class="table-responsive">
          <table id="tb_hutang" class="table table-bordered table-striped">
            <thead class="table-light">
              <tr class="text-center">
                <th width="5%">#</th>
                <th>Kode Jurnal</th>
                <th>Entitas</th>
                <th>Partner<br /><small>(Vendor/Pegawai)</small></th>
                <th>Akun Hutang</th>
                <th>Tanggal</th>
                <th>Total Hutang</th>
                <th>Pelunasan</th>
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
    
    $('#btnCariHutang').click(function() {
        @if(auth()->user()->level == "entitas")
            const entitas = "{{ auth()->user()->entitas_id }}";
            const partner = $('#partner_id').val();
            if (!partner) {
                Swal.fire('Oops', 'Pilih partner terlebih dahulu!', 'warning');
                return;
            }
        @else
            const entitas = $('#entitas_id').val();
            if (!entitas) {
                Swal.fire('Oops', 'Pilih entitas terlebih dahulu!', 'warning');
                return;
            }
            const partner = $('#partner_id').val();
            if (!partner) {
                Swal.fire('Oops', 'Pilih partner terlebih dahulu!', 'warning');
                return;
            }
        @endif
        $('#modalHutang').modal('show');
        loadHutangTable(entitas,partner);
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
    // 🔽 Select2 Entitas
    $('.entitas').select2({
        ajax: {
            url: '{{ route("entitas.select") }}',
            dataType: 'json',
            delay: 250,
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
        placeholder: "-- Pilih Entitas --",
        allowClear: true
    });
    @endif
    // 🔽 Select2 Entitas
    $('.cabang').select2({
        ajax: {
            url: '{{ route("cabang.select") }}',
            dataType: 'json',
            delay: 250,
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
                    jenis: 'all',
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
        placeholder: "-- Pilih Vendor --",
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



/** Load Datatable Hutang */
function loadHutangTable(entitas_id,partner_id) {
    if ($.fn.DataTable.isDataTable('#tb_hutang')) {
        $('#tb_hutang').DataTable().destroy();
    }

    $('#tb_hutang').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('jurnal.hutang.datatable') }}",
            data: { entitas_id: entitas_id,partner_id: partner_id },
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false },
            { 
                data: 'kode_jurnal', 
                name: 'kode_jurnal',
                render: function(data,type, row) {
                     if (type === 'display') {
                        let html = row.kode_jurnal;
                        return html;
                    }

                    // 🔹 Untuk search / export — pakai teks polos gabungan
                    return `${row.kode_jurnal} ${row.no_invoice ?? ''}`;
                },orderable:false, 
            },
            { data: 'entitas_nama', name: 'm_entitas.nama' },
            { data: 'partner_nama', name: 'partner_nama' },
            { data: 'akun_hutang', name: 'm_akun_gl.nama' },
            {
                data: 'tanggal',
                name: 'tanggal',
                render: function (data) {
                    if (!data) return '';
                    let tgl = new Date(data);
                    return tgl.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    });
                }
            },
            { data: 'total_tagihan', name: 'total_tagihan', className: 'text-end',orderable: false, searchable: false,
                render: data => new Intl.NumberFormat('id-ID').format(data)
            },
            { data: 'total_pelunasan', name: 'total_pelunasan', className: 'text-end',orderable: false, searchable: false,
                render: data => new Intl.NumberFormat('id-ID').format(data)
            },
            { data: 'sisa_hutang', name: 'sisa_hutang', className: 'text-end',orderable: false, searchable: false,
                render: data => new Intl.NumberFormat('id-ID').format(data)
            },
            { data: 'umur_hutang', name: 'umur_hutang', 
                render: data => data + " Hari"
            },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
        // order: [[, 'asc']]
    });
}

$(document).on('click','.pilihHutang', function() {
    const data = $(this).data();
    console.log(data);
    $(".entitas").prop("disabled",true);
    $(".partner").prop("disabled",true);
    insertDetailJurnalUtang({
            id: data.id,
            kode: data.kode,
            tanggal: data.tanggal,
            total: data.total,
            sisa: data.sisa_hutang,
            akun_id: data.akun_hutang_id,
            akun_nama: data.akun_nama
        });
    $(".dis-cabang").addClass("d-none");
    $("#is_multi_cabang").val(1);
    $('#modalHutang').modal('hide');
});

function insertDetailJurnalUtang(data) {
    console.log(data);
    
    function formatIDR(angka) {
        if (angka === null || angka === undefined || angka === '' || isNaN(angka)) return '0,00';

        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(angka).replace('Rp', '').trim();
    }
    // Ambil index baris terakhir
    const idx = $('#tableDetail tbody tr').length;
    // 🔹 kolom cabang (conditional)
    let btn_hapus = `<button type="button" data-piutang='' class="btn btn-danger btn-sm btn-hapus"><i class="fas fa-trash"></i></button>`;
    let jurnal_id = `<input type="hidden"  name="detail[${idx}][jurnal_id]" value="${data.id}" class="form-control" value="">`;
   
    // 🔹 Baris kedua: Piutang (Kredit)
    let rowHutang = `
        <tr>
            <td>
                <select class="form-select akun-select" name="detail[${idx}][akun_id]" required>
                    <option value="${data.akun_id ?? ''}">
                        ${data.akun_nama ?? '(Akun Uang Muka)'}
                    </option>
                </select>
            </td>
            <td>${jurnal_id}<input readonly type="text" name="detail[${idx}][deskripsi]" value='PJ Uang Muka ${data.kode}' class="form-control"></td>
            <td><input type="text"  name="detail[${idx}][debit]" onkeyup="formatRupiah(this)" class="form-control text-end debit-input" value="${formatIDR(data.sisa)}" ></td>
            <td><input type="text" name="detail[${idx}][kredit]" onkeyup="formatRupiah(this)" class="form-control readonly text-end kredit-input" value="0"></td>
            <td class="text-center">
                ${btn_hapus}
            </td>
        </tr>
    `;

    // Masukkan ke tabel
    $('#tableDetail tbody').append(rowHutang);
    $("[data-toggle='tooltip']").tooltip();
    // Hitung ulang total
    hitungTotal();
}



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

function reIndexRows() {
    $('#tableDetail tbody tr').each(function(i, tr) {
        $(tr).find('select, input').each(function() {
            let name = $(this).attr('name');
            $(this).attr('name', name.replace(/\d+/, i));
        });
    });
}

/**
 * 🧠 Proses Simpan Data (AJAX)
 * @param {boolean} confirmSave - true jika user sudah konfirmasi peringatan
 */
function proses_data(confirmSave = false) {
    $("#entitas_id").prop("disabled",false);
    $("#partner_id").prop("disabled",false);
    let iData = new FormData(document.getElementById("form_data"));
    if (confirmSave) iData.append('confirm', true);
    $.ajax({
        type: "POST",
        url: "{{ route('jurnal.kaskeluar.save') }}",
        data: iData,
        cache: false,
        processData: false,
        contentType: false,
        beforeSend: function (){
            $("#btn-submit").html("<i class='fa fa-spinner fa-spin'></i>  Simpan..");
            $("#btn-submit").prop("disabled", true);
        },
        success: function(result){
            $("#btn-submit").html("<i class='fa fa-save'></i> Simpan");
            $("#btn-submit").prop("disabled", false);

            // ✅ Jika warning → tampilkan konfirmasi SweetAlert
            if (result.status === "warning" && result.need_confirm) {
                Swal.fire({
                    title: "Konfirmasi Transaksi?",
                    html: `<pre style='text-align:left'>${result.message}</pre>`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Ya, lanjutkan simpan",
                    cancelButtonText: "Batal",
                }).then((res) => {
                    if (res.isConfirmed) {
                        proses_data(true); // kirim ulang dengan konfirmasi
                    }
                });
                return;
            }

            // ✅ Jika sukses
            if (result.status === "success") {
                Swal.fire({
                    title: "Berhasil!",
                    text: result.message || "Data jurnal berhasil disimpan",
                    icon: "success",
                    timer: 1500,
                    showConfirmButton: false
                });
                $("#entitas_id").prop("disabled",true);
                $("#partner_id").prop("disabled",true);
                setTimeout(() => {
                    window.location.href = "{{ route('jurnal.kaskeluar') }}";
                }, 1500);
            }

            // ⚠️ Jika error non-konfirmasi
            if (result.status === "error") {
                Swal.fire("Gagal!", result.message, "error");
                $("#entitas_id").prop("disabled",true);
                $("#partner_id").prop("disabled",true);
            }
        },
        error: function(e){
            console.log(e);
            $("#btn-submit").html("<i class='fa fa-save'></i> Simpan");
            $("#btn-submit").prop("disabled", false);
            Swal.fire("Error", e.responseJSON?.message || "Proses Data Error", "error");
        }
    });
}
</script>
@endsection
