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

<body class="min-h-screen bg-neutral-100 antialiased flex flex-col font-paperlogy">

    <livewire:sidebar />
    <main class="flex-1 pb-24 lg:pb-0 lg:pl-20 lg:pt-0">
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

            const isImage = file.type.startsWith('image/') || /\.(jpe?g|png|webp|heic|heif|bmp)$/i.test(file.name);
            const maxSize = 1 * 1024 * 1024; // 1 MB

            // PERBAIKAN DINAMIS: Mencari container Livewire terdekat secara otomatis dari DOM Element
            const livewireElement = event.target.closest('[wire\\:id]');
            if (!livewireElement) {
                console.error('[Compress Error] Element ini tidak berada di dalam komponen Livewire.');
                return;
            }
            const component = window.Livewire.find(livewireElement.getAttribute('wire:id'));
            if (!component) {
                console.error('[Compress Error] Instance Livewire component tidak ditemukan.');
                return;
            }

            // Log awal deteksi file masuk
            console.log(`%c[Global Compressor] Target Field: ${wirePropertyName}`,
                'color: #4e44db; font-weight: bold; font-size: 11px;');
            console.log(`• Nama File   : ${file.name}`);
            console.log(`• Tipe File   : ${file.type || 'unknown'}`);
            console.log(`• Ukuran Asli : ${(file.size / (1024 * 1024)).toFixed(2)} MB`);

            // PENANGANAN FILE NON-GAMBAR
            if (!isImage) {
                if (file.size <= maxSize) {
                    console.log('%c[Info] File non-gambar (<= 1MB). Langsung mengunggah file asli...',
                        'color: #65a30d; font-weight: bold;');

                    const ext = file.name.split('.').pop() || 'tmp';
                    const randomStr = Math.random().toString(36).substring(2, 8);
                    const uniqueName = `${wirePropertyName}_${Date.now()}_${randomStr}.${ext}`;
                    const uniqueFile = new File([file], uniqueName, { type: file.type });

                    component.upload(wirePropertyName, uniqueFile,
                        (uploadedName) => console.log(`%c[Upload Success] File asli "${uniqueName}" terunggah!`,
                            'color: #16a34a; font-weight: bold;'),
                        () => console.error(`[Upload Error] Gagal mengunggah file asli pada: ${wirePropertyName}`),
                        (progressEvent) => {}
                    );
                } else {
                    console.error('[Error] File non-gambar melebihi batas 1MB.');
                    alert('File terlalu besar! Maksimal 1MB.');
                }
                return;
            }

            // PENANGANAN GAMBAR: KOMPRESI DI SISI BROWSER MENGGUNAKAN JPEG
            // Menggunakan JPEG karena didukung 100% di semua browser (iOS Safari, Android, WebView).
            // WebP pada Canvas di iOS sering kali fallback ke PNG (lossless) yang menyebabkan ukuran membengkak > 4MB.
            console.log('%c[Info] Memulai kompresi gambar di sisi browser (Format: JPEG)...',
                'color: #65a30d; font-weight: bold;');

            const blobUrl = URL.createObjectURL(file);
            const img = new Image();

            img.onload = function() {
                URL.revokeObjectURL(blobUrl);

                let width = img.width;
                let height = img.height;
                const maxResolution = 1600; // 1600px sangat tajam untuk inspeksi HP dan hemat memori

                if (width > maxResolution || height > maxResolution) {
                    if (width > height) {
                        height = Math.round(height * (maxResolution / width));
                        width = maxResolution;
                    } else {
                        width = Math.round(width * (maxResolution / height));
                        height = maxResolution;
                    }
                    console.log(`[Resizing] Dimensi disesuaikan menjadi: ${width}px x ${height}px`);
                } else {
                    width = Math.round(width);
                    height = Math.round(height);
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');

                // Isi background putih agar area transparan tidak menjadi hitam saat dikonversi ke JPEG
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, width, height);
                ctx.drawImage(img, 0, 0, width, height);

                // JPEG 0.75 menghasilkan kualitas tinggi dengan ukuran stabil 150KB - 350KB
                const quality = file.size > maxSize ? 0.75 : 0.82;
                const randomStr = Math.random().toString(36).substring(2, 8);
                const uniqueName = `${wirePropertyName}_${Date.now()}_${randomStr}.jpg`;

                canvas.toBlob(function(blob) {
                    if (!blob) {
                        console.error('[Compress Error] Canvas toBlob menghasilkan null.');
                        alert('Gagal memproses gambar kamera. Silakan coba ambil ulang.');
                        return;
                    }

                    const compressedFile = new File([blob], uniqueName, {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    });

                    console.log(
                        `%c[Done] Ukuran Baru: ${(compressedFile.size / (1024 * 1024)).toFixed(2)} MB (${Math.round(compressedFile.size / 1024)} KB)`,
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
                }, 'image/jpeg', quality);
            };

            img.onerror = function() {
                URL.revokeObjectURL(blobUrl);
                console.error('[Compress Error] Gagal merender gambar ke browser.');

                // Fallback untuk file kamera yang tidak dapat di-decode langsung oleh Image objek (misal HEIC di Safari lama)
                if (file.size <= 5 * 1024 * 1024) {
                    console.warn('[Compress Fallback] Mengunggah file asli (<= 5MB)...');
                    const randomStr = Math.random().toString(36).substring(2, 8);
                    const ext = file.name.split('.').pop() || 'jpg';
                    const fallbackFile = new File([file], `${wirePropertyName}_${Date.now()}_${randomStr}.${ext}`, {
                        type: file.type
                    });

                    component.upload(wirePropertyName, fallbackFile,
                        () => console.log(`%c[Upload Success] File asli fallback terunggah!`, 'color: #16a34a; font-weight: bold;'),
                        () => console.error(`[Upload Error] Gagal mengunggah file fallback pada: ${wirePropertyName}`),
                        (progress) => {}
                    );
                } else {
                    alert('Format foto kamera tidak dapat diproses browser dan ukurannya melebihi 5MB. Silakan gunakan format JPG/PNG.');
                }
            };

            img.src = blobUrl;
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
        document.addEventListener('livewire:initialized', () => {

            Livewire.on('print-receipt', (event) => {
                console.log('Event cetak diterima:', event);

                // Parsing payload Livewire v3
                let payload = event[0] || event.detail || event;
                let base64Data = payload?.base64Data || payload?.base64;
                let orderNumber = payload?.orderNumber || 'terbaru';

                if (!base64Data) {
                    console.error("Gagal! Data base64 tidak ditemukan.", payload);
                    alert("Data struk gagal dibuat.");
                    return;
                }

                const isAndroid = /Android/i.test(navigator.userAgent);

                if (isAndroid) {
                    // ==========================================
                    // JALUR ANDROID: Gunakan RawBT
                    // ==========================================
                    console.log("Perangkat Android terdeteksi, membuka RawBT...");
                    const rawbtUri = `rawbt:base64,${base64Data}`;
                    window.location.href = rawbtUri;

                } else {
                    // ==========================================
                    // JALUR PC/DESKTOP: Gunakan QZ Tray
                    // ==========================================
                    console.log("Perangkat Desktop terdeteksi, menggunakan QZ Tray...");
                    cetakDenganQZ(base64Data);
                }
            });
        });

        function cetakDenganQZ(base64Data) {
            // Pastikan library QZ sudah dimuat sebelumnya
            if (typeof qz === 'undefined') {
                console.error("Library QZ Tray belum dimuat!");
                return;
            }

            if (!qz.websocket.isActive()) {
                qz.websocket.connect().then(function() {
                    console.log("Berhasil terhubung ke QZ Tray!");
                    prosesPrintBase64(base64Data);
                }).catch(function(err) {
                    console.error("Gagal terhubung ke QZ.", err);
                    alert("Pastikan aplikasi QZ Tray sudah berjalan di komputer ini!");
                });
            } else {
                prosesPrintBase64(base64Data);
            }
        }

        function prosesPrintBase64(base64Data) {
            // Pastikan nama printer sesuai dengan yang ada di sistem OS (Windows/Mac)
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
                console.log("Struk berhasil dicetak!");
            }).catch(function(err) {
                console.error("Gagal mencetak: ", err);
                alert("Gagal mencetak struk. Cek koneksi printer atau konsol browser.");
            });
        }
    </script>
</body>

</html>
