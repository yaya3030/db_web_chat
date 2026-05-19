<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable; // HasApiTokens sudah dihapus di sini karena tidak dipakai di Laravel 11

    /**
     * Atribut yang dapat diisi massal (Mass Assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Atribut yang harus disembunyikan untuk serialisasi array.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * RELASI UTAMA: User bisa mengikuti banyak grup (Many to Many)
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * RELASI TAMBAHAN: User bisa mengirim banyak pesan (One to Many)
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
}