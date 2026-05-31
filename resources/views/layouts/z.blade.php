<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'TokoPun') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('zlogoblack.svg') }}" sizes="any">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes laser-scan {
            0% {
                top: 0%;
            }

            50% {
                top: 100%;
            }

            100% {
                top: 0%;
            }
        }

        .animate-laser {
            animation: laser-scan 2s linear infinite;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50 antialiased flex flex-col">

    <livewire:sidebar />
    <main class="flex-1 pb-24 lg:pb-0 lg:ml-20">
        {{ $slot }}
    </main>

    <livewire:confirm-modal />
    <x-toast />
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
    {{-- Tambahkan atribut data-navigate-once di sini --}}
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript" data-navigate-once></script>
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.min.js"></script>
    <script>
        // Ubah 'let' menjadi 'var' agar aman dari error redeklarasi wire:navigate
        var html5QrcodeScanner;
        var currentInputIndex = null;
        var currentSnIndex = null;

        // Fungsi untuk membuka kamera
        function startScanner(index, snIndex = null) {
            currentInputIndex = index;
            currentSnIndex = snIndex;

            // Tampilkan Modal
            document.getElementById('scanner-modal').classList.remove('hidden');

            // Inisialisasi Scanner
            html5QrcodeScanner = new Html5Qrcode("reader");

            // Mulai kamera belakang (environment)
            html5QrcodeScanner.start({
                    facingMode: "environment"
                }, {
                    fps: 10, // Frame per second
                    qrbox: {
                        width: 250,
                        height: 150
                    } // Area scan bentuk persegi panjang (cocok untuk barcode SN)
                },
                (decodedText, decodedResult) => {
                    // JIKA BERHASIL SCAN:

                    // 1. Matikan kamera dan tutup modal
                    closeScanner();

                    // 2. Cari elemen input berdasarkan index (Ubah let jadi var di sini juga untuk konsistensi)
                    var inputId = currentSnIndex !== null ? 'sn_input_' + currentInputIndex + '_' + currentSnIndex :
                        'sn_input_' + currentInputIndex;
                    var inputElement = document.getElementById(inputId);

                    if (inputElement) {
                        // 3. Update value di input
                        inputElement.value = decodedText;

                        // 4. Trigger event 'change' agar Livewire menangkap perubahan ini (karena ada wire:change)
                        inputElement.dispatchEvent(new Event('change'));
                    }
                },
                (errorMessage) => {
                    // Proses scan berjalan... (diabaikan saja, tidak perlu di-log agar console tidak penuh)
                }
            ).catch((err) => {
                alert("Gagal mengakses kamera. Pastikan browser memiliki izin untuk menggunakan kamera.");
                console.error(err);
                closeScanner();
            });
        }

        // Fungsi untuk menutup kamera
        function closeScanner() {
            document.getElementById('scanner-modal').classList.add('hidden');

            if (html5QrcodeScanner) {
                // Tambahkan try-catch untuk mencegah error jika user menutup modal sebelum kamera benar-benar menyala
                try {
                    html5QrcodeScanner.stop().then((ignore) => {
                        html5QrcodeScanner.clear(); // Bersihkan DOM
                    }).catch((err) => {
                        console.error("Gagal mematikan scanner", err);
                    });
                } catch (err) {
                    console.log("Scanner dihentikan sebelum siap.");
                }
            }
        }
    </script>
    <script>
        // Gunakan window listener untuk menangkap dispatch dari Livewire v3
        window.addEventListener('print-receipt', event => {
            console.log('🔥 Event cetak berhasil ditangkap di Frontend!');

            // Di Livewire v3, data dari PHP otomatis masuk ke properti event.detail
            let payload = event.detail;
            console.log('Isi Data dari PHP:', payload);

            let base64Data = payload?.base64Data;
            let orderNumber = payload?.orderNumber || 'terbaru';

            if (!base64Data) {
                console.error("Gagal! Data base64 tidak ditemukan.");
                alert("Data struk kosong.");
                return;
            }

            const isAndroid = /Android/i.test(navigator.userAgent);

            if (isAndroid) {
                // ==========================================
                // JALUR ANDROID: Membuka RawBT
                // ==========================================
                console.log("Membuka aplikasi RawBT...");
                window.location.href = `rawbt:base64,${base64Data}`;
            } else {
                // ==========================================
                // JALUR PC / DESKTOP: Menjalankan QZ Tray
                // ==========================================
                console.log("Mencoba mencetak lewat QZ Tray...");
                cetakDenganQZ(base64Data);
            }
        });

        function cetakDenganQZ(base64Data) {
            // Antisipasi jika library qz-tray.js belum di-include di aplikasi
            if (typeof qz === 'undefined') {
                console.warn("Library QZ Tray tidak ditemukan di halaman ini.");
                alert("Sistem mendeteksi Anda di PC, tetapi library QZ belum dipasang.");
                return;
            }

            if (!qz.websocket.isActive()) {
                qz.websocket.connect().then(function() {
                    console.log("Berhasil terhubung ke WebSocket QZ Tray!");
                    prosesPrintBase64(base64Data);
                }).catch(function(err) {
                    console.error("Gagal terhubung ke QZ Tray.", err);
                    alert("Nyalakan aplikasi QZ Tray terlebih dahulu di komputer ini!");
                });
            } else {
                prosesPrintBase64(base64Data);
            }
        }

        function prosesPrintBase64(base64Data) {
            // Sesuaikan nama ini dengan nama printer kasir yang terdeteksi di Windows/Mac kamu
            var namaPrinter = "PrinterKasir";

            qz.printers.find(namaPrinter).then(function(printer) {
                console.log("Printer ditemukan: " + printer);
                var config = qz.configs.create(printer);

                var dataStruk = [{
                    type: 'raw',
                    format: 'base64',
                    data: base64Data
                }];

                return qz.print(config, dataStruk);
            }).then(function() {
                console.log("Sukses! Perintah cetak telah dikirim ke printer.");
            }).catch(function(err) {
                console.error("Gagal mengeksekusi cetak QZ: ", err);
                alert("Gagal mencetak. Pastikan nama printer '" + namaPrinter + "' sudah benar.");
            });
        }
    </script>
</body>

</html>
