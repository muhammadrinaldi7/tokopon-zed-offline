<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'TokoPun') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 antialiased flex flex-col">

    <livewire:header />
    @auth
        @unless (auth()->user()->hasRole('cs'))
            <livewire:chatroom />
        @endunless
    @endauth
    <main class="flex-1">
        {{ $slot }}
    </main>

    @unless (request()->is('/'))
        <livewire:footer />
    @endunless

    <livewire:confirm-modal />
    <livewire:toast-notification />
    <script>
        /**
         * Global Livewire Image Auto Compressor
         * @param {Event} event - Event dari @change="customCompressHandler"
         * @param {String} wirePropertyName - Nama properti/field di komponen Livewire (misal: 'photo_depan', 'foto_ktp')
         */
        function customCompressHandler(event, wirePropertyName) {
            const file = event.target.files[0];
            if (!file) return;

            const maxSize = 5 * 1024 * 1024; // 5 MB

            // PERBAIKAN DINAMIS: Mencari container Livewire terdekat secara otomatis dari DOM Element
            const livewireElement = event.target.closest('[wire\\:id]');
            if (!livewireElement) {
                console.error('[Compress Error] Element ini tidak berada di dalam komponen Livewire.');
                return;
            }
            const component = window.Livewire.find(livewireElement.getAttribute('wire:id'));

            // Log awal deteksi file masuk
            console.log(`%c[Global Compressor] Target Field: ${wirePropertyName}`,
                'color: #4e44db; font-weight: bold; font-size: 11px;');
            console.log(`• Nama File   : ${file.name}`);
            console.log(`• Ukuran Asli : ${(file.size / (1024 * 1024)).toFixed(2)} MB`);

            // JIKA DI BAWAH 5MB (Langsung Upload Asli)
            if (file.size <= maxSize) {
                console.log('%c[Info] Ukuran file aman (<= 5MB). Langsung mengunggah file asli...',
                    'color: #65a30d; font-weight: bold;');

                component.set(wirePropertyName, null);

                // Memanggil fungsi upload dinamis melalui instance component yang ditemukan
                component.upload(wirePropertyName, file,
                    (uploadedName) => console.log(`%c[Upload Success] File asli "${file.name}" terunggah!`,
                        'color: #16a34a; font-weight: bold;'),
                    () => console.error(`[Upload Error] Gagal mengunggah file asli pada: ${wirePropertyName}`),
                    (progressEvent) => {}
                );
                return;
            }

            // JIKA DI ATAS 5MB (Proses Kompresi Lokal)
            console.log('%c[Warning] File besar (> 5MB). Memulai kompresi kanvas di sisi browser...',
                'color: #ea580c; font-weight: bold;');
            component.set(wirePropertyName, null);

            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function(eventReader) {
                const img = new Image();
                img.src = eventReader.target.result;
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;

                    const maxResolution = 1920;
                    if (width > maxResolution || height > maxResolution) {
                        if (width > height) {
                            height *= maxResolution / width;
                            width = maxResolution;
                        } else {
                            width *= maxResolution / height;
                            height = maxResolution;
                        }
                        console.log(`[Resizing] Dimensi disesuaikan menjadi: ${width}px x ${Math.round(height)}px`);
                    }

                    canvas.width = width;
                    canvas.height = height;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(function(blob) {
                        const compressedFile = new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });

                        console.log(
                            `%c[Done] Ukuran Baru: ${(compressedFile.size / (1024 * 1024)).toFixed(2)} MB`,
                            'color: #16a34a; font-weight: bold;');

                        // Mengunggah file hasil kompresi lewat instance component
                        component.upload(wirePropertyName, compressedFile,
                            () => console.log(
                                `%c[Upload Success] File kompresi "${wirePropertyName}" terunggah!`,
                                'color: #16a34a; font-weight: bold;'),
                            () => console.error(
                                `[Upload Error] Gagal mengunggah file kompresi pada: ${wirePropertyName}`
                            ),
                            (progress) => {}
                        );
                    }, 'image/jpeg', 0.65);
                };
            };
        }
    </script>
</body>

</html>
