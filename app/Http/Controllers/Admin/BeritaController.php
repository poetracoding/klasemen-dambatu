<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\Liga;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BeritaController extends Controller
{
    public function index()
    {
        $liga = Liga::current();
        $beritas = Berita::with('author')->orderByDesc('id')->get();

        return view('admin.berita', compact('liga', 'beritas'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateBeritaRequest($request);

        $liga = Liga::current();
        $gambarPath = null;

        if ($request->hasFile('gambar')) {
            $gambarPath = $this->storeUploadedImage($request->file('gambar'));
        }

        $judul = trim($validated['judul']);
        $inputStatus = $validated['status'];

        if ($inputStatus === 'scheduled') {
            $dbStatus = 'published';
            $publishedAt = Carbon::parse($validated['published_at']);
            $message = "Berita berhasil dijadwalkan terbit pada {$publishedAt->format('d/m/Y H:i')} WIB.";
        } elseif ($inputStatus === 'published') {
            $dbStatus = 'published';
            $publishedAt = now();
            $message = 'Berita berhasil dipublikasikan.';
        } else {
            $dbStatus = 'draft';
            $publishedAt = !empty($validated['published_at']) ? Carbon::parse($validated['published_at']) : null;
            $message = 'Berita berhasil disimpan sebagai draft.';
        }

        $berita = Berita::create([
            'liga_id' => $liga->id,
            'user_id' => Auth::id(),
            'judul' => $judul,
            'slug' => Berita::generateUniqueSlug($judul),
            'kategori' => $validated['kategori'],
            'ringkasan' => isset($validated['ringkasan']) && trim($validated['ringkasan']) !== '' ? trim($validated['ringkasan']) : null,
            'isi' => trim($validated['isi']),
            'gambar' => $gambarPath,
            'status' => $dbStatus,
            'published_at' => $publishedAt,
        ]);

        $this->dispatchBeritaNotification($berita);

        return back()->with('success', $message);
    }

    public function update(Request $request, $id)
    {
        $berita = Berita::findOrFail($id);

        $validated = $this->validateBeritaRequest($request);

        $gambarPath = $berita->gambar;
        if ($request->hasFile('gambar')) {
            $this->deletePhysicalImage($berita->gambar);
            $gambarPath = $this->storeUploadedImage($request->file('gambar'));
        }

        $judul = trim($validated['judul']);
        $slug = $judul !== $berita->judul
            ? Berita::generateUniqueSlug($judul, $berita->id)
            : $berita->slug;

        $inputStatus = $validated['status'];
        if ($inputStatus === 'scheduled') {
            $dbStatus = 'published';
            $publishedAt = Carbon::parse($validated['published_at']);
        } elseif ($inputStatus === 'published') {
            $dbStatus = 'published';
            $publishedAt = ($berita->published_at === null || $berita->published_at->isFuture())
                ? now()
                : $berita->published_at;
        } else {
            $dbStatus = 'draft';
            $publishedAt = $request->has('published_at')
                ? (!empty($validated['published_at']) ? Carbon::parse($validated['published_at']) : null)
                : $berita->published_at;
        }

        $berita->update([
            'judul' => $judul,
            'slug' => $slug,
            'kategori' => $validated['kategori'],
            'ringkasan' => isset($validated['ringkasan']) && trim($validated['ringkasan']) !== '' ? trim($validated['ringkasan']) : null,
            'isi' => trim($validated['isi']),
            'gambar' => $gambarPath,
            'status' => $dbStatus,
            'published_at' => $publishedAt,
        ]);

        $this->dispatchBeritaNotification($berita);

        return back()->with('success', 'Berita berhasil diperbarui.');
    }

    public function toggleStatus($id)
    {
        $berita = Berita::findOrFail($id);

        if ($berita->status === 'published') {
            $berita->status = 'draft';
            $berita->save();

            return back()->with('success', 'Berita berhasil disimpan sebagai draft.');
        }

        $berita->status = 'published';
        if ($berita->published_at === null) {
            $berita->published_at = now();
        }
        $berita->save();

        $this->dispatchBeritaNotification($berita);

        $message = $berita->isScheduled()
            ? "Berita dijadwalkan terbit pada {$berita->published_at->format('d/m/Y H:i')} WIB."
            : 'Berita berhasil dipublikasikan.';

        return back()->with('success', $message);
    }

    public function publishNow($id)
    {
        $berita = Berita::findOrFail($id);
        $berita->status = 'published';
        $berita->published_at = now();
        $berita->save();

        $this->dispatchBeritaNotification($berita);

        return back()->with('success', 'Berita berhasil dipublikasikan sekarang.');
    }

    private function dispatchBeritaNotification(Berita $berita): void
    {
        if (!$berita->isLive() || $berita->notified_at !== null) {
            return;
        }

        try {
            app(FcmService::class)->notifyBeritaPublished($berita);
        } catch (\Throwable $e) {
            // Pastikan gagal kirim notifikasi tidak menggagalkan penyimpanan berita
        }
    }

    public function destroy($id)
    {
        $berita = Berita::findOrFail($id);
        $this->deletePhysicalImage($berita->gambar);
        $berita->delete();

        return back()->with('success', 'Berita berhasil dihapus.');
    }

    private function validateBeritaRequest(Request $request): array
    {
        return $request->validate([
            'judul' => 'required|string|max:180',
            'kategori' => ['required', 'string', Rule::in(Berita::KATEGORI_LIST)],
            'ringkasan' => 'nullable|string|max:300',
            'isi' => 'required|string|max:20000',
            'status' => ['required', 'string', Rule::in(['published', 'scheduled', 'draft'])],
            'published_at' => [
                Rule::requiredIf(fn () => $request->input('status') === 'scheduled'),
                'nullable',
                'date',
                $request->input('status') === 'scheduled' ? 'after:now' : 'nullable',
            ],
            'gambar' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'published_at.required' => 'Jadwal terbit wajib diisi jika memilih status Jadwalkan Terbit.',
            'published_at.date' => 'Format tanggal dan waktu jadwal terbit tidak valid.',
            'published_at.after' => 'Jadwal terbit harus lebih besar dari waktu sekarang.',
        ]);
    }

    private function storeUploadedImage($file): string
    {
        $dir = public_path('uploads/berita');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        if (is_dir($dir) && is_writable($dir)) {
            $ext = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'png'));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'png';
            }

            $filename = 'berita_' . time() . '_' . Str::lower(Str::random(8)) . '.' . $ext;
            $file->move($dir, $filename);

            return 'uploads/berita/' . $filename;
        }

        // Fallback ke Laravel public storage jika direktori public/uploads tidak writable
        $stored = $file->store('berita', 'public');
        return 'storage/' . $stored;
    }

    private function deletePhysicalImage(?string $path): void
    {
        if (!$path) {
            return;
        }

        if (str_starts_with($path, 'uploads/berita/') && file_exists(public_path($path))) {
            @unlink(public_path($path));
        } elseif (str_starts_with($path, 'storage/berita/')) {
            $relativePath = str_replace('storage/', '', $path);
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }
    }
}
