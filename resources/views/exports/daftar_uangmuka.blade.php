<table>
    <thead>
        <tr>
            <th colspan="9" style="text-align:center; font-weight:bold; font-size:14pt">
                LAPORAN DAFTAR UANG MUKA
            </th>
        </tr>
        <tr><th colspan="9"></th></tr>
        <tr style="font-weight:bold; background:#d9ead3;">
            <th>No</th>
            <th>Akun GL</th>
            <th>Entitas</th>
            <th>Partner</th>
            <th class="text-end">Saldo</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach($data as $row)
        <tr>
            <td>{{ $no++ }}</td>
            <td>{{ $row['akun_gl'] }}</td>
            <td>{{ $row['entitas'] }}</td>
            <td>{{ $row['partner'] }}</td>
            <td class="text-center">{{ $row['saldo'] }}</td>
           
        </tr>
        @endforeach

        <tr style="font-weight:bold; background:#f0f0f0;">
            <td colspan="4" style="text-align:right">TOTAL</td>
            <td style="text-align:right">
                {{ $data->sum('saldo') }}
            </td>
        </tr>
    </tbody>
</table>
