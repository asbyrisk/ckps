```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelatihan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="container">
        <h1>Daftar Pelatihan</h1>

        <form method="GET" action="{{ route('pelatihan.index') }}">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama pelatihan..."
            >
            <button type="submit">Cari</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Pelatihan</th>
                    <th>Kompetensi</th>
                    <th>ASN</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pelatihan as $item)
                    <tr>
                        <td>{{ $pelatihan->firstItem() + $loop->index }}</td>
                        <td>{{ $item->nama }}</td>
                        <td>{{ $item->kompetensi_count }}</td>
                        <td>{{ $item->asn_count }}</td>
                        <td>
                            <a href="{{ route('pelatihan.show', $item) }}">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada data pelatihan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $pelatihan->links() }}
    </div>
</body>
</html>
```
