@extends('layouts.app')
@section('title','Update Data Akun GL')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Update Data Akun GL</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('m_akun') }}">Akun GL</a></li>
            <li class="breadcrumb-item active" aria-current="page">Update</li>
        </ol>
        </div>
    </div>
    <!--end::Row-->
    </div>
    <!--end::Container-->
</div>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-8">
            <div class="card card-success card-outline mb-4">
                <div class="card-header"><div class="card-title">Update Data Akun GL </div></div>
                <!--end::Header-->
                <!--begin::Form-->
                <form action='javascript:void(0)'  id="form_data">
                    @csrf
                    @method("put")
                    <input type="hidden" value="{{ $id }}" id='id' name='id'>
                    <!--begin::Body-->
                    <div class="card-body">
                        <div class="row mb-3">
                            <label for="nomor_akun" class="col-sm-3 col-form-label">Nomor Akun <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text" value="{{ $data->no_akun  }}" class="form-control" id="nomor_akun" name="nomor_akun" placeholder="Nomor Akun" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="nama_akun" class="col-sm-3 col-form-label">Nama Akun <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $data->nama  }}" id="nama_akun" name="nama_akun" placeholder="Nama Akun" />
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="tipe_akun" class="col-sm-3 col-form-label">Tipe Akun <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="tipe_akun" id="tipe_akun" class="form-control tipe_akun">
                                    <option value="">-- Pilih Tipe Akun --</option>
                                    <option value="aktiva" @if($data->tipe_akun == 'aktiva') selected @endif>Aktiva</option>
                                    <option value="pasiva" @if($data->tipe_akun == 'pasiva') selected @endif>Pasiva</option>
                                    <option value="modal" @if($data->tipe_akun == 'modal') selected @endif>Modal</option>
                                    <option value="pendapatan" @if($data->tipe_akun == 'pendapatan') selected @endif>Pendapatan</option>
                                    <option value="beban" @if($data->tipe_akun == 'beban') selected @endif>Beban</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="kategori" class="col-sm-3 col-form-label">Kategori <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="kategori" id="kategori" class="form-control kategori">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="kas_bank" @if($data->kategori == 'kas_bank') selected @endif>Kas / Bank</option>
                                    <option value="piutang" @if($data->kategori == 'piutang') selected @endif>Piutang</option>
                                    <option value="investasi" @if($data->kategori == 'investasi') selected @endif>Investasi</option>
                                    <option value="pendanaan" @if($data->kategori == 'pendanaan') selected @endif>Pendanaan</option>
                                    <option value="uang_muka" @if($data->kategori == 'uang_muka') selected @endif>Uang Muka</option>
                                    <option value="deposito_customer" @if($data->kategori == 'deposito_customer') selected @endif>Deposito Customer</option>
                                    <option value="pendapatan_operasional" @if($data->kategori == 'pendapatan_operasional') selected @endif>Pendapatan Operasional</option>
                                    <option value="beban_operasional" @if($data->kategori == 'beban_operasional') selected @endif>Beban Operasional</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="saldo_normal" class="col-sm-3 col-form-label">Saldo Normal <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="saldo_normal" id="saldo_normal" class="form-control saldo_normal">
                                    <option value="">-- Pilih Saldo Normal --</option>
                                    <option value="debet" @if($data->saldo_normal == 'debet') selected @endif>Debet</option>
                                    <option value="kredit" @if($data->saldo_normal == 'kredit') selected @endif>Kredit</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="parent_akun" class="col-sm-3 col-form-label">Parent Akun</label>
                            <div class="col-sm-9">
                                <select name="parent_akun" id="parent_akun" class="form-control parent_akun">
                                    <option value="">-- Pilih Parent Akun --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!--end::Body-->
                    <!--begin::Footer-->
                    <div class="card-footer">
                      <a href="{{ route('m_akun') }}"  class="btn btn-danger btn-flat btn-sm"><i class="fa fa-mail-reply"></i> Kembali</a>
                      <button type="submit" id="btn-submit" class="btn btn-success btn-flat btn-sm float-end"><i class="fa fa-save"></i> Simpan</button>
                  </div>
                    <!--end::Footer-->
                </form>
                <!--end::Form-->
            </div>  
        </div>
    </div>    
</div>
@endsection
@section('js')
<script>
    $(document).ready(function() {
        $('.saldo_normal').select2({
            theme: 'bootstrap4',  
            width: '100%',
            placeholder: "-- Pilih Saldo Normal --",
            allowClear: true
        });

        $('.tipe_akun').select2({
            theme: 'bootstrap4', 
            width: '100%',
            placeholder: "-- Pilih Tipe Akun --",
            allowClear: true
        });

        $('.kategori').select2({
            theme: 'bootstrap4', 
            width: '100%',
            placeholder: "-- Pilih Kategori --",
            allowClear: true
        });

         $('.parent_akun').select2({
            ajax: {
                url: '{{ route("m_akun.search") }}',
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: data.map(function(m_akun){
                            return {id: m_akun.id, text: m_akun.no_akun + " - " + m_akun.nama};
                        })
                    };
                },
                cache: true
            },
            theme: 'bootstrap4', 
            width: '100%',
            placeholder: "-- Pilih Paren Akun --",
            allowClear: true
        });
        
        @if(!empty($selected))
            var selected = "{{ $selected->id }}";
            console.log(selected)
            var option = new Option("{{ $selected->nama }}", {{ $selected->id }}, true, true);
            $(".parent_akun").append(option).trigger('change');    
        @endif

        
    });
    proses_data = function(){
        let iData = $("#form_data").serialize();
        var id = $("#id").val();
        $.ajax({
            type    : "POST",
            url     : "{{ route('m_akun.update', ':id') }}".replace(':id', id),
            data    : iData,
            cache   : false,
            beforeSend  : function (){
                $("#btn-submit").html("<i class='fa fa-spinner fa-spin'></i>  Simpan..")
                $("#btn-submit").prop("disabled",true);
            },
            success: function(result){
                console.log(result)
                if(result.status == "success"){
                    position = "bottom-left";
                    icons = result.status;
                    pesan = result.messages;
                    title = "Updated!";
                    info(title,pesan,icons,position);
                    $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                    $("#btn-submit").prop("disabled",false);
                    setTimeout(() => {
                        window.location.href = "{{ route('m_akun') }}";
                    }, 2000);
                    
                }
            },
            error: function(e){
                console.log(e)
                $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                $("#btn-submit").prop("disabled",false);
                error_message(e,'Server Error!');
            }
        })
    }

    $(function() {
        $("#form_data").submit(function(e){
            e.preventDefault();
            proses_data();
        });
    });
</script>
@endsection

