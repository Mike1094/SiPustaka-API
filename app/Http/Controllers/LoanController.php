<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Models\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    // GET /api/loans - daftar semua peminjaman
    public function index(): JsonResponse
    {
        return response()->json(
            Loan::with(['book', 'user:id,name,email'])->latest()->get()
        );
    }

    // POST /api/loans - tambah peminjaman baru (user_id dari pengguna yang login)
    public function store(StoreLoanRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (! isset($data['status'])) {
            $data['status'] = 'Dipinjam';
        }

        $loan = $request->user()->loans()->create($data);

        return response()->json($loan->load(['book', 'user:id,name,email']), 201);
    }

    // GET /api/loans/{id} - detail satu peminjaman
    public function show(int|string $id): JsonResponse
    {
        return response()->json(
            $this->findLoan($id)->load(['book', 'user:id,name,email'])
        );
    }

    // PATCH /api/loans/{id} - ubah peminjaman (hanya milik sendiri)
    public function update(UpdateLoanRequest $request, int|string $id): JsonResponse
    {
        $loan = $this->findOwnedLoan($request, $id);
        $loan->update($request->validated());

        return response()->json($loan->load(['book', 'user:id,name,email']));
    }

    // DELETE /api/loans/{id} - hapus peminjaman (hanya milik sendiri)
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $loan = $this->findOwnedLoan($request, $id);
        $loan->delete();

        return response()->json(['message' => 'Peminjaman berhasil dihapus']);
    }

    // Pastikan peminjaman hanya boleh diubah/dihapus oleh pemiliknya
    private function findOwnedLoan(Request $request, int|string $id): Loan
    {
        $loan = $this->findLoan($id);
        if ($loan->user_id != $request->user()->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah peminjaman ini');
        }
        return $loan;
    }

    private function findLoan(int|string $id): Loan
    {
        $loan = Loan::find($id);
        if (! $loan) {
            abort(404, 'Peminjaman tidak ditemukan');
        }
        return $loan;
    }
}
