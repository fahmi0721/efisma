@extends('layouts.app')

@section('title', 'Akses Ditolak')

@section('content')
<center>
<div class="content-wrapper mt-5">

    <section class="content-header">
        <h1 class="text-danger">
            403 Forbidden
            <small>Akses ditolak</small>
        </h1>
    </section>

    <section class="content">

        <div class="error-page">
            <h2 class="headline text-danger">403</h2>

            <div class="error-content">
                <h3>
                    <i class="fas fa-ban text-danger"></i>
                    Kamu tidak memiliki izin untuk mengakses halaman ini.
                </h3>

                <p>
                    Silakan hubungi administrator jika kamu membutuhkan akses tambahan.
                    <br><br>

                    <a href="{{ url()->previous() }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>

                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </p>
            </div>
        </div>

    </section>

</div>
</center>
@endsection
