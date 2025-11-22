<table>
    <thead>
        <tr>
            <th colspan="9" style="text-align:center; font-weight:bold; font-size:14pt">
                LAPORAN BUKU BESAR
            </th>
        </tr>
        <tr>
            <th colspan="9" style="text-align:center; font-weight:bold; font-size:12pt">
                Periode: {{ \Carbon\Carbon::parse($periode . '-01')->translatedFormat('F Y') }}
            </th>
        </tr>
        <tr><th colspan="9"></th></tr>
        <tr>
            <th>No</th>
            <th>Kode Jurnal</th>
            <th>Akun</th>
            <th>Entitas</th>
            <th>Partner</th>
            <th>Keterangan</th>
            <th>Tanggal</th>
            <th>Debit</th>
            <th>Kredit</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach ($data as $row)
        <tr>
            <td>{{ $no++ }}</td>
            <td>{{ $row->kode_jurnal }}</td>
            <td>{{ $row->akun_gl }}</td>
            <td>{{ $row->entitas }}</td>
            <td>{{ $row->partner }}</td>
            <td>{{ $row->keterangan }}</td>
            <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}</td>
            <td>{{ $row->debit }}</td>
            <td>{{ $row->kredit}}</td>
        </tr>
        @endforeach
    </tbody>
</table>
