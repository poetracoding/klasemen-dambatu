<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use Illuminate\Http\Request;

class TimController extends Controller
{
    public function index()
    {
        $liga = Liga::current();
        $tims = Tim::where('liga_id', $liga->id)
            ->withCount(['homeMatches', 'awayMatches'])
            ->orderBy('id')
            ->get();

        $draft = $liga->status === 'draft';
        $adaJadwal = Pertandingan::where('liga_id', $liga->id)->exists();

        return view('admin.tim', compact('liga', 'tims', 'draft', 'adaJadwal'));
    }

    public function store(Request $request)
    {
        $liga = Liga::current();
        if ($liga->status !== 'draft') {
            return back()->with('danger', 'Tim hanya dapat ditambah saat liga berstatus draft.');
        }

        $request->validate([
            'nama_tim' => 'required|string|max:80',
            'pemain1' => 'required|string|max:60',
            'pemain2' => 'required|string|max:60',
        ]);

        $exists = Tim::where('liga_id', $liga->id)
            ->where('nama_tim', trim($request->nama_tim))
            ->exists();

        if ($exists) {
            return back()->with('danger', 'Nama tim sudah terdaftar.');
        }

        Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => trim($request->nama_tim),
            'pemain1' => trim($request->pemain1),
            'pemain2' => trim($request->pemain2),
            'status' => 1,
        ]);

        return back()->with('success', 'Tim "' . $request->nama_tim . '" berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $liga = Liga::current();
        $tim = Tim::where('liga_id', $liga->id)->findOrFail($id);

        $request->validate([
            'nama_tim' => 'required|string|max:80',
            'pemain1' => 'required|string|max:60',
            'pemain2' => 'required|string|max:60',
        ]);

        $exists = Tim::where('liga_id', $liga->id)
            ->where('nama_tim', trim($request->nama_tim))
            ->where('id', '<>', $tim->id)
            ->exists();

        if ($exists) {
            return back()->with('danger', 'Nama tim sudah digunakan oleh tim lain.');
        }

        $tim->update([
            'nama_tim' => trim($request->nama_tim),
            'pemain1' => trim($request->pemain1),
            'pemain2' => trim($request->pemain2),
        ]);

        return back()->with('success', 'Data tim berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $liga = Liga::current();
        if ($liga->status !== 'draft') {
            return back()->with('danger', 'Tim hanya dapat dihapus saat liga berstatus draft.');
        }

        $tim = Tim::where('liga_id', $liga->id)->findOrFail($id);
        $nama = $tim->nama_tim;

        // Cek jika tim sudah tercatat dalam pertandingan
        $tercatat = Pertandingan::where('liga_id', $liga->id)
            ->where(function ($q) use ($tim) {
                $q->where('tim_home', $tim->id)->orWhere('tim_away', $tim->id);
            })
            ->exists();

        if ($tercatat) {
            return back()->with('danger', 'Hapus atau generate ulang jadwal sebelum menghapus tim ini.');
        }

        $tim->delete();

        return back()->with('success', 'Tim "' . $nama . '" berhasil dihapus.');
    }
}
