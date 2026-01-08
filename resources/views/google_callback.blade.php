<!DOCTYPE html>
<html>
<head>
    <title>Otentikasi Berhasil</title>
</head>
<body>
    <script>
        // Data ke Frontend
        const authData = {
            success: true,
            token: "{{ $token }}",
            user: @json($user)
        };

        // Kirim data ke window yang membuka popup ini (Frontend)
        if (window.opener) {
            window.opener.postMessage(authData, "{{ $frontendUrl }}");
            
            // sedikit jeda sebelum menutup window agar pesan sampai
            setTimeout(() => {
                window.close();
            }, 100);
        } else {
            // Jika bukan popup, langsung redirect (fallback)
            window.location.href = "{{ $frontendUrl }}/auth/callback?token={{ $token }}";
        }
    </script>
</body>
</html>