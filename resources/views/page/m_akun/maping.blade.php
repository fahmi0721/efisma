@extends('layouts.app')
@section('title','Maping Akun GL')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Maping Akun GL</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('m_akun') }}">Akun GL</a></li>
            <li class="breadcrumb-item active" aria-current="page">Maing Akun GL</li>
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
        <div class="col-12">
            <div class="card card-success card-outline mb-4">
                <div class="card-header d-flex  align-items-center">
                    <h5 class="mb-0">Data Maping Akun GL</h5>
                    <div class=' ms-auto'>
                        <a href="{{ route('m_akun') }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-mail-reply"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tb_data" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID - No Akun - Nama Akun</th>
                                <th>Tipe</th>
                                <th>Saldo Normal</th>
                                <th>Kategori</th>
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
    load_data();
});

load_data = function(){
    $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        paging: false,
        ajax: "{{ route('m_akun.map') }}",
        columns: [
            { data: 'aksi', name: 'aksi', orderable: false, },
            { data: 'tipe_akun', name: 'tipe_akun', orderable: false, },
            { data: 'saldo_normal', name: 'saldo_normal', orderable: false, },
            { data: 'kategori', name: 'kategori', orderable: false, },
        ]
    });
    
}
</script>
@endsection

