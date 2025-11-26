@extends('layouts.app')
@section('title','Create New Jurnal Kas Masuk')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Create New Jurnal Kas Masuk</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('jurnal.kasmasuk') }}">Jurnal Kas Masuk</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create New Jurnal Kas Masuk</li>
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
                    <h5 class="mb-0">Create New Jurnal Kas Masuk</h5>
                    <div class="ms-auto">
                        <button id="btnCariInvoice" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-search fa-regular"></i> Cari Invoice Belum Lunas
                        </button>
                    </div>
                </div>
                 
                <form action="javascript:void(0)" enctype="multipart/form-data" id="form_data">
                    @csrf
                    @method("post")
                    <input type="hidden" name='jurnal_id_jp' id="jurnal_id_jp">
                    <div class="card-body">

                        <!-- 🔄 Ganti Tahun → Periode -->
                        <div class="row mb-3">
                            <label for="tanggal" class="col-sm-3 col-form-label">Tanggal <b class="text-danger">*</b></label>
                            <div class="col-sm-9">
                                <input type="text" id="tanggal" name="tanggal" class="form-control" 
                                       placeholder="Pilih periode (YYYY-MM-DD)" readonly required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="entitas_id" class="col-sm-3 col-form-label">Entitas <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="entitas_id" id="entitas_id" class="form-control entitas">
                                    <option value="">-- Pilih Entitas --</option>
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
                            <label for="cabang_id" class="col-sm-3 col-form-label">Cabang <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="cabang_id" id="cabang_id" class="form-control cabang">
                                    <option value="">-- Pilih Cabang --</option>
                                </select>
                            </div>
                        </div>

                        

                        <div class="row mb-3">
                            <label for="no_invoice" class="col-sm-3 col-form-label">No Invoice </label>
                            <div class="col-sm-9">
                                <input type="text"  class="form-control"  id="no_invoice_t" placeholder="No Invoice" />
                                <input type="hidden"  class="form-control" id="no_invoice" name="no_invoice" placeholder="No Invoice" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="keterangan" class="col-sm-3 col-form-label">Keterangan </label>
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
                                            <button type="button" data-piutang='' class="btn btn-danger btn-sm btn-hapus"><i class="fas fa-trash"></i></button>
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
                        <a href="{{ route('jurnal.kasmasuk') }}" class="btn btn-danger btn-flat btn-sm">
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
                                <button type="button" data-piutang='' class="btn btn-danger btn-sm btn-hapus"><i class="fas fa-trash"></i></button>
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

@endsection

@section('js')

