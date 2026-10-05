<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden Warjok</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .page-403 {
            width: 100vw;
            height: 100vh;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }

        /* Desktop: gunakan gambar landscape */
        .page-403 {
            background-image: url('/assets/403-access-desktop.jpg');
        }

        /* Mobile: gunakan gambar portrait */
        @media screen and (max-width: 767px) {
            .page-403 {
                background-image: url('/assets/403-access-phone.jpg');
            }
        }

        /* Tombol kembali */
        .back-btn {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #1e293b;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 28px;
            border-radius: 9999px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
            transition: background 0.2s, transform 0.15s;
            z-index: 9999;
        }

        .back-btn:hover {
            background: #0f172a;
            transform: translateX(-50%) scale(1.04);
        }

        .back-btn svg {
            width: 18px;
            height: 18px;
        }

        @media screen and (max-width: 767px) {
            .back-btn {
                bottom: 24px;
                padding: 10px 24px;
                font-size: 0.82rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-403"></div>

    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/' }}" class="back-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
        Kembali
    </a>
</body>
</html>
