@extends('layouts.app')

@section('title', 'Manajemen Pengguna - CKPS Mapping')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <p class="page-eyebrow">Administrasi Sistem</p>
            <h1 class="page-title">Manajemen Pengguna</h1>
            <p class="page-description">Kelola role pengguna yang memiliki akses ke CKPS Mapping.</p>
        </div>
    </div>

    <div class="panel">
        <div class="table-toolbar">
            <div>
                <h2>Daftar Pengguna</h2>
                <p>Admin memiliki akses pengelolaan sistem, sedangkan User menggunakan fitur operasional sesuai hak akses.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Terdaftar</th>
                        <th style="width:240px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->isAdmin())
                                    <span class="status-badge status-success">Admin</span>
                                @else
                                    <span class="status-badge">User</span>
                                @endif
                            </td>
                            <td>{{ optional($user->created_at)->format('d M Y H:i') }}</td>
                            <td>
                                @if(auth()->id() === $user->id)
                                    <span class="muted">Akun sedang digunakan</span>
                                @else
                                    <form method="POST" action="{{ route('admin.users.role.update', $user) }}" style="display:flex; gap:8px; align-items:center;">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="form-select" style="min-width:120px;">
                                            <option value="user" @selected($user->role === 'user')>User</option>
                                            <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                        </select>
                                        <button type="submit" class="btn btn-small">Simpan</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">Belum ada pengguna.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div style="padding:18px;">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection
