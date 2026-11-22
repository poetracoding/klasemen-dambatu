<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\Poster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PosterController extends Controller
{
    public function index()
    {
        $liga = Liga::current();
        $posters = Poster::orderBy('urutan')->orderBy('id')->get();

        return view('admin.poster', compact('liga', 'posters'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'nullable|string|max:120',
            'deskripsi' => 'nullable|string|max:255',
            'poster' => 'required_without:posters|file|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
            'posters' => 'nullable|array',
            'posters.*' => 'file|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
            'is_active' => 'nullable|boolean',
        ]);

        $liga = Liga::current();
        $dir = public_path('uploads/posters');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $files = [];
        if ($request->hasFile('posters')) {
            $uploaded = $request->file('posters');
            $files = is_array($uploaded) ? $uploaded : [$uploaded];
        }
        if ($request->hasFile('poster')) {
            $files[] = $request->file('poster');
        }

        $maxUrutan = (int) Poster::max('urutan');
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : true;
        $count = 0;

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $ext = $this->resolveSafeExtension($file);
            $filename = 'poster_' . time() . '_' . Str::lower(Str::random(8)) . '.' . $ext;
            $file->move($dir, $filename);

            $maxUrutan++;
            Poster::create([
                'liga_id' => $liga->id,
                'judul' => $request->filled('judul') ? trim($request->judul) : null,
                'deskripsi' => $request->filled('deskripsi') ? trim($request->deskripsi) : null,
                'file_path' => 'uploads/posters/' . $filename,
                'urutan' => $maxUrutan,
                'is_active' => $isActive,
            ]);
            $count++;
        }

        return back()->with('success', $count . ' poster informasi berhasil diunggah.');
    }

    public function update(Request $request, $id)
    {
        $poster = Poster::findOrFail($id);

        $request->validate([
            'judul' => 'nullable|string|max:120',
            'deskripsi' => 'nullable|string|max:255',
            'urutan' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'poster' => 'nullable|file|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
        ]);

        $filePath = $poster->file_path;
        if ($request->hasFile('poster')) {
            $dir = public_path('uploads/posters');
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            if (str_starts_with($poster->file_path, 'uploads/posters/') && file_exists(public_path($poster->file_path))) {
                @unlink(public_path($poster->file_path));
            }

            $file = $request->file('poster');
            $ext = $this->resolveSafeExtension($file);
            $filename = 'poster_' . time() . '_' . Str::lower(Str::random(8)) . '.' . $ext;
            $file->move($dir, $filename);
            $filePath = 'uploads/posters/' . $filename;
        }

        $poster->update([
            'judul' => $request->filled('judul') ? trim($request->judul) : null,
            'deskripsi' => $request->filled('deskripsi') ? trim($request->deskripsi) : null,
            'urutan' => $request->filled('urutan') ? (int) $request->urutan : $poster->urutan,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $poster->is_active,
            'file_path' => $filePath,
        ]);

        return back()->with('success', 'Poster informasi berhasil diperbarui.');
    }

    public function toggleStatus($id)
    {
        $poster = Poster::findOrFail($id);
        $poster->is_active = !$poster->is_active;
        $poster->save();

        $statusLabel = $poster->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Poster informasi berhasil {$statusLabel}.");
    }

    public function destroy($id)
    {
        $poster = Poster::findOrFail($id);

        if (str_starts_with($poster->file_path, 'uploads/posters/') && file_exists(public_path($poster->file_path))) {
            @unlink(public_path($poster->file_path));
        }

        $poster->delete();

        return back()->with('success', 'Poster informasi berhasil dihapus.');
    }

    private function resolveSafeExtension($file): string
    {
        $ext = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'png'));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return 'png';
        }

        return $ext;
    }
}