<script>
$(document).ready(function() {
    $(".cabang").prop("disabled",false);
    $('#btnCariInvoice').click(function() {
        const partner = $('#partner_id').val();
        if (!partner) {
            Swal.fire('Oops', 'Pilih partner terlebih dahulu!', 'warning');
            return;
        }
        

        $('#modalInvoice').modal('show');
        loadInvoiceTable(partner);
    });

    // 🔹 Inisialisasi tanggal
    flatpickr("#tanggal", {
        altInput: true,
        altFormat: "d F Y",
        dateFormat: "Y-m-d",
        allowInput: false,
        locale: "id"
    });

    // 🔹 Select2 Entitas
    $('.entitas').select2({
        ajax: {
            url: '{{ route("entitas.select") }}',
            dataType: 'json',
            delay: 250,
            processResults: data => ({
                results: data.map(q => ({ id: q.id, text: `${q.nama}` }))
            }),
            cache: true
        },
        theme: 'bootstrap4',
        width: '100%',
        placeholder: "-- Pilih Entitas --",
        allowClear: true
    });

    // === Jika entitas dipilih, reset partner ===
    $('.entitas').on('change', function () {
        // kosongkan partner
        $('.partner').val(null).trigger('change');
    });

    // 🔹 Select2 Cabang
    $('.cabang').select2({
        ajax: {
            url: '{{ route("cabang.select") }}',
            dataType: 'json',
            delay: 250,
            processResults: data => ({
                results: data.map(q => ({ id: q.id, text: `${q.nama}` }))
            }),
            cache: true
        },
        theme: 'bootstrap4',
        width: '100%',
        placeholder: "-- Pilih Cabang --",
        allowClear: true
    });

    // 🔹 Select2 Partner
    $('.partner').select2({
        ajax: {
            url: '{{ route("partner.select") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // teks yang diketik user
                    jenis: 'all', // teks yang diketik user
                    entitas_id: $('#entitas_id').val() || null // kirim data tambahan jika ada
                };
            },
            processResults: data => ({
                results: data.map(q => ({ id: q.id, text: q.nama }))
            }),
            cache: true
        },
        theme: 'bootstrap4',
        width: '100%',
        placeholder: "-- Pilih Partner --",
        allowClear: true
    });

    // 🔹 Select2 Akun GL (baris pertama)
    $('.akun-selectok').select2({
        ajax: {
            url: '{{ route("saldo_awal.akun_gl") }}',
            dataType: 'json',
            delay: 250,
            processResults: data => ({
                results: data.map(q => ({ id: q.id, text: `${q.no_akun} - ${q.nama}` }))
            }),
            cache: true
        },
        theme: 'bootstrap4',
        width: '100%',
        placeholder: "-- Pilih Akun GL --",
        allowClear: true
    });

    // 🔹 Submit Form
    $("#form_data").submit(function(e){
        e.preventDefault();
        proses_data(false);
    });

    // 🔹 Tambah baris baru
    $('#btnTambahBaris').click(function() {
        let idx = $('#tableDetail tbody tr').length;
        let row = $('#rowTemplate tr').clone();

        row.find('select, input').each(function() {
            let name = $(this).attr('name').replace('__INDEX__', idx);
            $(this).attr('name', name).val('');
            if ($(this).hasClass('debit-input') || $(this).hasClass('kredit-input')) $(this).val('0');
        });

        $('#tableDetail tbody').append(row);

        row.find('.akun-select').select2({
            ajax: {
                url: '{{ route("saldo_awal.akun_gl") }}',
                dataType: 'json',
                delay: 250,
                processResults: data => ({
                    results: data.map(q => ({ id: q.id, text: `${q.no_akun} - ${q.nama}` }))
                }),
                cache: true
            },
            theme: 'bootstrap4',
            width: '100%',
            placeholder: "-- Pilih Akun GL --",
            allowClear: true
        });

        reIndexRows();
    });

    // 🔹 Hapus baris
    $(document).on('click', '.btn-hapus', function() {
        if ($('#tableDetail tbody tr').length > 1) {
            const data = $(this).data();
            console.log(data);
            if(data.piutang == "ada"){
                $("#jurnal_id_jp").val('');
            }
            $(this).closest('tr').remove();
            reIndexRows();
            hitungTotal();
        }
    });

    // 🔹 Hitung total otomatis
    $(document).on('input', '.debit-input, .kredit-input', function() {
        hitungTotal();
    });
});

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
        totalDebit += parseFloat($(this).val().replace(/\./g, '').replace(/,/g, '.')) || 0;
    });

    $('.kredit-input').each(function() {
        totalKredit += parseFloat($(this).val().replace(/\./g, '').replace(/,/g, '.')) || 0;
    });

    $('#totalDebit').text(totalDebit.toLocaleString('id-ID'));
    $('#totalKredit').text(totalKredit.toLocaleString('id-ID'));
}

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
                <button type="button" data-piutang='ada' class="btn btn-danger btn-sm btn-hapus"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `;

    // Masukkan ke tabel
    $('#tableDetail tbody').append(rowPiutang);

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

/**
 * 🧠 Proses Simpan Data (AJAX)
 * @param {boolean} confirmSave - true jika user sudah konfirmasi peringatan
 */
function proses_data(confirmSave = false) {
    $(".cabang").prop("disabled",false);
    let iData = new FormData(document.getElementById("form_data"));
    if (confirmSave) iData.append('confirm', true);

    $.ajax({
        type: "POST",
        url: "{{ route('jurnal.kasmasuk.save') }}",
        data: iData,
        cache: false,
        processData: false,
        contentType: false,
        beforeSend: function () {
            $("#btn-submit").html("<i class='fa fa-spinner fa-spin'></i> Menyimpan...");
            $("#btn-submit").prop("disabled", true);
        },
        success: function(result) {
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
                setTimeout(() => {
                    window.location.href = "{{ route('jurnal.kasmasuk') }}";
                }, 1500);
            }

            // ⚠️ Jika error non-konfirmasi
            if (result.status === "error") {
                Swal.fire("Gagal!", result.message, "error");
            }
        },
        error: function(e) {
            console.log(e);
            $("#btn-submit").html("<i class='fa fa-save'></i> Simpan");
            $("#btn-submit").prop("disabled", false);
            Swal.fire("Error", e.responseJSON?.message || "Proses Data Error", "error");
        }
    });
}
</script>
@endsection
