<?php

namespace Plugins\Kepegawaian;

use Systems\AdminModule;
use Systems\Lib\Fpdf\PDF_MC_Table;

class Admin extends AdminModule
{
    public function navigation()
    {
        return [
            'Data Pegawai' => 'manage',
            'Tambah Baru' => 'add',
            'Master Pegawai' => 'master',
        ];
    }

    public function getManage($page = 1)
    {
        $perpage = '10';

        $phrase = '';
        if(isset($_GET['s']))
          $phrase = $_GET['s'];

        // pagination
        $totalRecords = $this->db('pegawai')->like('nik', '%'.$phrase.'%')->like('nama', '%'.$phrase.'%')->toArray();
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'kepegawaian', 'manage', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination','5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        $rows = $this->db('pegawai')->like('nik', '%'.$phrase.'%')->like('nama', '%'.$phrase.'%')->offset($offset)->limit($perpage)->toArray();

        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['editURL'] = url([ADMIN, 'kepegawaian', 'edit', $row['id']]);
                $row['viewURL'] = url([ADMIN, 'kepegawaian', 'view', $row['id']]);
                $this->assign['list'][] = $row;
            }
        }

        $this->assign['getStatus'] = isset($_GET['status']);
        $this->assign['printURL'] = url([ADMIN, 'kepegawaian', 'print']);

        return $this->draw('manage.html', ['pegawai' => $this->assign]);

    }

    public function getAdd()
    {
        $this->_addHeaderFiles();
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'nik' => '',
              'nama' => '',
              'jk' => '',
              'jbtn' => '',
              'jnj_jabatan' => '',
              'kode_kelompok' => '',
              'kode_resiko' => '',
              'kode_emergency' => '',
              'departemen' => '',
              'bidang' => '',
              'stts_wp' => '',
              'stts_kerja' => '',
              'npwp' => '',
              'pendidikan' => '',
              'gapok' => '',
              'tmp_lahir' => '',
              'tgl_lahir' => '',
              'alamat' => '',
              'kota' => '',
              'mulai_kerja' => '',
              'ms_kerja' => '',
              'indexins' => '',
              'bpd' => '',
              'rekening' => '',
              'stts_aktif' => '',
              'wajibmasuk' => '',
              'pengurang' => '',
              'indek' => '',
              'mulai_kontrak' => '',
              'cuti_diambil' => '',
              'dankes' => '',
              'photo' => '',
              'no_ktp' => ''
            ];
        }

        $this->assign['title'] = 'Tambah Pegawai';
        $this->assign['jk'] = $this->core->getEnum('pegawai', 'jk');
        $this->assign['ms_kerja'] = $this->core->getEnum('pegawai', 'ms_kerja');
        $this->assign['stts_aktif'] = $this->core->getEnum('pegawai', 'stts_aktif');
        $this->assign['jnj_jabatan'] = $this->db('jnj_jabatan')->toArray();
        $this->assign['kelompok_jabatan'] = $this->db('kelompok_jabatan')->toArray();
        $this->assign['resiko_kerja'] = $this->db('resiko_kerja')->toArray();
        $this->assign['departemen'] = $this->db('departemen')->toArray();
        $this->assign['bidang'] = $this->db('bidang')->toArray();
        $this->assign['stts_wp'] = $this->db('stts_wp')->toArray();
        $this->assign['stts_kerja'] = $this->db('stts_kerja')->toArray();
        $this->assign['pendidikan'] = $this->db('pendidikan')->toArray();
        $this->assign['bank'] = $this->db('bank')->toArray();
        $this->assign['emergency_index'] = $this->db('emergency_index')->toArray();

        $this->assign['fotoURL'] = url(MODULES.'/kepegawaian/img/default.png');

        return $this->draw('form.html', ['pegawai' => $this->assign]);
    }

    public function getEdit($id)
    {
        $this->_addHeaderFiles();
        $row = $this->db('pegawai')->oneArray($id);
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Pegawai';

            $this->assign['jk'] = $this->core->getEnum('pegawai', 'jk');
            $this->assign['ms_kerja'] = $this->core->getEnum('pegawai', 'ms_kerja');
            $this->assign['stts_aktif'] = $this->core->getEnum('pegawai', 'stts_aktif');
            $this->assign['jnj_jabatan'] = $this->db('jnj_jabatan')->toArray();
            $this->assign['kelompok_jabatan'] = $this->db('kelompok_jabatan')->toArray();
            $this->assign['resiko_kerja'] = $this->db('resiko_kerja')->toArray();
            $this->assign['departemen'] = $this->db('departemen')->toArray();
            $this->assign['bidang'] = $this->db('bidang')->toArray();
            $this->assign['stts_wp'] = $this->db('stts_wp')->toArray();
            $this->assign['stts_kerja'] = $this->db('stts_kerja')->toArray();
            $this->assign['pendidikan'] = $this->db('pendidikan')->toArray();
            $this->assign['bank'] = $this->db('bank')->toArray();
            $this->assign['emergency_index'] = $this->db('emergency_index')->toArray();

            $this->assign['fotoURL'] = url(WEBAPPS_PATH.'/penggajian/pages/pegawai/photo/'.$row['photo']);
            return $this->draw('form.html', ['pegawai' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'kepegawaian', 'manage']));
        }
    }

    public function postSave($id = null)
    {
        $errors = 0;

        if (!$id) {
            $location = url([ADMIN, 'kepegawaian', 'add']);
        } else {
            $location = url([ADMIN, 'kepegawaian', 'edit', $id]);
        }

        if (checkEmptyFields(['nik', 'nama'], $_POST)) {
            $this->notify('failure', 'Isian kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (($photo = isset_or($_FILES['photo']['tmp_name'], false)) || !$id) {
                $img = new \Systems\Lib\Image;

                if (empty($photo) && !$id) {
                    $photo = MODULES.'/kepegawaian/img/default.png';
                }
                if ($img->load($photo)) {
                    if ($img->getInfos('width') < $img->getInfos('height')) {
                        $img->crop(0, 0, $img->getInfos('width'), $img->getInfos('width'));
                    } else {
                        $img->crop(0, 0, $img->getInfos('height'), $img->getInfos('height'));
                    }

                    if ($img->getInfos('width') > 512) {
                        $img->resize(512, 512);
                    }

                    if ($id) {
                        $pegawai = $this->db('pegawai')->oneArray($id);
                    }

                    $_POST['photo'] = uniqid('photo').".".$img->getInfos('type');
                }
            }

            if (!$id) {    // new
                $query = $this->db('pegawai')->save($_POST);
            } else {        // edit
                $query = $this->db('pegawai')->where('id', $id)->save($_POST);
            }

            if ($query) {
                if (isset($img) && $img->getInfos('width')) {
                    if (isset($pegawai)) {
                        unlink(WEBAPPS_PATH."/penggajian/pages/pegawai/photo/".$pegawai['photo']);
                    }

                    $img->save(WEBAPPS_PATH."/penggajian/pages/pegawai/photo/".$_POST['photo']);
                }

                $this->notify('success', 'Simpan sukes');
            } else {
                $this->notify('failure', 'Simpan gagal');
            }

            redirect($location);
        }

        redirect($location, $_POST);
    }

    public function getPrint()
    {
      $pasien = $this->db('pegawai')->toArray();
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
      $pdf->Text(10, 40, 'DATA PEGAWAI');
      $pdf->Ln(34);
      $pdf->SetFont('Arial', '', 10);
      $pdf->SetWidths(array(50,70,25,25,20));
      $pdf->Row(array('Kode Pegawai','Nama Pegawai','Tempat Lahir', 'Tanggal Lahir', 'Status'));
      foreach ($pasien as $hasil) {
        $pdf->Row(array($hasil['nik'],$hasil['nama'],$hasil['tmp_lahir'],$hasil['tgl_lahir'],$hasil['stts_aktif']));
      }
      $pdf->Output('laporan_pegawai_'.date('Y-m-d').'.pdf','I');

    }

    public function getMaster()
    {
        $rows = $this->db('jnj_jabatan')->toArray();
        $this->assign['jnj_jabatan'] = [];
        foreach ($rows as $row) {
            $row['editURL'] = url([ADMIN, 'kepegawaian', 'jnjjabatanedit', $row['kode']]);
            $this->assign['jnj_jabatan'][] = $row;
        }

        $rows = $this->db('kelompok_jabatan')->toArray();
        $this->assign['kelompok_jabatan'] = [];
        foreach ($rows as $row) {
            $row['editURL'] = url([ADMIN, 'kepegawaian', 'kelompokjabatanedit', $row['kode_kelompok']]);
            $this->assign['kelompok_jabatan'][] = $row;
        }

        $rows = $this->db('resiko_kerja')->toArray();
        $this->assign['resiko_kerja'] = [];
        foreach ($rows as $row) {
            $row['editURL'] = url([ADMIN, 'kepegawaian', 'resikokerjaedit', $row['kode_resiko']]);
            $this->assign['resiko_kerja'][] = $row;
        }

        $rows = $this->db('departemen')->toArray();
        $this->assign['departemen'] = [];
        foreach ($rows as $row) {
            $row['editURL'] = url([ADMIN, 'kepegawaian', 'departemenedit', $row['dep_id']]);
            $this->assign['departemen'][] = $row;
        }

        $rows = $this->db('bidang')->toArray();
        $this->assign['bidang'] = [];
        foreach ($rows as $row) {
            $row['editURL'] = url([ADMIN, 'kepegawaian', 'bidangedit', $row['nama']]);
            $this->assign['bidang'][] = $row;
        }

        $rows = $this->db('stts_kerja')->toArray();
        $this->assign['stts_kerja'] = [];
        foreach ($rows as $row) {
          $row['editURL'] = url([ADMIN, 'kepegawaian', 'sttskerjaedit', $row['stts']]);
            $this->assign['stts_kerja'][] = $row;
        }

        $rows = $this->db('stts_wp')->toArray();
        $this->assign['stts_wp'] = [];
        foreach ($rows as $row) {
          $row['editURL'] = url([ADMIN, 'kepegawaian', 'sttswpedit', $row['stts']]);
            $this->assign['stts_wp'][] = $row;
        }

        $rows = $this->db('pendidikan')->toArray();
        $this->assign['pendidikan'] = [];
        foreach ($rows as $row) {
            $row['editURL'] = url([ADMIN, 'kepegawaian', 'pendidikanedit', $row['tingkat']]);
            $this->assign['pendidikan'][] = $row;
        }

        $rows = $this->db('bank')->toArray();
        $this->assign['bank'] = [];
        foreach ($rows as $row) {
            $row['editURL'] = url([ADMIN, 'kepegawaian', 'bankedit', $row['namabank']]);
            $this->assign['bank'][] = $row;
        }

        $rows = $this->db('emergency_index')->toArray();
        $this->assign['emergency_index'] = [];
        foreach ($rows as $row) {
            $row['editURL'] = url([ADMIN, 'kepegawaian', 'emergencyindexedit', $row['kode_emergency']]);
            $this->assign['emergency_index'][] = $row;
        }

        return $this->draw('master.html', ['master' => $this->assign]);
    }

    public function getJnjJabatanAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'kode' => '',
              'nama' => '',
              'tnj' => '',
              'indek' => ''
            ];
        }
        $this->assign['title'] = 'Tambah Master Jenjang Jabatan';

        return $this->draw('jnj_jabatan.form.html', ['master' => $this->assign]);
    }

    public function getJnjJabatanEdit($id)
    {
        $row = $this->db('jnj_jabatan')->where('kode', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Master Jenjang Jabatan';

            return $this->draw('jnj_jabatan.form.html', ['master' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'kepegawaian', 'master']));
        }
    }

    public function postJnjJabatanSave($id = null)
    {
        $errors = 0;

        $cek_penjab = $this->db('jnj_jabatan')->where('kode', $_POST['kode'])->count();

        if (!$id) {
            $location = url([ADMIN, 'kepegawaian', 'master']);
        } else {
            $location = url([ADMIN, 'kepegawaian', 'jnjjabatanedit', $id]);
        }

        if (checkEmptyFields(['kode', 'nama'], $_POST)) {
            $this->notify('failure', 'Isian ada yang masih kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$cek_penjab) {    // new
                $query = $this->db('jnj_jabatan')->save($_POST);
            } else {        // edit
                $query = $this->db('jnj_jabatan')->where('kode', $_POST['kode'])->save($_POST);
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

    public function getKelompokJabatanAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'kode_kelompok' => '',
              'nama_kelompok' => '',
              'indek' => ''
            ];
        }
        $this->assign['title'] = 'Tambah Master Kelompok Jabatan';

        return $this->draw('kelompok_jabatan.form.html', ['master' => $this->assign]);
    }

    public function getKelompokJabatanEdit($id)
    {
        $row = $this->db('kelompok_jabatan')->where('kode_kelompok', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Master Kelompok Jabatan';

            return $this->draw('kelompok_jabatan.form.html', ['master' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'kepegawaian', 'master']));
        }
    }

    public function postKelompokJabatanSave($id = null)
    {
        $errors = 0;

        $cek_penjab = $this->db('kelompok_jabatan')->where('kode_kelompok', $_POST['kode_kelompok'])->count();

        if (!$id) {
            $location = url([ADMIN, 'kepegawaian', 'master']);
        } else {
            $location = url([ADMIN, 'kepegawaian', 'kelompokjabatanedit', $id]);
        }

        if (checkEmptyFields(['kode_kelompok', 'nama_kelompok'], $_POST)) {
            $this->notify('failure', 'Isian ada yang masih kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$cek_penjab) {    // new
                $query = $this->db('kelompok_jabatan')->save($_POST);
            } else {        // edit
                $query = $this->db('kelompok_jabatan')->where('kode_kelompok', $_POST['kode_kelompok'])->save($_POST);
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

    public function getResikoKerjaAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'kode_resiko' => '',
              'nama_resiko' => '',
              'indek' => ''
            ];
        }
        $this->assign['title'] = 'Tambah Master Resiko Kerja';

        return $this->draw('resiko_kerja.form.html', ['master' => $this->assign]);
    }

    public function getResikoKerjaEdit($id)
    {
        $row = $this->db('resiko_kerja')->where('kode_resiko', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Master Resiko Kerja';

            return $this->draw('resiko_kerja.form.html', ['master' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'kepegawaian', 'master']));
        }
    }

    public function postResikoKerjaSave($id = null)
    {
        $errors = 0;

        $cek_penjab = $this->db('resiko_kerja')->where('kode_resiko', $_POST['kode_resiko'])->count();

        if (!$id) {
            $location = url([ADMIN, 'kepegawaian', 'master']);
        } else {
            $location = url([ADMIN, 'kepegawaian', 'resikokerjaedit', $id]);
        }

        if (checkEmptyFields(['kode_resiko', 'nama_resiko'], $_POST)) {
            $this->notify('failure', 'Isian ada yang masih kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$cek_penjab) {    // new
                $query = $this->db('resiko_kerja')->save($_POST);
            } else {        // edit
                $query = $this->db('resiko_kerja')->where('kode_resiko', $_POST['kode_resiko'])->save($_POST);
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

    public function getDepartemenAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'dep_id' => '',
              'nama' => ''
            ];
        }
        $this->assign['title'] = 'Tambah Master Departemen';

        return $this->draw('departemen.form.html', ['master' => $this->assign]);
    }

    public function getDepartemenEdit($id)
    {
        $row = $this->db('departemen')->where('dep_id', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Master Departemen';

            return $this->draw('departemen.form.html', ['master' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'kepegawaian', 'master']));
        }
    }

    public function postDepartemenSave($id = null)
    {
        $errors = 0;

        $cek_penjab = $this->db('departemen')->where('dep_id', $_POST['dep_id'])->count();

        if (!$id) {
            $location = url([ADMIN, 'kepegawaian', 'master']);
        } else {
            $location = url([ADMIN, 'kepegawaian', 'departemenedit', $id]);
        }

        if (checkEmptyFields(['dep_id', 'nama'], $_POST)) {
            $this->notify('failure', 'Isian ada yang masih kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$cek_penjab) {    // new
                $query = $this->db('departemen')->save($_POST);
            } else {        // edit
                $query = $this->db('departemen')->where('dep_id', $_POST['dep_id'])->save($_POST);
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

    public function getBidangAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'nama' => ''
            ];
        }
        $this->assign['title'] = 'Tambah Master Bidang';

        return $this->draw('bidang.form.html', ['master' => $this->assign]);
    }

    public function getBidangEdit($id)
    {
        $row = $this->db('bidang')->where('nama', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Master Departemen';

            return $this->draw('bidang.form.html', ['master' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'kepegawaian', 'master']));
        }
    }

    public function postBidangSave($id = null)
    {
        $errors = 0;

        $cek_penjab = $this->db('bidang')->where('nama', $_POST['nama'])->count();

        if (!$id) {
            $location = url([ADMIN, 'kepegawaian', 'master']);
        } else {
            $location = url([ADMIN, 'kepegawaian', 'bidangedit', $id]);
        }

        if (checkEmptyFields(['nama'], $_POST)) {
            $this->notify('failure', 'Isian ada yang masih kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$cek_penjab) {    // new
                $query = $this->db('bidang')->save($_POST);
            } else {        // edit
                $query = $this->db('bidang')->where('nama', $_POST['nama'])->save($_POST);
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

    public function getSttsWPAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'stts' => '',
              'ktg' => ''
            ];
        }
        $this->assign['title'] = 'Tambah Master Status WP';

        return $this->draw('stts_wp.form.html', ['master' => $this->assign]);
    }

    public function getSttsWPEdit($id)
    {
        $row = $this->db('stts_wp')->where('stts', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Master Status WP';

            return $this->draw('stts_wp.form.html', ['master' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'kepegawaian', 'master']));
        }
    }

    public function postSttsWPSave($id = null)
    {
        $errors = 0;

        $cek_penjab = $this->db('stts_wp')->where('stts', $_POST['stts'])->count();

        if (!$id) {
            $location = url([ADMIN, 'kepegawaian', 'master']);
        } else {
            $location = url([ADMIN, 'kepegawaian', 'sttswpedit', $id]);
        }

        if (checkEmptyFields(['stts', 'ktg'], $_POST)) {
          $this->notify('failure', 'Isian ada yang masih kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$cek_penjab) {    // new
                $query = $this->db('stts_wp')->save($_POST);
            } else {        // edit
                $query = $this->db('stts_wp')->where('stts', $_POST['stts'])->save($_POST);
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

    public function getSttsKerjaAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'stts' => '',
              'ktg' => '',
              'indek' => ''
            ];
        }
        $this->assign['title'] = 'Tambah Master Status Kerja';

        return $this->draw('stts_kerja.form.html', ['master' => $this->assign]);
    }

    public function getSttsKerjaEdit($id)
    {
        $row = $this->db('stts_kerja')->where('stts', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Master Status Kerja';

            return $this->draw('stts_kerja.form.html', ['master' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'kepegawaian', 'master']));
        }
    }

    public function postSttsKerjaSave($id = null)
    {
        $errors = 0;

        $cek_penjab = $this->db('stts_kerja')->where('stts', $_POST['stts'])->count();

        if (!$id) {
            $location = url([ADMIN, 'kepegawaian', 'master']);
        } else {
            $location = url([ADMIN, 'kepegawaian', 'sttskerjaedit', $id]);
        }

        if (checkEmptyFields(['stts', 'ktg'], $_POST)) {
            $this->notify('failure', 'Isian ada yang masih kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$cek_penjab) {    // new
                $query = $this->db('stts_kerja')->save($_POST);
            } else {        // edit
                $query = $this->db('stts_kerja')->where('stts', $_POST['stts'])->save($_POST);
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

    public function getPendidikanAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'tingkat' => '',
              'indek' => '',
              'gapok1' => '',
              'kenaikan' => '',
              'maksimal' => ''
            ];
        }
        $this->assign['title'] = 'Tambah Master Pendidikan';

        return $this->draw('pendidikan.form.html', ['master' => $this->assign]);
    }

    public function getPendidikanEdit($id)
    {
        $row = $this->db('pendidikan')->where('tingkat', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Master Pendidikan';

            return $this->draw('pendidikan.form.html', ['master' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'kepegawaian', 'master']));
        }
    }

    public function postPendidikanSave($id = null)
    {
        $errors = 0;

        $cek_penjab = $this->db('pendidikan')->where('tingkat', $_POST['tingkat'])->count();

        if (!$id) {
            $location = url([ADMIN, 'kepegawaian', 'master']);
        } else {
            $location = url([ADMIN, 'kepegawaian', 'pendidikanedit', $id]);
        }

        if (checkEmptyFields(['tingkat'], $_POST)) {
            $this->notify('failure', 'Isian ada yang masih kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$cek_penjab) {    // new
                $query = $this->db('pendidikan')->save($_POST);
            } else {        // edit
                $query = $this->db('pendidikan')->where('tingkat', $_POST['tingkat'])->save($_POST);
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

    public function getBankAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'namabank' => ''
            ];
        }
        $this->assign['title'] = 'Tambah Master Bank';

        return $this->draw('bank.form.html', ['master' => $this->assign]);
    }

    public function getBankEdit($id)
    {
        $row = $this->db('bank')->where('namabank', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Master Bank';

            return $this->draw('bank.form.html', ['master' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'kepegawaian', 'master']));
        }
    }

    public function postBankSave($id = null)
    {
        $errors = 0;

        $cek_penjab = $this->db('bank')->where('namabank', $_POST['namabank'])->count();

        if (!$id) {
            $location = url([ADMIN, 'kepegawaian', 'master']);
        } else {
            $location = url([ADMIN, 'kepegawaian', 'bankedit', $id]);
        }

        if (checkEmptyFields(['namabank'], $_POST)) {
          $this->notify('failure', 'Isian ada yang masih kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$cek_penjab) {    // new
                $query = $this->db('bank')->save($_POST);
            } else {        // edit
                $query = $this->db('bank')->where('namabank', $_POST['namabank'])->save($_POST);
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

    public function getEmergencyIndexAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'kode_emergency' => '',
              'nama_emergency' => '',
              'indek' => ''
            ];
        }
        $this->assign['title'] = 'Tambah Master Emergency Index';

        return $this->draw('emergency_index.form.html', ['master' => $this->assign]);
    }

    public function getEmergencyIndexEdit($id)
    {
        $row = $this->db('emergency_index')->where('kode_emergency', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Master Emergency Index';

            return $this->draw('emergency_index.form.html', ['master' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'kepegawaian', 'master']));
        }
    }

    public function postEmergencyIndexSave($id = null)
    {
        $errors = 0;

        $cek_penjab = $this->db('emergency_index')->where('kode_emergency', $_POST['kode_emergency'])->count();

        if (!$id) {
            $location = url([ADMIN, 'kepegawaian', 'master']);
        } else {
            $location = url([ADMIN, 'kepegawaian', 'emergencyindexedit', $id]);
        }

        if (checkEmptyFields(['kode_emergency', 'nama_emergency'], $_POST)) {
            $this->notify('failure', 'Isian ada yang masih kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$cek_penjab) {    // new
                $query = $this->db('emergency_index')->save($_POST);
            } else {        // edit
                $query = $this->db('emergency_index')->where('kode_emergency', $_POST['kode_emergency'])->save($_POST);
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

    public function getCSS()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/kepegawaian/css/admin/kepegawaian.css');
        exit();
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/kepegawaian/js/admin/kepegawaian.js');
        exit();
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('assets/css/jquery-ui.css'));

        // JS
        $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');

        // MODULE SCRIPTS
        $this->core->addCSS(url([ADMIN, 'kepegawaian', 'css']));
        $this->core->addJS(url([ADMIN, 'kepegawaian', 'javascript']), 'footer');
    }

}
