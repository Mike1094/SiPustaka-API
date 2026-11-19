<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookController extends Controller
{
    // GET /api/books - daftar semua buku (publik)
    public function index(): JsonResponse
    {
        return response()->json(Book::latest()->get());
    }

    // POST /api/books - tambah buku baru (opsional sampul)
    public function store(StoreBookRequest $request): JsonResponse
    {
        $data = $request->safe()->only([
            'judul',
            'penulis',
            'penerbit',
            'tahun_terbit',
            'stok',
        ]);

        // Jika ada file cover, simpan ke storage/app/private/books
        if ($request->hasFile('cover')) {
            $data = [...$data, ...$this->storeCover($request->file('cover'))];
        }

        $book = Book::create($data);

        return response()->json($book, 201);
    }

    // GET /api/books/{id} - detail satu buku (publik)
    public function show(int|string $id): JsonResponse
    {
        return response()->json($this->findBook($id));
    }

    // PATCH /api/books/{id} - ubah data buku
    // Jika mengirim file sampul, gunakan POST + field _method=PATCH
    public function update(UpdateBookRequest $request, int|string $id): JsonResponse
    {
        $book = $this->findBook($id);
        $data = $request->safe()->only([
            'judul',
            'penulis',
            'penerbit',
            'tahun_terbit',
            'stok',
        ]);

        if ($request->hasFile('cover')) {
            // Hapus sampul lama lalu simpan sampul baru
            $this->deleteCover($book);
            $data = [...$data, ...$this->storeCover($request->file('cover'))];
        } elseif ($request->boolean('remove_cover')) {
            // Hapus sampul tanpa mengganti
            $this->deleteCover($book);
            $data = [...$data, 'cover' => null];
        }

        $book->update($data);

        return response()->json($book);
    }

    // DELETE /api/books/{id} - hapus buku beserta sampulnya
    public function destroy(int|string $id): JsonResponse
    {
        $book = $this->findBook($id);
        $this->deleteCover($book);
        $book->delete();

        return response()->json(['message' => 'Buku berhasil dihapus']);
    }

    // GET /api/books/{id}/cover - membuka file sampul buku (publik)
    public function cover(int|string $id): StreamedResponse
    {
        $book = $this->findBook($id);

        if (! $book->cover || ! Storage::disk('local')->exists($book->cover)) {
            abort(404, 'Sampul buku tidak ditemukan');
        }

        return Storage::disk('local')->response($book->cover, basename($book->cover), [], 'inline');
    }

    private function storeCover(UploadedFile $file): array
    {
        return [
            'cover' => $file->store('books', 'local'),
        ];
    }

    private function deleteCover(Book $book): void
    {
        if ($book->cover) {
            Storage::disk('local')->delete($book->cover);
        }
    }

    private function findBook(int|string $id): Book
    {
        $book = Book::find($id);
        if (! $book) {
            abort(404, 'Buku tidak ditemukan');
        }
        return $book;
    }
}
