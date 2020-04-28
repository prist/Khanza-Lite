<?php

namespace Plugins\Pasien;

use Systems\AdminModule;
use Systems\Lib\BpjsRequest;
use Systems\Lib\Fpdf\FPDF;
use Systems\Lib\Fpdf\PDF_MC_Table;

class Admin extends AdminModule
{
    private $assign = [];

    public function navigation()
    {
        return [
            'Kelola'    => 'manage',
            'Tambah Baru'                => 'add'
        ];
    }

    public function getManage($page = 1)
    {
        $this->_addHeaderFiles();
        $perpage = '10';
        $phrase = '';
        if(isset($_GET['s']))
          $phrase = $_GET['s'];

        // pagination
        $totalRecords = $this->core->db('pasien')->like('no_rkm_medis', '%'.$phrase.'%')->orLike('nm_pasien', '%'.$phrase.'%')->orLike('no_ktp', '%'.$phrase.'%')->orLike('no_peserta', '%'.$phrase.'%')->toArray();
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'pasien', 'manage', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination','5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        $query = $this->db()->pdo()->prepare("SELECT * FROM pasien WHERE (no_rkm_medis LIKE ? OR nm_pasien LIKE ? OR no_ktp LIKE ? OR no_peserta LIKE ?) LIMIT $perpage OFFSET $offset");
        $query->execute(['%'.$phrase.'%', '%'.$phrase.'%', '%'.$phrase.'%', '%'.$phrase.'%']);
        $rows = $query->fetchAll();

        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['editURL'] = url([ADMIN, 'pasien', 'edit', $row['no_rkm_medis']]);
                $row['delURL']  = url([ADMIN, 'pasien', 'delete', $row['no_rkm_medis']]);
                $row['viewURL'] = url([ADMIN, 'pasien', 'view', $row['no_rkm_medis']]);
                $this->assign['list'][] = $row;
            }
        }

        return $this->draw('manage.html', ['pasien' => $this->assign]);
    }

    public function getAdd()
    {
        $this->_addHeaderFiles();

        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'no_rkm_medis' => $this->_setNoRM(),
              'nm_pasien' => '',
              'no_ktp' => '',
              'jk' => '',
              'tmp_lahir' => '',
              'tgl_lahir' => '',
              'nm_ibu' => '',
              'alamat' => '',
              'gol_darah' => '',
              'pekerjaan' => '',
              'stts_nikah' => '',
              'agama' => '',
              'tgl_daftar' => '',
              'no_tlp' => '',
              'umur' => '',
              'pnd' => '',
              'keluarga' => '',
              'namakeluarga' => '',
              'kd_pj' => '',
              'no_peserta' => '',
              'kd_kel' => '',
              'kd_kec' => '',
              'kd_kab' => '',
              'pekerjaanpj' => '',
              'alamatpj' => '',
              'kelurahanpj' => '',
              'kecamatanpj' => '',
              'kabupatenpj' => '',
              'perusahaan_pasien' => '',
              'suku_bangsa' => '',
              'bahasa_pasien' => '',
              'cacat_fisik' => '',
              'email' => '',
              'nip' => '',
              'kd_prop' => '',
              'propinsipj' => ''
            ];
        }

        $this->assign['jk'] = $this->_addEnum('pasien', 'jk');
        $this->assign['gol_darah'] = $this->_addEnum('pasien', 'gol_darah');
        $this->assign['stts_nikah'] = $this->_addEnum('pasien', 'stts_nikah');
        $this->assign['pnd'] = $this->_addEnum('pasien', 'pnd');
        $this->assign['keluarga'] = $this->_addEnum('pasien', 'keluarga');
        $this->assign['agama'] = array('ISLAM', 'KRISTEN', 'HINDU', 'BUDHA');
        $this->assign['penjab'] = $this->db('penjab')->toArray();
        $this->assign['suku_bangsa'] = $this->db('suku_bangsa')->toArray();
        $this->assign['bahasa_pasien'] = $this->db('bahasa_pasien')->toArray();
        $this->assign['cacat_fisik'] = $this->db('cacat_fisik')->toArray();
        $this->assign['perusahaan_pasien'] = $this->db('perusahaan_pasien')->toArray();

        $this->assign['propinsi']['nm_prop'] = '';
        $this->assign['kabupaten']['nm_kab'] = '';
        $this->assign['kecamatan']['nm_kec'] = '';
        $this->assign['kelurahan']['nm_kel'] = '';

        $this->assign['manageURL'] = url([ADMIN, 'pasien', 'manage']);

        return $this->draw('form.html', ['pasien' => $this->assign]);
    }

    public function getEdit($no_rkm_medis)
    {
        $this->_addHeaderFiles();
        $pasien = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
        $this->assign['jk'] = $this->_addEnum('pasien', 'jk');
        $this->assign['gol_darah'] = $this->_addEnum('pasien', 'gol_darah');
        $this->assign['stts_nikah'] = $this->_addEnum('pasien', 'stts_nikah');
        $this->assign['pnd'] = $this->_addEnum('pasien', 'pnd');
        $this->assign['keluarga'] = $this->_addEnum('pasien', 'keluarga');
        $this->assign['agama'] = array('ISLAM', 'KRISTEN', 'HINDU', 'BUDHA');
        $this->assign['penjab'] = $this->db('penjab')->toArray();
        $this->assign['suku_bangsa'] = $this->db('suku_bangsa')->toArray();
        $this->assign['bahasa_pasien'] = $this->db('bahasa_pasien')->toArray();
        $this->assign['cacat_fisik'] = $this->db('cacat_fisik')->toArray();
        $this->assign['perusahaan_pasien'] = $this->db('perusahaan_pasien')->toArray();

        $this->assign['propinsi'] = $this->db('propinsi')->where('kd_prop', $pasien['kd_prop'])->oneArray();
        $this->assign['kabupaten'] = $this->db('kabupaten')->where('kd_kab', $pasien['kd_kab'])->oneArray();
        $this->assign['kecamatan'] = $this->db('kecamatan')->where('kd_kec', $pasien['kd_kec'])->oneArray();
        $this->assign['kelurahan'] = $this->db('kelurahan')->where('kd_kel', $pasien['kd_kel'])->oneArray();

        if (!empty($pasien)) {
            $this->assign['form'] = $pasien;
            $this->assign['title'] = 'Edit pasien';

            $this->assign['manageURL'] = url([ADMIN, 'pasien', 'manage']);

            return $this->draw('form.html', ['pasien' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'pasien', 'manage']));
        }
    }

    public function getView($no_rkm_medis)
    {
        $this->_addHeaderFiles();
        $this->assign['print_rm'] = url([ADMIN, 'pasien', 'print_rm', $no_rkm_medis]);
        $this->assign['print_kartu'] = url([ADMIN, 'pasien', 'print_kartu', $no_rkm_medis]);
        $pasien = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();

        $count_ralan = $this->db('reg_periksa')->where('no_rkm_medis', $no_rkm_medis)->where('status_lanjut', 'Ralan')->count();
        $count_ranap = $this->db('reg_periksa')->where('no_rkm_medis', $no_rkm_medis)->where('status_lanjut', 'Ranap')->count();

        if (!empty($pasien)) {
            $this->assign['view'] = $pasien;
            $this->assign['view']['count_ralan'] = $count_ralan;
            $this->assign['view']['count_ranap'] = $count_ranap;
            $this->assign['fotoURL'] = url('/plugins/pasien/img/'.$pasien['jk'].'.png');

            $rows = $this->db()->pdo()->prepare("SELECT b.*, a.nm_poli, c.nm_dokter FROM poliklinik a, reg_periksa b, dokter c WHERE b.no_rkm_medis = '$no_rkm_medis' AND a.kd_poli = b.kd_poli AND b.kd_dokter = c.kd_dokter ORDER BY b.tgl_registrasi DESC");
            $rows->execute();
            $rows = $rows->fetchAll();

            foreach ($rows as &$row) {
                $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')->where('no_rawat', $row['no_rawat'])->oneArray();
                $diagnosa_pasien = $this->db('diagnosa_pasien')->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')->where('no_rawat', $row['no_rawat'])->toArray();
                $rawat_jl_dr = $this->db('rawat_jl_dr')->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw = rawat_jl_dr.kd_jenis_prw')->where('no_rawat', $row['no_rawat'])->toArray();
                $catatan_perawatan = $this->db('catatan_perawatan')->where('no_rawat', $row['no_rawat'])->oneArray();
                $row['keluhan'] = $pemeriksaan_ralan['keluhan'];
                $row['suhu_tubuh'] = $pemeriksaan_ralan['suhu_tubuh'];
                $row['tensi'] = $pemeriksaan_ralan['tensi'];
                $row['nadi'] = $pemeriksaan_ralan['nadi'];
                $row['respirasi'] = $pemeriksaan_ralan['respirasi'];
                $row['tinggi'] = $pemeriksaan_ralan['tinggi'];
                $row['berat'] = $pemeriksaan_ralan['berat'];
                $row['gcs'] = $pemeriksaan_ralan['gcs'];
                $row['pemeriksaan'] = $pemeriksaan_ralan['pemeriksaan'];
                $row['rtl'] = $pemeriksaan_ralan['rtl'];
                $row['catatan_perawatan'] = $catatan_perawatan['catatan'];
                $row['diagnosa_pasien'] = $diagnosa_pasien;
                $row['rawat_jl_dr'] = $rawat_jl_dr;
                $this->assign['riwayat'][] = $row;
            }

            $this->assign['manageURL'] = url([ADMIN, 'pasien', 'manage']);

            return $this->draw('view.html', ['pasien' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'pasien', 'manage']));
        }
    }

    public function getPrint_kartu($id)
    {
      $pasien = $this->db('pasien')->where('no_rkm_medis', $id)->oneArray();
      $logo = 'data:image/png;base64,' . base64_encode($this->core->getSettings('logo'));

  		$pdf = new FPDF('L', 'mm', array(59,98));
  		$pdf->AddPage();
      $pdf->SetAutoPageBreak(true, 10);
      $pdf->SetTopMargin(5);
      $pdf->SetLeftMargin(5);
      $pdf->SetRightMargin(5);

      $pdf->Image($logo, 3, 5, '18', '18', 'png');
      $pdf->SetFont('Arial', '', 18);
      $pdf->Text(24, 12, $this->core->getSettings('nama_instansi'));
      $pdf->SetFont('Arial', '', 8);
      $pdf->Text(24, 17, $this->core->getSettings('alamat_instansi').' - '.$this->core->getSettings('kabupaten'));
      $pdf->Text(24, 20, $this->core->getSettings('kontak').' - '.$this->core->getSettings('email'));

      $pdf->SetFont('Arial', '', 10);
      $pdf->Text(5, 40, 'No. Kartu');
      $pdf->Text(25, 40, ': '.$pasien['no_rkm_medis']);
      $pdf->Text(5, 46, 'Nama');
      $pdf->Text(25, 46, ': '.$pasien['nm_pasien']);
      $pdf->Text(5, 52, 'Alamat');
      $pdf->Text(25, 52, ': '.$pasien['alamat']);

      $pdf->Output('kartu_pasien_'.$pasien['no_rkm_medis'].'.pdf','I');

      exit();

    }

    public function getPrint($phrase = null)
    {
      $phrase = '';
      if(isset($_GET['s']))
        $phrase = $_GET['s'];
      $pasien = $this->db('pasien')->like('nm_pasien', '%'.$phrase.'%')->toArray();
      $logo = 'data:image/png;base64,' . base64_encode($this->core->getSettings('logo'));

      $pdf = new PDF_MC_Table();
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(true, 10);
      $pdf->SetTopMargin(10);
      $pdf->SetLeftMargin(10);
      $pdf->SetRightMargin(10);

      $pdf->Image($logo, 10, 8, '18', '18', 'png');
      $pdf->SetFont('Arial', '', 24);
      $pdf->Text(30, 16, $this->core->getSettings('nama_instansi'));
      $pdf->SetFont('Arial', '', 10);
      $pdf->Text(30, 21, $this->core->getSettings('alamat_instansi').' - '.$this->core->getSettings('kabupaten'));
      $pdf->Text(30, 25, $this->core->getSettings('kontak').' - '.$this->core->getSettings('email'));
      $pdf->Line(10, 30, 200, 30);
      $pdf->Line(10, 31, 200, 31);
      $pdf->Text(10, 40, 'DATA PASIEN');
      $pdf->Ln(34);
      $pdf->SetFont('Arial', '', 10);
      $pdf->SetWidths(array(20,60,35,75));
      $pdf->Row(array('No. RM','Nama Pasien','No KTP', 'Alamat'));
      foreach ($pasien as $hasil) {
        $pdf->Row(array($hasil['no_rkm_medis'],$hasil['nm_pasien'],$hasil['no_ktp'],$hasil['alamat']));
      }
      $pdf->Output('laporan_pasien_'.date('Y-m-d').'.pdf','I');

    }

    public function getPrint_rm($id)
    {
      $pasien = $this->db('pasien')->where('no_rkm_medis', $id)->oneArray();
      $logo = 'data:image/png;base64,' . base64_encode($this->core->getSettings('logo'));

      $pdf = new FPDF();
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(true, 10);
      $pdf->SetTopMargin(5);
      $pdf->SetLeftMargin(5);
      $pdf->SetRightMargin(5);

      $pdf->Image($logo, 5, 8, '18', '18', 'png');
      $pdf->SetFont('Arial', '', 24);
      $pdf->Text(30, 16, $this->core->getSettings('nama_instansi'));
      $pdf->SetFont('Arial', '', 12);
      $pdf->Text(30, 22, $this->core->getSettings('alamat_instansi').' - '.$this->core->getSettings('kabupaten'));
      $pdf->Text(30, 26, $this->core->getSettings('kontak').' - '.$this->core->getSettings('email'));

      $pdf->Output('rekam_medik_pasien_'.$pasien['no_rkm_medis'].'.pdf','I');

    }

    public function postSave($id = null)
    {
        $errors = 0;

        $cek_no_rkm_medis = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->count();

        // location to redirect
        if ($cek_no_rkm_medis == 0) {
            $location = url([ADMIN, 'pasien', 'add']);
        } else {
            $location = url([ADMIN, 'pasien', 'edit', $_POST['no_rkm_medis']]);
        }

        // check if required fields are empty
        if (checkEmptyFields(['no_ktp', 'nm_pasien', 'alamat'], $_POST)) {
            $this->notify('failure', 'Isian ada yang masih kosong');
            redirect($location, $_POST);
        }

        // check if pasien already exists
        if ($this->_pasienAlreadyExists($_POST['no_rkm_medis'])) {
            $errors++;
            $this->notify('failure', 'Pasiens sudah terdaftar dengan nomor KTP '.$_POST['no_ktp']);
        }

        // CREATE / EDIT
        if (!$errors) {
            unset($_POST['save']);

            if ($cek_no_rkm_medis == 0) {    // new
                $_POST['no_rkm_medis'] = $this->_setNoRM();
                $_POST['umur'] = $this->_setUmur($_POST['tgl_lahir']);
                $query = $this->db('pasien')->save($_POST);
                $this->core->db()->pdo()->exec("UPDATE set_no_rkm_medis SET no_rkm_medis='$_POST[no_rkm_medis]'");
            } else {        // edit
                $query = $this->db('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->save($_POST);
            }

            if ($query) {
                $this->notify('success', 'Simpan sukes');
            } else {
                $this->notify('failure', 'Simpan gagal');
            }

            redirect($location);
        }

        redirect($location, $_POST);
    }

    public function getDelete($no_rkm_medis)
    {
        if ($pasien = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray()) {
            if ($this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->delete()) {
                $this->notify('success', 'Hapus sukses');
            } else {
                $this->notify('failure', 'Hapus gagal');
            }
        }
        redirect(url([ADMIN, 'pasien', 'manage']));
    }

    public function getAjax()
    {
        header('Content-type: text/html');
        $show = isset($_GET['show']) ? $_GET['show'] : "";
        switch($show){
        	default:
          break;
        	case "propinsi":
          $propinsi = $this->db('propinsi')->toArray();
          foreach ($propinsi as $row) {
            echo '<tr class="pilihpropinsi" data-kdprop="'.$row['kd_prop'].'" data-namaprop="'.$row['nm_prop'].'">';
      			echo '<td>'.$row['kd_prop'].'</td>';
      			echo '<td>'.$row['nm_prop'].'</td>';
      			echo '</tr>';
          }
          break;
          case "kabupaten":
          $kode = $_GET['kd_prop'];
          $kode = ''.$kode.'%';
          $kabupaten = $this->db('kabupaten')->like('kd_kab', $kode)->toArray();
          foreach ($kabupaten as $row) {
            echo '<tr class="pilihkabupaten" data-kdkab="'.$row['kd_kab'].'" data-namakab="'.$row['nm_kab'].'">';
      			echo '<td>'.$row['kd_kab'].'</td>';
      			echo '<td>'.$row['nm_kab'].'</td>';
      			echo '</tr>';
          }
          break;
          case "kecamatan":
          $kode = $_GET['kd_kab'];
          $kode = ''.$kode.'%';
          $kecamatan = $this->db('kecamatan')->like('kd_kec', $kode)->toArray();
          foreach ($kecamatan as $row) {
            echo '<tr class="pilihkecamatan" data-kdkec="'.$row['kd_kec'].'" data-namakec="'.$row['nm_kec'].'">';
      			echo '<td>'.$row['kd_kec'].'</td>';
      			echo '<td>'.$row['nm_kec'].'</td>';
      			echo '</tr>';
          }
          break;
          case "kelurahan":
          $kode = $_GET['kd_kec'];
          $kode = ''.$kode.'%';
          $kelurahan = $this->db('kelurahan')->like('kd_kel', $kode)->toArray();
          foreach ($kelurahan as $row) {
            echo '<tr class="pilihkelurahan" data-kdkel="'.$row['kd_kel'].'" data-namakel="'.$row['nm_kel'].'">';
      			echo '<td>'.$row['kd_kel'].'</td>';
      			echo '<td>'.$row['nm_kel'].'</td>';
      			echo '</tr>';
          }
          break;
        }
        exit();
    }

    /**
    * check if pasien already exists
    * @return array
    */
    private function _pasienAlreadyExists($no_rkm_medis)
    {
        $cek_no_rkm_medis = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->count();
        if ($no_rkm_medis == 0) {    // new
            $count = $this->db('pasien')->where('no_ktp', $_POST['no_ktp'])->count();
        } else {        // edit
            $count = $this->db('pasien')->where('no_ktp', $_POST['no_ktp'])->where('no_rkm_medis', '!=', $no_rkm_medis)->count();
        }
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getNoka_BPJS()
    {
      header('Content-type: text/html');
      $date = date('Y-m-d');
      $url = BpjsApiUrl.'Peserta/nokartu/'.$_GET[noka].'/tglSEP/'.$date;
      $output = BpjsRequest::get($url);
      echo $output;
      exit();
    }

    public function getNik_BPJS()
    {
      header('Content-type: text/html');
      $date = date('Y-m-d');
      $url = BpjsApiUrl.'Peserta/nik/'.$_GET[nik].'/tglSEP/'.$date;
      $output = BpjsRequest::get($url);
      echo $output;
      exit();
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/pasien/js/admin/pasien.js');
        exit();
    }

    public function getCss()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/pasien/css/admin/pasien.css');
        exit();
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('assets/css/jquery-ui.css'));

        // JS
        $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');

        // MODULE SCRIPTS
        $this->core->addCSS(url([ADMIN, 'pasien', 'css']));
        $this->core->addJS(url([ADMIN, 'pasien', 'javascript']), 'footer');
    }

    private function _addEnum($table_name, $column_name) {
      $result = $this->db()->pdo()->prepare("SHOW COLUMNS FROM $table_name LIKE '$column_name'");
      $result->execute();
      $result = $result->fetch();
      $result = explode("','",preg_replace("/(enum|set)\('(.+?)'\)/","\\2", $result[1]));
      return $result;
    }

    private function _setNoRM()
    {
        // Get last no_rm
        $last_no_rm = $this->db('set_no_rkm_medis')->oneArray();
        $last_no_rm = substr($last_no_rm['no_rkm_medis'], 0, 6);
        $next_no_rm = sprintf('%06s', ($last_no_rm + 1));
        return $next_no_rm;
    }

    private function _setUmur($tanggal)
    {
        list($cY, $cm, $cd) = explode('-', date('Y-m-d'));
        list($Y, $m, $d) = explode('-', date('Y-m-d', strtotime($tanggal)));
        $umur = $cY - $Y;
        return $umur;
    }

}