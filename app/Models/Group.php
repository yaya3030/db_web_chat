<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi massal (Mass Assignable).
     * Kolom 'created_by' WAJIB didaftarkan di sini agar bisa disimpan lewat Controller!
     */
    protected $fillable = [
        'name',
        'created_by'
    ];

    /**
     * RELASI UTAMA: Grup memiliki banyak anggota user (Many to Many)
     * Terhubung melalui tabel pivot 'group_user'
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * RELASI TAMBAHAN: Satu grup bisa menampung banyak pesan (One to Many)
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * RELASI TAMBAHAN (Opsional): Mengetahui siapa user pembuat grup ini
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}