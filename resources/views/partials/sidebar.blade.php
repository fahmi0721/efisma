<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">
        <!--begin::Brand Link-->
        <a href="../index.html" class="brand-link">
            <img src="{{ getLogoAplikasi() }}" alt="AdminLTE Logo" class="brand-image opacity-75 shadow">
            <span class="brand-text">{{ getNamaAplikasi() }}</span>
        </a>
        <!--end::Brand Link-->
    </div>
    <!--end::Sidebar Brand-->
    <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
        <!--begin::Sidebar Menu-->
        <ul
            class="nav sidebar-menu flex-column"
            data-lte-toggle="treeview"
            role="navigation"
            aria-label="Main navigation"
            data-accordion="false"
            id="navigation"
        >
          

            <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ Route::is('dashboard') ? 'active' : '' }}">
                <i class="nav-icon bi bi-speedometer"></i>
                <p>Dashboard</p>
            </a>
            </li>

            <li class="nav-header">GENERAL</li>
            <li class="nav-item">
            <a href="{{ route('setting') }}" class="nav-link {{ Route::is('setting') ? 'active' : '' }}">
                <i class="nav-icon fa fa-solid fa-gear"></i>
                <p>Pengaturan Umum</p>
            </a>
            </li>
            
            <li class="nav-item {{ setActive(['entitas', 'entitas.create', 'entitas.edit','partner', 'partner.create', 'partner.edit','m_akun', 'm_akun.create', 'm_akun.edit','m_akun.map','m_akun.transaksi'], 'menu-open')  }}">
                <a href="#" class="nav-link {{ setActive(['entitas', 'entitas.create', 'entitas.edit','partner', 'partner.create', 'partner.edit','m_akun', 'm_akun.create', 'm_akun.edit','m_akun.map','m_akun.transaksi'], 'active')  }}">
                    <i class="nav-icon bi bi-clipboard-fill"></i>
                    <p>
                    Master Data
                    <i class="nav-arrow bi bi-chevron-right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                    <a href="{{ route('entitas') }}" class="nav-link {{  setActive(['entitas', 'entitas.create', 'entitas.edit'], 'active')  }}">
                        <i class="nav-icon fa fa-chevron-right fa-reguler"></i>
                        <p>Entitas</p>
                    </a>
                    </li>
                    <li class="nav-item">
                    <a href="{{ route('cabang') }}" class="nav-link {{  setActive(['cabang', 'cabang.create', 'cabang.edit'], 'active')  }}">
                        <i class="nav-icon fa fa-chevron-right fa-reguler"></i>
                        <p>Cabang</p>
                    </a>
                    </li>
                    <li class="nav-item">
                    <a href="{{ route('partner') }}" class="nav-link {{  setActive(['partner', 'partner.create', 'partner.edit'], 'active')  }}">
                        <i class="nav-icon fa fa-chevron-right fa-reguler"></i>
                        <p>Partner</p>
                    </a>
                    </li>
                    <li class="nav-item">
                    <a href="{{ route('m_akun') }}" class="nav-link {{  setActive(['m_akun', 'm_akun.create', 'm_akun.edit','m_akun.map','m_akun.transaksi'], 'active')  }}">
                        <i class="nav-icon fa fa-chevron-right fa-reguler"></i>
                        <p>No Akun GL</p>
                    </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item">
            <a href="{{ route('saldo_awal') }}" class="nav-link {{  setActive(['saldo_awal', 'saldo_awal.create', 'saldo_awal.edit','saldo_awal.form_import'], 'active')  }}">
                <i class="nav-icon fa fa-solid fa-usd"></i>
                <p>Saldo Awal</p>
            </a>
            </li>

            <li class="nav-item">
            <a href="{{ route('periode_akuntansi') }}" class="nav-link {{  setActive(['periode_akuntansi', 'periode_akuntansi.create', 'periode_akuntansi.edit'], 'active')  }}">
                <i class="nav-icon fa fa-calendar"></i>
                <p>Periode Akutansi</p>
            </a>
            </li>

            <li class="nav-header">TRANSAKSI</li>
            <li class="nav-item">
            <a href="{{ route('jurnal.pendapatan') }}" class="nav-link {{  setActive(['jurnal.pendapatan', 'jurnal.pendapatan.create', 'jurnal.pendapatan.edit'], 'active')  }}">
                <i class="nav-icon fa fa-book"></i>
                <p>Jurnal Pendapatan</p>
            </a>
            </li>
            <li class="nav-item">
            <a href="{{ route('jurnal.kasmasuk') }}" class="nav-link {{  setActive(['jurnal.kasmasuk', 'jurnal.kasmasuk.create', 'jurnal.kasmasuk.edit'], 'active')}}">
                <i class="nav-icon fa fa-book"></i>
                <p>Jurnal Kas Masuk</p>
            </a>
            </li>
            <li class="nav-item">
            <a href="{{ route('jurnal.kaskeluar') }}" class="nav-link {{  setActive(['jurnal.kaskeluar', 'jurnal.kaskeluar.create', 'jurnal.kaskeluar.edit'], 'active')}}">
                <i class="nav-icon fa fa-book"></i>
                <p>Jurnal Kas Keluar</p>
            </a>
            </li>
            <li class="nav-item">
            <a href="{{ route('jurnal.penyesuaian') }}" class="nav-link {{  setActive(['jurnal.penyesuaian', 'jurnal.penyesuaian.create', 'jurnal.penyesuaian.edit'], 'active')}}">
                <i class="nav-icon fa fa-book"></i>
                <p>Jurnal Penyesuaian</p>
            </a>
            </li>

            <li class="nav-header">LAPORAN</li>
            <li class="nav-item {{ setActive(['piutang.aging','piutang.daftar'], 'menu-open')  }}">
                <a href="#" class="nav-link {{ setActive(['piutang.aging','piutang.daftar'], 'active')  }}">
                    <i class="nav-icon fa fa-book"></i>
                    <p>
                    Piutang
                    <i class="nav-arrow bi bi-chevron-right"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                    <a href="{{ route('piutang.aging') }}" class="nav-link {{  setActive(['piutang.aging'], 'active')  }}">
                        <i class="nav-icon fa fa-chevron-right fa-reguler"></i>
                        <p>Aging Piutang</p>
                    </a>
                    </li>
                    <li class="nav-item">
                    <a href="{{ route('piutang.daftar') }}" class="nav-link {{  setActive(['piutang.daftar'], 'active')  }}">
                        <i class="nav-icon fa fa-chevron-right fa-reguler"></i>
                        <p>Daftar Piutang</p>
                    </a>
                    </li>
                    
                </ul>
            </li>

            <li class="nav-item">
            <a href="{{ route('laporan.laba_rugi') }}" class="nav-link {{  setActive(['laporan.laba_rugi'], 'active')  }}">
                <i class="nav-icon fa fa-solid fa-book"></i>
                <p>PBL</p>
            </a>
            </li>

            <li class="nav-item">
            <a href="{{ route('laporan.neraca') }}" class="nav-link {{  setActive(['laporan.neraca'], 'active')  }}">
                <i class="nav-icon fa fa-solid fa-book"></i>
                <p>Neraca</p>
            </a>
            </li>

            <li class="nav-item">
            <a href="{{ route('laporan.arus_kas') }}" class="nav-link {{  setActive(['laporan.arus_kas'], 'active')  }}">
                <i class="nav-icon fa fa-solid fa-book"></i>
                <p>Arus Kas</p>
            </a>
            </li>

            <li class="nav-item">
            <a href="{{ route('laporan.bukubesar') }}" class="nav-link {{  setActive(['laporan.bukubesar'], 'active')  }}">
                <i class="nav-icon fa fa-solid fa-book"></i>
                <p>Buku Besar</p>
            </a>
            </li>

            
        </ul>
        <!--end::Sidebar Menu-->
        </nav>
    </div>
    <!--end::Sidebar Wrapper-->
    </aside>