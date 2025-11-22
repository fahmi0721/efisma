function info(title,pesan,icons){
    Swal.fire({
        title: title,
        text: pesan,
        icon: icons,
        width: '380px',     // ubah lebar
        padding: '1rem',
        buttonsStyling: false, // penting agar custom class bekerja
        confirmButtonText: 'OK',
        customClass: {
            confirmButton: 'btn btn-success btn-sm' // kelas bootstrap
        }
    });
}

function error_message(e, title = "") {
    let pesan = "";
    let icons = "error";

    if (e.status == 401) {
        pesan = e.responseJSON?.messages || "Unauthorized access";
    } 
    else if (e.status == 405 || e.status == 500 || e.status == 419) {
        if (e.responseJSON && "messages" in e.responseJSON) {
            pesan = e.responseJSON.messages;
        } else {
            pesan = e.responseText || "Terjadi kesalahan pada server";
        }
    } else if(e.status == 501 || e.status == 422){
        pesan = e.responseJSON?.message || "Terjadi kesalahan pada server";
    }
    else {
        pesan = "Error 505";
    }

    Swal.fire({
        title: title,
        text: pesan,
        icon: icons,
        width: '380px',
        padding: '1rem',
        buttonsStyling: false,
        confirmButtonText: 'OK',
        customClass: {
            confirmButton: 'btn btn-success btn-sm'
        }
    });
}


function formatRupiah(el) {
    let value = el.value.replace(/[^,\d]/g, '');  // Hapus karakter selain angka dan koma
    let parts = value.split(',');
    let numberString = parts[0];
    let remainder = numberString.length % 3;
    let rupiah = numberString.substr(0, remainder);
    let thousands = numberString.substr(remainder).match(/\d{3}/g);

    if (thousands) {
        rupiah += (remainder ? '.' : '') + thousands.join('.');
    }

    // Tambahkan koma jika ada bagian desimal
    rupiah = parts[1] !== undefined ? rupiah + ',' + parts[1] : rupiah;

    el.value = rupiah;
}

function formatRupiahB(angka) {
    if (!angka) return '0';
    return Number(angka).toLocaleString('id-ID');
}

function validasiTahun(el) {
    // Hanya izinkan angka
    let value = el.value.replace(/[^0-9]/g, '');

    // Batasi maksimal 4 digit
    if (value.length > 4) {
        value = value.substr(0, 4);
    }

    el.value = value;
}