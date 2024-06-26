<?php

namespace App\Controllers\Dosen\Proposal;

use App\Controllers\BaseController;

use App\Libraries\Access_API; // Import library

class Validasi_Usulan extends BaseController
{
    public function __construct()
    {
        $this->api = new Access_API();
        $this->db = \Config\Database::connect();
    }
    public function index()
    {
        if (session()->get('ses_id') == '' || session()->get('ses_login') == 'mahasiswa') {
            return redirect()->to('/');
        }

        $pengajuan_pembimbing_1 = $this->db->query("SELECT * FROM `tb_pengajuan_pembimbing` WHERE nip = '" . session()->get('ses_id') . "'  AND sebagai = 1 AND status_pengajuan ='diterima' AND pesan IS NULL")->getResult();
        $pengajuan_pembimbing_2 = $this->db->query("SELECT * FROM `tb_pengajuan_pembimbing` WHERE nip = '" . session()->get('ses_id') . "'  AND sebagai = 2 AND status_pengajuan ='diterima' AND pesan IS NULL")->getResult();
        $jumlah_p1 = $this->db->query("SELECT * FROM tb_jumlah_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 1'")->getResult();
        $jumlah_p2 = $this->db->query("SELECT * FROM tb_jumlah_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 2'")->getResult();
        if (empty($pengajuan_pembimbing_1)) {
            $jumlah_pem1 = 0;
        } else {
            $jumlah_pem1 = count($pengajuan_pembimbing_1);
        }
        if (count($jumlah_p1) > 0) {
            if ($jumlah_pem1 != $jumlah_p1[0]->jumlah) {
                $this->db->query("UPDATE tb_jumlah_pembimbing SET jumlah=$jumlah_pem1 WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 1'");
            }
        }
        if (empty($pengajuan_pembimbing_2)) {
            $jumlah_pem2 = 0;
        } else {
            $jumlah_pem2 = count($pengajuan_pembimbing_2);
        }
        if (count($jumlah_p2) > 0) {
            if ($jumlah_pem2 != $jumlah_p2[0]->jumlah) {
                $this->db->query("UPDATE tb_jumlah_pembimbing SET jumlah=$jumlah_pem2 WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 2'");
            }
        }
        $jumlah_pembimbing_p1 = $this->db->query("SELECT * FROM tb_jumlah_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 1'")->getResult();
        $jumlah_pembimbing_p2 = $this->db->query("SELECT * FROM tb_jumlah_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 2'")->getResult();
        // if (!empty($jumlah_pembimbing_p1)) {
        //     if ($jumlah_pembimbing_p1[0]->jumlah >= $jumlah_pembimbing_p1[0]->kuota) {
        //         $this->db->query("DELETE FROM  `tb_pengajuan_pembimbing` WHERE nip = '" . session()->get('ses_id') . "'  AND sebagai = 1 AND status_pengajuan ='menunggu' AND pesan IS NULL");
        //     }
        // }

        // if (!empty($jumlah_pembimbing_p2)) {
        //     if ($jumlah_pembimbing_p2[0]->jumlah >= $jumlah_pembimbing_p2[0]->kuota) {
        //         $this->db->query("DELETE FROM  `tb_pengajuan_pembimbing` WHERE nip = '" . session()->get('ses_id') . "'  AND sebagai = 2 AND status_pengajuan ='menunggu' AND pesan IS NULL");
        //     }
        // }

        $data = [
            'title' => 'Validasi Usulan',
            'db' => $this->db,
            'jumlah_pembimbing_p1' => $jumlah_pembimbing_p1,
            'jumlah_pembimbing_p2' => $jumlah_pembimbing_p2,
            'data_menunggu' => $this->db->query("SELECT a.*,b.*,c.*,c.`nama` AS nama_topik,d.nama as nama_mhs FROM tb_pengajuan_pembimbing a LEFT JOIN tb_pengajuan_topik b ON a.`nim`=b.nim LEFT JOIN tb_topik c ON b.`id_topik`=c.`idtopik` LEFT JOIN tb_mahasiswa d ON a.`nim`=d.`nim` WHERE nip='" . session()->get('ses_id') . "' AND status_pengajuan='menunggu'")->getResult(),
            'data_ditolak' => $this->db->query("SELECT a.*,b.nim AS nim,b.`nip` AS nip,sebagai,status_pengajuan,pesan,reject_at,c.`nama`,e.`nama` AS nama_topik,d.`judul_topik` AS judul FROM tb_penolakan_pengajuan_pembimbing a  LEFT JOIN tb_pengajuan_pembimbing b ON a.`id_pengajuan_pembimbing`=b.`id_pengajuan_pembimbing` LEFT JOIN tb_mahasiswa c ON b.`nim`=c.`nim` LEFT JOIN tb_pengajuan_topik d ON b.`nim`=d.`nim` LEFT JOIN tb_topik e ON d.`id_topik`=e.idtopik WHERE b.nip='" . session()->get('ses_id') . "'")->getResult(),
            'data_diterima' => $this->db->query("SELECT d.judul_topik,b.`id_pengajuan_pembimbing`, b.`nim`,c.`nama`,b.`nip`,b.`agree_at`,b.`sebagai`,d.`berkas`,b.`status_pengajuan`,e.`nama` AS nama_topik, b.pesan
            FROM tb_pengajuan_pembimbing b LEFT JOIN tb_mahasiswa c ON b.`nim`=c.`nim` LEFT JOIN tb_pengajuan_topik d ON b.`nim`=d.`nim` LEFT JOIN tb_topik e ON d.`id_topik`=e.idtopik WHERE b.nip='" . session()->get('ses_id') . "' AND status_pengajuan='diterima' AND b.pesan IS NULL")->getResult()
        ];
        return view('Dosen/Proposal/Validasi_Usulan', $data);
    }
    public function setujui_validasi($id)
    {
        if (session()->get('ses_id') == '' || session()->get('ses_login') == 'mahasiswa') {
            return redirect()->to('/');
        }
        // dd($jumlah_pembimbing_p1[0]->jumlah);

        $data = $this->db->query("SELECT * FROM tb_pengajuan_pembimbing WHERE id_pengajuan_pembimbing=$id")->getResult();
        if ($data[0]->sebagai == 1) {
            $jumlah_pembimbing_p1 = $this->db->query("SELECT * FROM tb_jumlah_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 1'")->getResult();
            $pengajuan_pembimbing_1 = $this->db->query("SELECT * FROM tb_pengajuan_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='1' AND status_pengajuan='diterima'")->getResult();
            // if (count($pengajuan_pembimbing_1) != $jumlah_pembimbing_p1[0]->jumlah) {
            //     $this->db->query("UPDATE tb_jumlah_pembimbing SET jumlah='" . count($pengajuan_pembimbing_1) . "' WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 1'");
            // }
            if ($jumlah_pembimbing_p1[0]->jumlah >= $jumlah_pembimbing_p1[0]->kuota) {
                session()->setFlashdata('message_pem1', '
                <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
            <span class="alert-inner--icon"><i class="fe fe-slash"></i></span>
            <span class="alert-inner--text"><strong>Gagal!</strong> Kuota Dosen Pembimbing 1 Anda penuh</span>
            <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            </div>');
                return redirect()->to('/validasi_usulan');
            }
        } else {
            $jumlah_pembimbing_p2 = $this->db->query("SELECT * FROM tb_jumlah_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 2'")->getResult();
            $pengajuan_pembimbing_2 = $this->db->query("SELECT * FROM tb_pengajuan_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='2' AND status_pengajuan='diterima'")->getResult();
            if (count($pengajuan_pembimbing_2) != $jumlah_pembimbing_p2[0]->jumlah) {
                $this->db->query("UPDATE tb_jumlah_pembimbing SET jumlah='" . count($pengajuan_pembimbing_2) . "' WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 2'");
            }
            if ($jumlah_pembimbing_p2[0]->jumlah >= $jumlah_pembimbing_p2[0]->kuota) {
                session()->setFlashdata('message_pem1', '
                <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
            <span class="alert-inner--icon"><i class="fe fe-slash"></i></span>
            <span class="alert-inner--text"><strong>Gagal!</strong> Kuota Dosen Pembimbing 2 Anda penuh</span>
            <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            </div>');
                return redirect()->to('/validasi_usulan');
            }
        }
        $this->db->query("UPDATE tb_pengajuan_pembimbing SET status_pengajuan='diterima',agree_at=now() WHERE id_pengajuan_pembimbing=$id");
        if ($data[0]->sebagai == '1') {
            $jumlah_pembimbing_p1 = $this->db->query("SELECT * FROM tb_jumlah_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 1'")->getResult();
            $jumlah = $jumlah_pembimbing_p1[0]->jumlah + 1;
            $this->db->query("UPDATE tb_jumlah_pembimbing SET jumlah='$jumlah' WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 1'");
            $jumlah_pembimbing_p1 = $this->db->query("SELECT * FROM tb_jumlah_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 1'")->getResult();
            // if ($jumlah_pembimbing_p1[0]->jumlah >= $jumlah_pembimbing_p1[0]->kuota) {
            //     $datatunggu = $this->db->query("SELECT * FROM tb_pengajuan_pembimbing a LEFT JOIN tb_pengajuan_topik b ON a.`nim`=b.`nim` WHERE a.nip='" . session()->get('ses_id') . "' AND a.sebagai='1' AND a.status_pengajuan='menunggu'")->getResult();
            //     foreach ($datatunggu as $key) {
            //         $this->db->query("UPDATE tb_pengajuan_pembimbing SET status_pengajuan='ditolak',pesan='Maaf! Bimbingan penuh',reject_at=now() WHERE id_pengajuan_pembimbing=$key->id_pengajuan");
            //         $this->db->query("INSERT INTO tb_penolakan_pengajuan_pembimbing (id_pengajuan_pembimbing,berkas) VALUES ('$key->id_pengajuan','$key->berkas')");
            //     }
            // }
        } else {
            $jumlah_pembimbing_p2 = $this->db->query("SELECT * FROM tb_jumlah_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 2'")->getResult();
            $jumlah = $jumlah_pembimbing_p2[0]->jumlah + 1;
            $this->db->query("UPDATE tb_jumlah_pembimbing SET jumlah='$jumlah' WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 2'");
            $jumlah_pembimbing_p2 = $this->db->query("SELECT * FROM tb_jumlah_pembimbing WHERE nip='" . session()->get('ses_id') . "' AND sebagai='pembimbing 2'")->getResult();
            // if ($jumlah_pembimbing_p2[0]->jumlah >= $jumlah_pembimbing_p2[0]->kuota) {
            //     $datatunggu = $this->db->query("SELECT * FROM tb_pengajuan_pembimbing a LEFT JOIN tb_pengajuan_topik b ON a.`nim`=b.`nim` WHERE a.nip='" . session()->get('ses_id') . "' AND a.sebagai='2' AND a.status_pengajuan='menunggu'")->getResult();
            //     foreach ($datatunggu as $key) {
            //         $this->db->query("UPDATE tb_pengajuan_pembimbing SET status_pengajuan='ditolak',pesan='Maaf! Bimbingan penuh',reject_at=now() WHERE id_pengajuan_pembimbing=$key->id_pengajuan_pembimbing");
            //         $this->db->query("INSERT INTO tb_penolakan_pengajuan_pembimbing (id_pengajuan_pembimbing,berkas) VALUES ('$key->id_pengajuan_pembimbing','$key->berkas')");
            //     }
            // }
        }
        return redirect()->to('/validasi_usulan');
    }
    public function tolak_validasi()
    {
        $id_pengajuan = $this->request->getPost("id_pengajuan");
        $berkas = $this->request->getPost("berkas");
        $pesan = $this->request->getPost("pesan");
        if (session()->get('ses_id') == '' || session()->get('ses_login') == 'mahasiswa') {
            return redirect()->to('/');
        }
        $this->db->query("UPDATE tb_pengajuan_pembimbing SET status_pengajuan='ditolak',pesan='$pesan',reject_at=now() WHERE id_pengajuan_pembimbing=$id_pengajuan");
        $this->db->query("INSERT INTO tb_penolakan_pengajuan_pembimbing (id_pengajuan_pembimbing,berkas) VALUES ('$id_pengajuan','$berkas')");
        return redirect()->to('/validasi_usulan');
    }
    public function download($jenis, $id)
    {
        if (session()->get('ses_id') == '' || session()->get('ses_login') == 'mahasiswa') {
            return redirect()->to('/');
        }
        if ($jenis == 'menunggu') {
            $data = $this->db->query("SELECT * FROM tb_pengajuan_pembimbing a left join tb_pengajuan_topik b on a.nim=b.nim where id_pengajuan_pembimbing=$id")->getResult();
        } elseif ($jenis == 'tolak_pengajuan') {
            $data = $this->db->query("SELECT * FROM tb_penolakan_pengajuan_pembimbing where id_penolakan_pengajuan_pembimbing=$id")->getResult();
        } else {
            $data = $this->db->query("SELECT * FROM tb_pengajuan_pembimbing a left join tb_pengajuan_topik b on a.nim=b.nim where id_pengajuan_pembimbing=$id")->getResult();
        }
        return $this->response->download(FCPATH . 'berkas/' . $data[0]->berkas, null);
    }
}
