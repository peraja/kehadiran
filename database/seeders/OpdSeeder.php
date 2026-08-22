<?php

namespace Database\Seeders;

use App\Models\Opd;
use Illuminate\Database\Seeder;

class OpdSeeder extends Seeder
{
    /**
     * Seed official master OPD data of Pemerintah Kabupaten Sinjai.
     */
    public function run(): void
    {
        $opds = [
            ['unit_id' => '730707', 'name' => 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Aparatur', 'address' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730729', 'name' => 'Badan Kesatuan Bangsa dan Politik', 'address' => 'Jl. Persatuan Raya No. 101, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730711', 'name' => 'Badan Keuangan dan Aset Daerah', 'address' => 'Jl. Jend. Ahmad Yani No. 1, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730710', 'name' => 'Badan Penanggulangan Bencana Daerah', 'address' => 'Jl. Bulo-Bulo Timur No. 1, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730715', 'name' => 'Badan Pendapatan Daerah', 'address' => 'Jl. Bulo-Bulo Barat No. 1, Sinjai Utara, Kab. Sinjai 92611', 'phone' => '(0482) 21004', 'email' => null],
            ['unit_id' => '730747', 'name' => 'Badan Penelitian dan Pengembangan Daerah', 'address' => 'Jl. Persatuan Raya No. 101, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730706', 'name' => 'Badan Perencanaan Pembangunan Daerah', 'address' => 'Jl. Bulo-Bulo Barat No. 1, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730726', 'name' => 'Dinas Kependudukan dan Pencatatan Sipil', 'address' => 'Jl. Persatuan Raya No. 116, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730722', 'name' => 'Dinas Kesehatan', 'address' => 'Jl. Jenderal Sudirman No. 4, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730713', 'name' => 'Dinas Ketahanan Pangan', 'address' => 'Jl. H. Abdul Latief No. 8, Sinjai Utara, Kab. Sinjai 92611', 'phone' => '(0482) 2425372', 'email' => null],
            ['unit_id' => '730714', 'name' => 'Dinas Komunikasi Informatika dan Persandian', 'address' => 'Jl. Persatuan Raya No. 101, Sinjai Utara, Kab. Sinjai 92611', 'phone' => '0482-21432', 'email' => 'info@sinjaikab.go.id'],
            ['unit_id' => '730743', 'name' => 'Dinas Koperasi Usaha Kecil Menengah dan Tenaga Kerja', 'address' => 'Jl. Jenderal Sudirman No. 19, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730731', 'name' => 'Dinas Lingkungan Hidup dan Kehutanan', 'address' => 'Jl. Persatuan Raya No. 141, Sinjai Utara, Kab. Sinjai 92611', 'phone' => '(0482) 23655', 'email' => null],
            ['unit_id' => '730746', 'name' => 'Dinas Pariwisata dan Kebudayaan', 'address' => 'Jl. Jenderal Sudirman No. 21, Sinjai Utara, Kab. Sinjai 92615', 'phone' => '(0482) 21226', 'email' => null],
            ['unit_id' => '730724', 'name' => 'Dinas Pekerjaan Umum dan Penataan Ruang', 'address' => 'Jl. Lamatti No. 1, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730708', 'name' => 'Dinas Pemberdayaan Masyarakat dan Desa', 'address' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611', 'phone' => '(0482) 23305', 'email' => null],
            ['unit_id' => '730709', 'name' => 'Dinas Pemberdayaan Perempuan, Perlindungan Anak, Pengendalian Penduduk dan Keluarga Berencana', 'address' => 'Jl. Persatuan Raya No. 101, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730745', 'name' => 'Dinas Pemuda dan Olahraga', 'address' => 'Jl. H. A. Abdul Latief No. 1, Sinjai Utara, Kab. Sinjai 92612', 'phone' => null, 'email' => null],
            ['unit_id' => '730712', 'name' => 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu', 'address' => 'Jl. Persatuan Raya No. 116, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730723', 'name' => 'Dinas Pendidikan', 'address' => 'Jl. Jenderal Sudirman No. 2, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730721', 'name' => 'Dinas Perdagangan, Perindustrian dan Energi Sumber Daya Mineral', 'address' => 'Jl. Jend. Ahmad Yani No. 1, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730716', 'name' => 'Dinas Perhubungan', 'address' => 'Jl. Bulu Pattuku, Kel. Bongki, Kec. Sinjai Utara, Kab. Sinjai 92613', 'phone' => null, 'email' => null],
            ['unit_id' => '730720', 'name' => 'Dinas Perikanan', 'address' => 'Jl. Persatuan Raya No. 98, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730730', 'name' => 'Dinas Perpustakaan dan Kearsipan', 'address' => 'Jl. Kartini No. 1, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730725', 'name' => 'Dinas Perumahan, Kawasan Pemukiman dan Pertanahan', 'address' => 'Jl. Persatuan Raya No. 116, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730717', 'name' => 'Dinas Peternakan dan Kesehatan Hewan', 'address' => 'Jl. Lamatti No. 1, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730727', 'name' => 'Dinas Sosial', 'address' => 'Jl. Jenderal Sudirman No. 3, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730718', 'name' => 'Dinas Tanaman Pangan, Holtikultura dan Perkebunan', 'address' => 'Jl. Persatuan Raya No. 121, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730705', 'name' => 'Inspektorat', 'address' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730740', 'name' => 'Kantor Kecamatan Bulupoddo', 'address' => 'Lamatti Riawang, Kec. Bulupoddo, Kab. Sinjai 92651', 'phone' => null, 'email' => null],
            ['unit_id' => '730741', 'name' => 'Kantor Kecamatan Pulau Sembilan', 'address' => 'Pulau Harapan, Kec. Pulau Sembilan, Kab. Sinjai 92655', 'phone' => null, 'email' => null],
            ['unit_id' => '730737', 'name' => 'Kantor Kecamatan Sinjai Barat', 'address' => 'Jl. Persatuan Raya No. A.69, Manipi, Kec. Sinjai Barat, Kab. Sinjai 92653', 'phone' => null, 'email' => null],
            ['unit_id' => '730738', 'name' => 'Kantor Kecamatan Sinjai Borong', 'address' => 'Jl. Pendidikan No. 64, Pasir Putih, Kec. Sinjai Borong, Kab. Sinjai 92622', 'phone' => null, 'email' => null],
            ['unit_id' => '730736', 'name' => 'Kantor Kecamatan Sinjai Selatan', 'address' => 'Jl. Persatuan Raya Bikeru No. 1B, Kec. Sinjai Selatan, Kab. Sinjai 92661', 'phone' => null, 'email' => null],
            ['unit_id' => '730735', 'name' => 'Kantor Kecamatan Sinjai Tengah', 'address' => 'Jl. Damai No. 1, Lappadata, Kec. Sinjai Tengah, Kab. Sinjai 92652', 'phone' => '(0482) 2424001', 'email' => null],
            ['unit_id' => '730734', 'name' => 'Kantor Kecamatan Sinjai Timur', 'address' => 'Jl. Abd. Latif No. 1, Sinjai Timur, Kab. Sinjai 92611', 'phone' => '(0482) 23014', 'email' => null],
            ['unit_id' => '730733', 'name' => 'Kantor Kecamatan Sinjai Utara', 'address' => 'Jl. Bulu Kunyi No. 1, Sinjai Utara, Kab. Sinjai 92612', 'phone' => '(0482) 21014', 'email' => null],
            ['unit_id' => '730739', 'name' => 'Kantor Kecamatan Tellulimpoe', 'address' => 'Mannanti, Kec. Tellu Limpoe, Kab. Sinjai 92662', 'phone' => null, 'email' => null],
            ['unit_id' => '730728', 'name' => 'Rumah Sakit Umum Daerah', 'address' => 'Jl. Jenderal Sudirman No. 47, Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730732', 'name' => 'Satuan Polisi Pamong Praja dan Pemadam Kebakaran', 'address' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611', 'phone' => '(0482) 23305', 'email' => null],
            ['unit_id' => '730701', 'name' => 'Sekretariat Daerah', 'address' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
            ['unit_id' => '730702', 'name' => 'Sekretariat DPRD', 'address' => 'Tanassang, Kel. Alehanuae, Kec. Sinjai Utara, Kab. Sinjai 92611', 'phone' => null, 'email' => null],
        ];

        foreach ($opds as $data) {
            Opd::updateOrCreate(
                ['unit_id' => $data['unit_id']],
                [
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'is_active' => true,
                ]
            );
        }

        // Set default Diskominfo leader for TTE testing
        Opd::where('unit_id', '730714')->update([
            'leader_name' => 'Testing TTE',
            'leader_nip' => '123456',
            'leader_nik' => '7307010101800001',
            'leader_title' => 'Kepala Dinas Komunikasi Informatika dan Persandian',
            'leader_rank' => 'Pembina Utama Muda (IV/c)',
            'leader_eselon' => 'II.b',
        ]);
    }
}
