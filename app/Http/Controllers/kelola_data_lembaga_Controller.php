<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class kelola_data_lembaga_Controller extends Controller
{
    public function kelola_data_pt()
    {
        // Mengambil data kota/kabupaten
        $kotaKabupaten = DB::table('ref_wil_kota_kab')
            ->select('kode_kotakab_dagri', 'nama_kota_kab')
            ->get();
    
        // Mengambil data provinsi
        $provinsi = DB::table('ref_wil_provinsi')
            ->select('kode_awal_provinsi_dagri', 'nama_provinsi')
            ->get();
    
        // Mengambil data kecamatan
        $kecamatan = DB::table('ref_wilayah')
            ->select('id_wil', 'nm_wil')
            ->where('id_level_wil', '3') // Filter berdasarkan id_level_wil
            ->where(function ($query) {
                $query->where('id_wil', 'LIKE', '02%') // Filter berdasarkan awalan id_wil
                    ->orWhere('id_wil', 'LIKE', '03%');
            })
            ->get();
    
        return view('admin.kelola_data_pt', compact('kotaKabupaten', 'provinsi', 'kecamatan'));
    }
    
    public function getkelola_data_pt()
    {
        $query = DB::table('pt')
            ->select(
                'pt.kode_pt',
                'pt.nama_pt',
                'pt.peringkat_aipt',
                'pt.status_pt',
                'pt.kota_kabupaten',
                'ref_wil_kota_kab.nama_kota_kab',
                'pt.status_aipt',
                'pt.nilai_aipt',
                'pt.no_sk_aipt',
                'pt.tgl_sk_aipt',
                'pt.tgl_kadaluarsa_aipt',
            )
            ->selectRaw('COUNT(prodi.kode_prodi) as total_prodi')
            ->leftJoin('prodi', 'pt.kode_pt', '=', 'prodi.kode_pt')
            ->join('ref_wil_kota_kab', 'pt.kota_kabupaten', '=', 'ref_wil_kota_kab.kode_kotakab_dagri')
            ->join('ref_wil_provinsi', 'pt.provinsi', '=', 'ref_wil_provinsi.kode_awal_provinsi_dagri')
            ->where('pt.status_pt', 'A')
            ->groupBy(
                'pt.kode_pt',
                'pt.nama_pt',
                'pt.peringkat_aipt',
                'pt.status_pt',
                'pt.kota_kabupaten',
                'ref_wil_kota_kab.nama_kota_kab',
                'pt.status_aipt',
                'pt.nilai_aipt',
                'pt.no_sk_aipt',
                'pt.tgl_sk_aipt',
                'pt.tgl_kadaluarsa_aipt',
            )
            ->get();
    
        $data = DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('aksi', function ($row) {
                return '<button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#editModal' . $row->kode_pt . '">Edit</button>';
            })
            ->addColumn('modal', function ($pt) {
                return view('admin.kelola_data_pt_modal', compact('pt'))->render();
            })
            ->addColumn('id_sp', function ($row) {
                return '<a href="#"><i class="fa-solid fa-check" style="color: #63E6BE;"></i></a>';
            })
            ->editColumn('peringkat_aipt', function ($row) {
                $peringkat = $row->peringkat_aipt == '-' ? 'Tidak Terakreditasi' : $row->peringkat_aipt;
                if ($peringkat != 'Tidak Terakreditasi') {
                    return $peringkat . ' -<a href="#"> history</a>';
                } else {
                    return $peringkat;
                }
            })
            ->editColumn('nama_pt', function ($row) {
                return '<a href="' . route('daftar_prodi', ['kode_pt' => $row->kode_pt]) . '">' . $row->nama_pt . '</a>';
            })
            ->rawColumns(['aksi', 'modal', 'id_sp', 'peringkat_aipt', 'nama_pt'])
            ->make(true);
    
        return $data;
    }

    public function update_data_pt(Request $request, $kode_pt)
    {
        // Perbarui data sesuai dengan input dari form
        DB::table('pt')
            ->where('kode_pt', $kode_pt)
            ->update([
                'status_aipt' => $request->input('status_aipt'),
                'peringkat_aipt' => $request->input('peringkat_aipt'),
                'nilai_aipt' => $request->input('nilai_aipt'),
                'no_sk_aipt' => $request->input('no_sk_aipt'),
                'tgl_sk_aipt' => $request->input('tgl_sk_aipt'),
                'tgl_kadaluarsa_aipt' => $request->input('tgl_kadaluarsa_aipt')
            ]);

        return redirect()->route('kelola_data_pt')->with('success', 'Data PT berhasil diperbarui');
    }

    public function daftar_prodi(Request $request)
    {
        $kode_pt = $request->query('kode_pt');

        // Simpan kode PT terakhir yang diakses ke dalam session
        session(['kode_pt_terakhir' => $kode_pt]);

        // Ambil data nama_pt berdasarkan kode_pt
        $nama_pt = DB::table('pt')
            ->where('kode_pt', $kode_pt)
            ->value('nama_pt');

        // Ambil data prodi berdasarkan kode_pt
        $prodi = DB::table('prodi')
            ->select(
                'prodi.kode_prodi',
                'prodi.nama_prodi',
                'prodi.peringkat_akreditasi_banpt',
                'ref_jenjang_pendidikan.nm_jenj_didik',
                'prodi.status_prodi',
                'prodi.status_akreditasi',
                'prodi.peringkat_akreditasi_banpt',
                'prodi.nilai_akreditasi_banpt',
                'prodi.no_sk_akreditasi_banpt',
                'prodi.tgl_sk_akreditasi_banpt',
                'prodi.tgl_kadaluarsa_sk_akreditasi_banpt'
            )
            ->join('ref_jenjang_pendidikan', 'prodi.id_jenj_didik', '=', 'ref_jenjang_pendidikan.id_jenj_didik')
            ->where('prodi.kode_pt', $kode_pt)
            ->groupBy(
                'prodi.kode_prodi',
                'prodi.nama_prodi',
                'prodi.peringkat_akreditasi_banpt',
                'ref_jenjang_pendidikan.nm_jenj_didik',
                'prodi.status_prodi',
                'prodi.status_akreditasi',
                'prodi.peringkat_akreditasi_banpt',
                'prodi.nilai_akreditasi_banpt',
                'prodi.no_sk_akreditasi_banpt',
                'prodi.tgl_sk_akreditasi_banpt',
                'prodi.tgl_kadaluarsa_sk_akreditasi_banpt'
            )
            ->get();

        return view('admin.daftar_prodi', compact('prodi', 'nama_pt'));
    }

    public function update_data_prodi(Request $request, $kode_prodi)
    {
        // Simpan kode PT terakhir yang diakses dari session
        $kode_pt_terakhir = session('kode_pt_terakhir');

        // Perbarui data sesuai dengan input dari form
        DB::table('prodi')
            ->where('kode_prodi', $kode_prodi)
            ->update([
                'status_akreditasi' => $request->input('status_akreditasi'),
                'peringkat_akreditasi_banpt' => $request->input('peringkat_akreditasi_banpt'),
                'nilai_akreditasi_banpt' => $request->input('nilai_akreditasi_banpt'),
                'no_sk_akreditasi_banpt' => $request->input('no_sk_akreditasi_banpt'),
                'tgl_sk_akreditasi_banpt' => $request->input('tgl_sk_akreditasi_banpt'),
                'tgl_kadaluarsa_sk_akreditasi_banpt' => $request->input('tgl_kadaluarsa_sk_akreditasi_banpt')
            ]);

        // Redirect kembali ke daftar_prodi dengan kode PT terakhir
        return redirect()->route('daftar_prodi', ['kode_pt' => $kode_pt_terakhir])->with('success', 'Data Prodi berhasil diperbarui');
    }

    public function kelola_data_prodi()
    {
        return view('admin.kelola_data_prodi');
    }

    public function getkelola_data_prodi(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('prodi')
                ->join('ref_jenjang_pendidikan', 'prodi.id_jenj_didik', '=', 'ref_jenjang_pendidikan.id_jenj_didik')
                ->join('pt', 'prodi.kode_pt', '=', 'pt.kode_pt')
                ->select(
                    'prodi.kode_prodi',
                    'prodi.nama_prodi',
                    'ref_jenjang_pendidikan.nm_jenj_didik',
                    'pt.nama_pt'
                )
                ->where('prodi.status_prodi', 'A');

            return DataTables::of($data)
                ->addIndexColumn()
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($query) use ($search) {
                            $query->where('prodi.kode_prodi', 'like', "%{$search}%")
                                ->orWhere('prodi.nama_prodi', 'like', "%{$search}%")
                                ->orWhere('ref_jenjang_pendidikan.nm_jenj_didik', 'like', "%{$search}%")
                                ->orWhere('pt.nama_pt', 'like', "%{$search}%");
                        });
                    }
                })
                ->make(true);
        }
    }
}
