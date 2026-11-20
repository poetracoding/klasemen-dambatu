<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\Liga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $validated = $request->validate([
            'judul' => 'required|string|max:180',
            'kategori' => ['required', 'string', Rule::in(Berita::KATEGORI_LIST)],
            'ringkasan' => 'nullable|string|max:300',
            'isi' => 'required|string|max:20000',
            'status' => ['required', 'string', Rule::in(['published', 'draft'])],
            'gambar' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $liga = Liga::current();
        $gambarPath = null;

        if ($request->hasFile('gambar')) {
            $gambarPath = $this->storeUploadedImage($request->file('gambar'));
        }

        $judul = trim($validated['judul']);
        $status = $validated['status'];

        Berita::create([
            'liga_id' => $liga->id,
            'user_id' => Auth::id(),
            'judul' => $judul,
            'slug' => Berita::generateUniqueSlug($judul),
            'kategori' => $validated['kategori'],
            'ringkasan' => isset($validated['ringkasan']) && trim($validated['ringkasan']) !== '' ? trim($validated['ringkasan']) : null,
            'isi' => trim($validated['isi']),
            'gambar' => $gambarPath,
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);

        return back()->with('success', 'Berita berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $berita = Berita::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'required|string|max:180',
            'kategori' => ['required', 'string', Rule::in(Berita::KATEGORI_LIST)],
            'ringkasan' => 'nullable|string|max:300',
            'isi' => 'required|string|max:20000',
            'status' => ['required', 'string', Rule::in(['published', 'draft'])],
            'gambar' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $gambarPath = $berita->gambar;
        if ($request->hasFile('gambar')) {
            $this->deletePhysicalImage($berita->gambar);
            $gambarPath = $this->storeUploadedImage($request->file('gambar'));
        }

        $judul = trim($validated['judul']);
        $slug = $judul !== $berita->judul
            ? Berita::generateUniqueSlug($judul, $berita->id)
            : $berita->slug;

        $status = $validated['status'];
        $publishedAt = $berita->published_at;
        if ($status === 'published' && $publishedAt === null) {
            $publishedAt = now();
        }

        $berita->update([
            'judul' => $judul,
            'slug' => $slug,
            'kategori' => $validated['kategori'],
            'ringkasan' => isset($validated['ringkasan']) && trim($validated['ringkasan']) !== '' ? trim($validated['ringkasan']) : null,
            'isi' => trim($validated['isi']),
            'gambar' => $gambarPath,
            'status' => $status,
            'published_at' => $publishedAt,
        ]);

        return back()->with('success', 'Berita berhasil diperbarui.');
    }

    public function toggleStatus($id)
    {
        $berita = Berita::findOrFail($id);
        $newStatus = $berita->status === 'published' ? 'draft' : 'published';

        $berita->status = $newStatus;
        if ($newStatus === 'published' && $berita->published_at === null) {
            $berita->published_at = now();
        }
        $berita->save();

        $label = $newStatus === 'published' ? 'dipublikasikan' : 'disimpan sebagai draft';
        return back()->with('success', "Berita berhasil {$label}.");
    }

    public function destroy($id)
    {
        $berita = Berita::findOrFail($id);
        $this->deletePhysicalImage($berita->gambar);
        $berita->delete();

        return back()->with('success', 'Berita berhasil dihapus.');
    }

    private function storeUploadedImage($file): string
    {
        $dir = public_path('uploads/berita');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'png'));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'png';
        }

        $filename = 'berita_' . time() . '_' . Str::lower(Str::random(8)) . '.' . $ext;
        $file->move($dir, $filename);

        return 'uploads/berita/' . $filename;
    }

    private function deletePhysicalImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/berita/') && file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}
