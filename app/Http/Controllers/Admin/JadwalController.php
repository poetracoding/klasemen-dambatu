<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Services\TournamentService;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    public function index()
    {
        $liga = Liga::current();
        $draft = $liga->status === 'draft';
        $defaultMeja = max(1, (int) ($liga->jumlah_meja ?? 2));
        $defaultHari = array_map('intval', explode(',', $liga->hari_pertandingan ?? '1,2,3,4,5,6,7'));
        $defaultLibur = array_values(array_filter(explode(',', $liga->tanggal_libur ?? '')));
        $jumlahTim = Tim::where('liga_id', $liga->id)->count();

        $sudahBertanding = Pertandingan::where('liga_id', $liga->id)
            ->where('status', '<>', 'belum_main')
            ->count();

        $matches = Pertandingan::where('liga_id', $liga->id)
            ->with(['homeTeam', 'awayTeam', 'pemenang'])
            ->orderByRaw('tanggal IS NULL, tanggal, waktu, nomor_pertandingan')
            ->get();

        return view('admin.jadwal', compact(
            'liga', 'draft', 'defaultMeja', 'defaultHari', 'defaultLibur',
            'jumlahTim', 'sudahBertanding', 'matches'
        ));
    }

    public function generate(Request $request)
    {
        $liga = Liga::current();
        if ($liga->status !== 'draft') {
            return back()->with('danger', 'Jadwal hanya dapat dibuat ulang saat liga masih berstatus draft.');
        }

        $jumlahTim = Tim::where('liga_id', $liga->id)->count();
        if ($jumlahTim < 2) {
            return back()->with('danger', 'Minimal 2 tim untuk membuat jadwal.');
        }

        $request->validate([
            'mulai'       => 'required|date',
            'per_hari'    => 'required|integer|min:1|max:50',
            'jumlah_meja' => 'required|integer|min:1|max:10',
            'jam'         => 'required',
            'durasi'      => 'required|integer|min:1|max:600',
            'lokasi'      => 'nullable|string|max:60',
            'hari'        => 'required|array|min:1',
            'libur'       => 'nullable|array',
        ]);

        $o = [
            'mulai'       => $request->mulai,
            'per_hari'    => (int) $request->per_hari,
            'jumlah_meja' => (int) $request->jumlah_meja,
            'jam'         => $request->jam,
            'durasi'      => (int) $request->durasi,
            'lokasi'      => trim($request->lokasi ?? ''),
            'hari'        => $request->hari,
            'libur'       => $request->libur ?? [],
        ];

        $n = TournamentService::buatJadwal($liga, $o);

        return back()->with('success', "Jadwal dibuat: $n pertandingan dengan {$o['jumlah_meja']} meja Dam Batu. Pertandingan dijadwalkan pada hari yang ditentukan secara berurutan tanpa bentrok tim.");
    }

    public function batalJadwal()
    {
        $liga = Liga::current();
        $sudahBertanding = Pertandingan::where('liga_id', $liga->id)
            ->where('status', '<>', 'belum_main')
            ->count();

        if ($sudahBertanding > 0) {
            return back()->with('danger', "Jadwal TIDAK DAPAT dibatalkan karena sudah ada $sudahBertanding pertandingan yang sedang berjalan atau telah selesai.");
        }

        Pertandingan::where('liga_id', $liga->id)->delete();
        $liga->update([
            'status' => 'draft',
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
        ]);

        return back()->with('success', 'Seluruh jadwal pertandingan berhasil dibatalkan dan dihapus. Anda dapat menambah/mengurangi tim atau generate jadwal ulang.');
    }

    public function publikasi()
    {
        $liga = Liga::current();
        $count = Pertandingan::where('liga_id', $liga->id)->count();

        if ($liga->status !== 'draft') {
            return back()->with('info', 'Jadwal sudah dipublikasikan.');
        }

        if ($count === 0) {
            return back()->with('danger', 'Belum ada jadwal untuk dipublikasikan.');
        }

        $minDate = Pertandingan::where('liga_id', $liga->id)->min('tanggal');
        $maxDate = Pertandingan::where('liga_id', $liga->id)->max('tanggal');

        $liga->update([
            'status' => 'berjalan',
            'tanggal_mulai' => $minDate,
            'tanggal_selesai' => $maxDate,
        ]);

        return back()->with('success', 'Jadwal dipublikasikan. Liga sekarang berstatus berjalan.');
    }

    public function batalPublikasi()
    {
        $liga = Liga::current();
        $sudahBertanding = Pertandingan::where('liga_id', $liga->id)
            ->where('status', '<>', 'belum_main')
            ->count();

        if ($liga->status === 'draft' || $sudahBertanding > 0) {
            return back()->with('danger', 'Publikasi hanya dapat dibatalkan jika belum ada pertandingan yang dimainkan/diubah statusnya.');
        }

        $liga->update(['status' => 'draft']);

        return back()->with('success', 'Publikasi dibatalkan. Liga kembali ke draft.');
    }

    public function geserJadwal(Request $request)
    {
        $liga = Liga::current();
        $request->validate([
            'id' => 'required|integer',
            'menit' => 'required|integer',
            'cakupan' => 'required|in:hanya_satu,semua_setelahnya',
        ]);

        $count = TournamentService::geserJadwal($liga, (int)$request->id, (int)$request->menit, $request->cakupan);

        if ($count === 0) {
            return back()->with('danger', 'Tidak ada jadwal yang digeser.');
        }

        $aksiTeks = $request->menit < 0 ? 'dipercepat ' . abs($request->menit) . ' menit' : 'diperlambat ' . $request->menit . ' menit';
        return back()->with('success', "Jadwal $count pertandingan berhasil $aksiTeks.");
    }

    public function updateSlot(Request $request, $id)
    {
        $liga = Liga::current();
        $match = Pertandingan::where('liga_id', $liga->id)->findOrFail($id);

        $match->update([
            'tanggal' => $request->tanggal ?: null,
            'waktu' => $request->waktu ?: null,
            'lokasi' => trim($request->lokasi ?? ''),
        ]);

        return back()->with('success', 'Jadwal pertandingan #' . $match->nomor_pertandingan . ' diperbarui.');
    }
}

