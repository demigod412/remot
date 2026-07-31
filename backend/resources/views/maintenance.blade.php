<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance — {{ gs()->site_name ?? 'Job Station' }}</title>
    <script>(function(){if(localStorage.getItem('jobstation-theme')==='dark')document.documentElement.classList.add('dark');})()</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent: #2f54eb;
            --accent-soft: rgba(47,84,235,0.12);
        }

        html, body {
            height: 100%;
            font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body {
            background: var(--bg);
            color: var(--fg);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Animated background grid */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(47,84,235,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(47,84,235,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: grid-drift 20s linear infinite;
            pointer-events: none;
        }

        @keyframes grid-drift {
            0%   { background-position: 0 0; }
            100% { background-position: 60px 60px; }
        }

        /* Radial glow */
        .bg-glow {
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse 80% 60% at 50% 40%, rgba(47,84,235,0.07) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Floating orbs — dark mode only */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0;
            pointer-events: none;
            animation: orb-float 8s ease-in-out infinite;
            transition: opacity 0.3s;
        }
        html.dark .orb { opacity: 0.15; }

        .orb-1 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #2f54eb, transparent);
            top: -100px; left: -100px;
            animation-delay: 0s;
        }
        .orb-2 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, #1e6b2a, transparent);
            bottom: -80px; right: -80px;
            animation-delay: -4s;
        }

        @keyframes orb-float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50%       { transform: translate(30px, -30px) scale(1.1); }
        }

        /* Center card */
        .card {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 480px;
            width: 100%;
            padding: 0 24px;
        }

        /* Logo */
        .logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 40px;
        }

        .logo-svg {
            animation: logo-pulse 2.5s ease-in-out infinite;
        }

        @keyframes logo-pulse {
            0%, 100% { filter: drop-shadow(0 0 6px rgba(47,84,235,0.4)); }
            50%       { filter: drop-shadow(0 0 18px rgba(47,84,235,0.8)); }
        }

        .logo-name {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--fg);
        }

        /* Gear animation */
        .gear-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 36px;
        }

        .gear {
            width: 56px;
            height: 56px;
            opacity: 0.7;
        }

        .gear-big {
            animation: spin-cw 4s linear infinite;
        }
        .gear-small {
            width: 36px;
            height: 36px;
            margin-left: -8px;
            animation: spin-ccw 2.67s linear infinite;
        }

        @keyframes spin-cw  { to { transform: rotate(360deg); } }
        @keyframes spin-ccw { to { transform: rotate(-360deg); } }

        /* Progress bar */
        .progress-wrap {
            width: 100%;
            height: 3px;
            background: var(--border);
            border-radius: 99px;
            overflow: hidden;
            margin-bottom: 36px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--accent), #1e6b2a);
            border-radius: 99px;
            animation: progress-slide 2.4s ease-in-out infinite;
            transform-origin: left;
        }

        @keyframes progress-slide {
            0%   { width: 0%; margin-left: 0%; }
            50%  { width: 60%; margin-left: 20%; }
            100% { width: 0%; margin-left: 100%; }
        }

        /* Text */
        h1 {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.8px;
            line-height: 1.2;
            margin-bottom: 14px;
            color: var(--fg);
        }

        html.dark h1 {
            background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.65) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        p {
            font-size: 15px;
            line-height: 1.7;
            color: var(--fg-3);
            max-width: 360px;
            margin: 0 auto 36px;
        }

        /* Dots */
        .dots {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--accent);
            opacity: 0.3;
            animation: dot-bounce 1.4s ease-in-out infinite;
        }
        .dot:nth-child(1) { animation-delay: 0s; }
        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes dot-bounce {
            0%, 80%, 100% { opacity: 0.3; transform: scale(1); }
            40%           { opacity: 1;   transform: scale(1.4); }
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 24px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 12px;
            color: var(--fg-4);
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="bg-glow"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="card">

        {{-- Logo --}}
        <div class="logo-wrap">
            <svg class="logo-svg" width="28" height="28" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="8" width="18" height="12" rx="2.5" stroke="#2f54eb" stroke-width="2" fill="none"/><path d="M8 8V6.5A2 2 0 0 1 10 4.5h4A2 2 0 0 1 16 6.5V8" stroke="#2f54eb" stroke-width="2" fill="none" stroke-linecap="round"/><path d="M3 13.3h18" stroke="#2f54eb" stroke-width="2"/>
            </svg>
            <span class="logo-name">{{ gs()->site_name ?? 'Job Station' }}</span>
        </div>

        {{-- Animated gears --}}
        <div class="gear-wrap">
            <svg class="gear gear-big" viewBox="0 0 24 24" fill="none" stroke="#2f54eb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            </svg>
            <svg class="gear gear-small" viewBox="0 0 24 24" fill="none" stroke="rgba(47,84,235,0.5)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            </svg>
        </div>

        {{-- Progress --}}
        <div class="progress-wrap">
            <div class="progress-bar"></div>
        </div>

        <h1>We're under maintenance</h1>
        <p>We're making some improvements to give you a better experience. We'll be back shortly — thank you for your patience.</p>

        <div class="dots">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>

    <div class="footer">&copy; {{ date('Y') }} {{ gs()->site_name ?? 'Job Station' }}. All rights reserved.</div>
</body>
</html>
