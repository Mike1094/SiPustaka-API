<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'judul',
        'penulis',
        'penerbit',
        'tahun_terbit',
        'stok',
        'cover',
    ];

    // Lokasi file di server disembunyikan, kirim cover_url di JSON
    protected $hidden = ['cover'];

    protected $appends = ['cover_url'];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    // URL publik untuk membuka sampul buku, null jika tidak ada sampul
    protected function coverUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->cover
                ? url("/api/books/{$this->id}/cover")
                : null
        );
    }
}
