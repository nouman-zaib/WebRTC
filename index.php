<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusCall - Secure WebRTC Video & Audio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
    <style>
        @keyframes pulse-subtle {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.6;
            }
        }

        .animate-pulse-subtle {
            animation: pulse-subtle 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>

<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between overflow-x-hidden font-sans">

    <!-- HTTP Security Warning Banner (Shows if browser blocks camera on HTTP IP address) -->
    <div id="http-warning-banner"
        class="hidden bg-amber-900/60 border-b border-amber-500/40 px-4 py-3 text-amber-200 text-xs sm:text-sm shadow-md">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-triangle-exclamation text-amber-400 text-lg flex-shrink-0"></i>
                <div>
                    <span class="font-semibold text-amber-300">Camera Blocked by Browser (HTTP IP Connection):</span>
                    <span class="text-amber-200/90 ml-1">Chrome/Edge blocks camera on HTTP (<code id="current-origin"
                            class="bg-amber-950/80 border border-amber-500/30 px-1.5 py-0.5 rounded font-mono text-amber-300 text-xs"></code>).</span>
                </div>
            </div>
            <div class="flex items-center space-x-2 flex-shrink-0 self-end sm:self-auto">
                <button onclick="copyFlagsUrl()"
                    class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 px-3 py-1.5 rounded-lg font-medium transition active:scale-95 text-xs flex items-center space-x-1.5">
                    <i class="fa-regular fa-copy"></i>
                    <span>Copy Chrome Flag Link</span>
                </button>
                <button onclick="showHttpHelpModal()"
                    class="bg-amber-500 hover:bg-amber-400 text-slate-950 px-3 py-1.5 rounded-lg font-bold transition active:scale-95 text-xs flex items-center space-x-1">
                    <i class="fa-solid fa-wrench"></i>
                    <span>Fix Guide</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header
        class="flex flex-wrap justify-between items-center px-6 py-4 bg-slate-900/90 backdrop-blur-md border-b border-slate-800/80 sticky top-0 z-30 shadow-lg">
        <div class="flex items-center space-x-3">
            <div
                class="bg-gradient-to-r from-indigo-500 to-purple-600 p-2.5 rounded-xl text-white shadow-md shadow-indigo-500/20">
                <i class="fa-solid fa-video text-lg"></i>
            </div>
            <div>
                <h1
                    class="text-xl font-bold tracking-wide bg-gradient-to-r from-white to-slate-300 bg-clip-text text-transparent">
                    Nexus<span class="text-indigo-400">Call</span></h1>
                <p class="text-xs text-slate-400">P2P Encrypted Video Chat</p>
            </div>
        </div>

        <div class="flex items-center space-x-3 mt-2 sm:mt-0">
            <button onclick="initializeMedia()"
                class="text-xs px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 rounded-lg transition flex items-center space-x-1.5 active:scale-95"
                title="Retry camera access">
                <i class="fa-solid fa-rotate-right text-indigo-400"></i>
                <span>Retry Camera</span>
            </button>
            <div id="connection-status"
                class="text-xs px-3.5 py-1.5 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded-full font-medium flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span id="status-text">Initializing Media...</span>
            </div>
        </div>
    </header>

    <!-- Notification Toast -->
    <div id="toast-container" class="fixed top-24 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <!-- HTTP Fix Help Modal -->
    <div id="http-help-modal"
        class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center hidden p-4">
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-2xl max-w-lg w-full space-y-4">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-amber-400"></i>
                    How to Unblock Camera on HTTP (LAN Call)
                </h3>
                <button onclick="hideHttpHelpModal()" class="text-slate-400 hover:text-white transition"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="text-xs text-slate-300 space-y-3 leading-relaxed">
                <p class="text-slate-400">Chrome and Edge block camera & microphone access on HTTP IP addresses (like
                    <span class="font-mono text-indigo-400 font-semibold" id="help-origin-ip"></span>) for security.
                    Follow these simple steps to allow camera:</p>

                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 space-y-2">
                    <p class="font-semibold text-amber-400 flex items-center gap-1.5">
                        <span
                            class="bg-amber-500/20 text-amber-300 w-5 h-5 rounded-full flex items-center justify-center text-xs">1</span>
                        Open Browser Flag Page
                    </p>
                    <p>Paste this URL into a new tab address bar in Chrome or Edge:</p>
                    <div class="flex items-center gap-2">
                        <input type="text" id="flag-url-input" readonly
                            value="chrome://flags/#unsafely-treat-insecure-origin-as-secure"
                            class="bg-slate-900 border border-slate-700 px-2.5 py-1.5 rounded text-indigo-300 font-mono w-full select-all text-xs outline-none">
                        <button onclick="copyFlagsUrl()"
                            class="bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 px-3 py-1.5 rounded hover:bg-indigo-600/50 transition flex-shrink-0 font-medium">Copy</button>
                    </div>
                </div>

                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 space-y-1.5">
                    <p class="font-semibold text-amber-400 flex items-center gap-1.5">
                        <span
                            class="bg-amber-500/20 text-amber-300 w-5 h-5 rounded-full flex items-center justify-center text-xs">2</span>
                        Enter Server URL & Enable
                    </p>
                    <p>In the text box under <em>"Insecure origins treated as secure"</em>, enter:</p>
                    <p class="font-mono text-indigo-400 font-bold bg-slate-900 p-2 rounded border border-slate-800 text-center select-all"
                        id="help-origin-url"></p>
                    <p class="text-slate-400">Change the dropdown menu on the right from <span
                            class="text-rose-400">Disabled</span> to <span
                            class="text-emerald-400 font-bold">Enabled</span>.</p>
                </div>

                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 space-y-1">
                    <p class="font-semibold text-amber-400 flex items-center gap-1.5">
                        <span
                            class="bg-amber-500/20 text-amber-300 w-5 h-5 rounded-full flex items-center justify-center text-xs">3</span>
                        Relaunch Browser
                    </p>
                    <p class="text-slate-400">Click the <strong>Relaunch</strong> button at the bottom right. Refresh
                        this page and camera access will be granted!</p>
                </div>
            </div>
            <div class="pt-2 flex justify-end">
                <button onclick="hideHttpHelpModal()"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-xl text-xs transition shadow-lg">Got
                    it, Close</button>
            </div>
        </div>
    </div>

    <!-- Incoming Call Modal -->
    <div id="incoming-call-modal"
        class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center hidden">
        <div
            class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-2xl max-w-sm w-full text-center space-y-5 animate-bounce-short">
            <div
                class="w-16 h-16 bg-indigo-600/20 border border-indigo-500/30 rounded-full flex items-center justify-center mx-auto text-indigo-400 text-2xl animate-pulse">
                <i class="fa-solid fa-phone-incoming"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-white">Incoming Call</h3>
                <p id="caller-id-display"
                    class="text-xs font-mono text-indigo-400 mt-1 break-all bg-slate-950 p-2 rounded-lg border border-slate-800">
                    Peer ID...</p>
            </div>
            <div class="flex items-center justify-center space-x-4 pt-2">
                <button onclick="declineCall()"
                    class="flex-1 py-2.5 px-4 bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-500/30 rounded-xl font-medium transition text-sm flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-xmark"></i>
                    <span>Decline</span>
                </button>
                <button onclick="acceptCall()"
                    class="flex-1 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-medium transition text-sm shadow-lg shadow-emerald-600/20 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-phone"></i>
                    <span>Accept</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Video Grid -->
    <main
        class="flex-1 p-4 md:p-6 grid grid-cols-1 md:grid-cols-2 gap-6 max-w-7xl mx-auto w-full items-center justify-center">

        <!-- Local Stream -->
        <div
            class="relative bg-slate-900 rounded-2xl overflow-hidden shadow-2xl border border-slate-800/80 aspect-video flex items-center justify-center group">
            <video id="localVideo" autoplay muted playsinline
                class="w-full h-full object-cover transform -scale-x-100"></video>
            <div id="local-video-off-placeholder"
                class="absolute inset-0 bg-slate-900 flex flex-col items-center justify-center text-slate-500 space-y-2 hidden">
                <i class="fa-solid fa-video-slash text-4xl text-slate-600"></i>
                <p class="text-sm font-medium">Camera Off</p>
            </div>
            <div
                class="absolute bottom-4 left-4 bg-slate-950/80 backdrop-blur-md px-3.5 py-1.5 rounded-xl text-xs font-semibold border border-slate-700/50 flex items-center space-x-2 shadow-md">
                <span id="local-status-dot" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span id="local-label">You (Local Stream)</span>
            </div>
        </div>

        <!-- Remote Stream -->
        <div
            class="relative bg-slate-900 rounded-2xl overflow-hidden shadow-2xl border border-slate-800/80 aspect-video flex items-center justify-center group">
            <video id="remoteVideo" autoplay playsinline class="w-full h-full object-cover"></video>

            <!-- Click to Play sound overlay if autoplay gets blocked by browser -->
            <div id="autoplay-play-btn"
                class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm flex flex-col items-center justify-center text-white space-y-3 z-10 hidden">
                <button onclick="enableRemoteAudioVideo()"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-3 rounded-xl font-bold shadow-xl transition flex items-center space-x-2 active:scale-95">
                    <i class="fa-solid fa-play"></i>
                    <span>Click to Unmute / Play Video</span>
                </button>
                <p class="text-xs text-slate-400">Browser required manual click to start audio stream</p>
            </div>

            <div id="remote-placeholder"
                class="absolute inset-0 bg-slate-900/90 flex flex-col items-center justify-center text-slate-400 space-y-3 p-6 text-center">
                <div
                    class="w-16 h-16 rounded-full bg-slate-800/80 border border-slate-700/50 flex items-center justify-center text-slate-500 text-2xl">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-300">Waiting for remote user...</p>
                    <p class="text-xs text-slate-500 mt-1 max-w-xs">Share your Peer ID or paste a remote Peer ID below
                        to start video calling.</p>
                </div>
            </div>
            <div
                class="absolute bottom-4 left-4 bg-slate-950/80 backdrop-blur-md px-3.5 py-1.5 rounded-xl text-xs font-semibold border border-slate-700/50 flex items-center space-x-2 shadow-md z-20">
                <span id="remote-status-dot" class="w-2 h-2 rounded-full bg-slate-500"></span>
                <span id="remote-status-label">Remote Peer</span>
            </div>
        </div>

    </main>

    <!-- Controls & ID Toolbar Footer -->
    <footer
        class="bg-slate-900/90 backdrop-blur-md border-t border-slate-800/80 py-4 px-6 flex flex-col lg:flex-row items-center justify-between gap-4 z-20">

        <!-- My Peer ID Box -->
        <div
            class="flex items-center space-x-2 w-full lg:w-auto bg-slate-950/60 p-2 rounded-xl border border-slate-800">
            <span class="text-xs font-semibold text-slate-400 px-2 uppercase tracking-wider">My ID:</span>
            <input type="text" id="my-id" readonly
                class="bg-slate-900 border border-slate-800 px-3 py-1.5 rounded-lg text-indigo-400 font-mono text-xs w-48 outline-none select-all"
                placeholder="Connecting to server...">
            <button onclick="copyMyId()"
                class="bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center space-x-1.5 active:scale-95"
                title="Copy My ID">
                <i class="fa-regular fa-copy"></i>
                <span>Copy</span>
            </button>
        </div>

        <!-- Call Action Controls -->
        <div class="flex items-center space-x-3">
            <button onclick="toggleAudio()" id="mic-btn"
                class="w-12 h-12 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white flex items-center justify-center transition shadow-lg border border-slate-700/50 active:scale-95"
                title="Toggle Microphone">
                <i class="fa-solid fa-microphone text-base" id="mic-icon"></i>
            </button>

            <button onclick="toggleVideo()" id="video-btn"
                class="w-12 h-12 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white flex items-center justify-center transition shadow-lg border border-slate-700/50 active:scale-95"
                title="Toggle Camera">
                <i class="fa-solid fa-video text-base" id="video-icon"></i>
            </button>

            <button onclick="toggleScreenShare()" id="screen-btn"
                class="w-12 h-12 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white flex items-center justify-center transition shadow-lg border border-slate-700/50 active:scale-95"
                title="Share Screen">
                <i class="fa-solid fa-desktop text-base" id="screen-icon"></i>
            </button>

            <button onclick="endCall()" id="end-btn"
                class="w-12 h-12 rounded-2xl bg-rose-600/80 hover:bg-rose-600 text-white flex items-center justify-center transition shadow-lg shadow-rose-600/20 active:scale-95"
                title="End Call">
                <i class="fa-solid fa-phone-slash text-base"></i>
            </button>
        </div>

        <!-- Peer ID Connect Box -->
        <div
            class="flex items-center space-x-2 w-full lg:w-auto bg-slate-950/60 p-2 rounded-xl border border-slate-800">
            <input type="text" id="peer-id-input" placeholder="Paste Remote Peer ID here..."
                class="bg-slate-900 border border-slate-800 px-3 py-1.5 rounded-lg text-slate-200 text-xs w-52 focus:border-indigo-500 outline-none transition font-mono">
            <button id="call-btn" onclick="connectToPeer()"
                class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white px-5 py-1.5 rounded-lg text-xs font-semibold transition shadow-lg shadow-indigo-600/20 flex items-center space-x-1.5 active:scale-95">
                <i class="fa-solid fa-paper-plane text-xs"></i>
                <span>Call</span>
            </button>
        </div>

    </footer>

    <script>
        let peer = null;
        let myStream = null;
        let isUsingFallbackStream = false;
        let currentCall = null;
        let incomingCallData = null;
        let isScreenSharing = false;

        const localVideo = document.getElementById('localVideo');
        const remoteVideo = document.getElementById('remoteVideo');
        const remotePlaceholder = document.getElementById('remote-placeholder');
        const statusText = document.getElementById('status-text');
        const statusEl = document.getElementById('connection-status');
        const myIdInput = document.getElementById('my-id');
        const incomingModal = document.getElementById('incoming-call-modal');
        const callerIdDisplay = document.getElementById('caller-id-display');
        const remoteStatusDot = document.getElementById('remote-status-dot');
        const remoteStatusLabel = document.getElementById('remote-status-label');
        const autoplayPlayBtn = document.getElementById('autoplay-play-btn');

        // Toast Helper
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `pointer-events-auto px-4 py-2.5 rounded-xl text-xs font-medium shadow-xl border flex items-center space-x-2 transition-all duration-300 transform translate-y-2 opacity-0 ${type === 'error' ? 'bg-rose-950/90 text-rose-300 border-rose-800' :
                    type === 'success' ? 'bg-emerald-950/90 text-emerald-300 border-emerald-800' :
                        'bg-slate-900/90 text-indigo-300 border-slate-700'
                }`;

            const icon = type === 'error' ? 'fa-circle-exclamation' : type === 'success' ? 'fa-circle-check' : 'fa-circle-info';
            toast.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;

            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('opacity-0', '-translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 4500);
        }

        function updateStatus(text, badgeType = 'warning') {
            statusText.textContent = text;
            if (badgeType === 'success') {
                statusEl.className = "text-xs px-3.5 py-1.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-full font-medium flex items-center space-x-2";
            } else if (badgeType === 'error') {
                statusEl.className = "text-xs px-3.5 py-1.5 bg-rose-500/10 text-rose-400 border border-rose-500/20 rounded-full font-medium flex items-center space-x-2";
            } else {
                statusEl.className = "text-xs px-3.5 py-1.5 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded-full font-medium flex items-center space-x-2";
            }
        }

        // Check HTTP / Insecure Context and display guide banner if camera restricted
        function checkHttpSecureContext() {
            const isLocalhost = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            const isHttps = location.protocol === 'https:';
            const originUrl = `${location.protocol}//${location.host}`;
            const originIp = location.host;

            document.getElementById('current-origin').textContent = originUrl;
            document.getElementById('help-origin-url').textContent = originUrl;
            document.getElementById('help-origin-ip').textContent = originIp;

            if (!isHttps && !isLocalhost) {
                document.getElementById('http-warning-banner').classList.remove('hidden');
            } else {
                document.getElementById('http-warning-banner').classList.add('hidden');
            }
        }

        function showHttpHelpModal() {
            document.getElementById('http-help-modal').classList.remove('hidden');
        }

        function hideHttpHelpModal() {
            document.getElementById('http-help-modal').classList.add('hidden');
        }

        function copyFlagsUrl() {
            const flagUrl = 'chrome://flags/#unsafely-treat-insecure-origin-as-secure';
            navigator.clipboard.writeText(flagUrl).then(() => {
                showToast("Chrome Flag URL copied to clipboard! Open a new tab and paste it.", "success");
            }).catch(() => {
                showToast("URL: chrome://flags/#unsafely-treat-insecure-origin-as-secure", "info");
            });
        }

        // Synthetic Video Stream Generator with animated canvas & silent audio track
        // Ensures WebRTC P2P connection succeeds even if physical camera is blocked or missing!
        function createFallbackStream(label = "Camera Blocked / Restricted") {
            isUsingFallbackStream = true;
            const canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 480;
            const ctx = canvas.getContext('2d');

            function draw() {
                // Gradient background
                const grad = ctx.createLinearGradient(0, 0, 640, 480);
                grad.addColorStop(0, '#0f172a');
                grad.addColorStop(1, '#1e1b4b');
                ctx.fillStyle = grad;
                ctx.fillRect(0, 0, 640, 480);

                // Animated pulsing ring
                const time = Date.now() * 0.003;
                const pulse = Math.sin(time) * 5 + 65;

                ctx.fillStyle = 'rgba(99, 102, 241, 0.15)';
                ctx.beginPath();
                ctx.arc(320, 200, pulse + 25, 0, Math.PI * 2);
                ctx.fill();

                ctx.fillStyle = 'rgba(99, 102, 241, 0.3)';
                ctx.beginPath();
                ctx.arc(320, 200, pulse + 10, 0, Math.PI * 2);
                ctx.fill();

                // User Avatar Circle
                ctx.fillStyle = '#312e81';
                ctx.beginPath();
                ctx.arc(320, 200, pulse, 0, Math.PI * 2);
                ctx.fill();

                // Head
                ctx.fillStyle = '#a5b4fc';
                ctx.beginPath();
                ctx.arc(320, 180, 26, 0, Math.PI * 2);
                ctx.fill();

                // Body
                ctx.beginPath();
                ctx.arc(320, 255, 45, Math.PI, 0);
                ctx.fill();

                // Text overlay
                ctx.fillStyle = '#f8fafc';
                ctx.font = 'bold 20px system-ui, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(label, 320, 310);

                ctx.fillStyle = '#94a3b8';
                ctx.font = '13px system-ui, sans-serif';
                ctx.fillText('P2P Fallback Media Active', 320, 338);

                requestAnimationFrame(draw);
            }
            draw();

            const stream = canvas.captureStream(30);

            // Add synthetic silent audio track for full WebRTC media negotiation
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = audioCtx.createOscillator();
                const dst = audioCtx.createMediaStreamDestination();
                const gain = audioCtx.createGain();
                gain.gain.value = 0; // Silent
                osc.connect(gain);
                gain.connect(dst);
                osc.start();
                dst.stream.getAudioTracks().forEach(t => stream.addTrack(t));
            } catch (e) {
                console.warn("Audio Context fallback track creation error:", e);
            }

            return stream;
        }

        // 1. Initialize User Media (Camera & Microphone with fallbacks)
        async function initializeMedia() {
            checkHttpSecureContext();

            // Stop existing stream tracks if re-initializing
            if (myStream) {
                myStream.getTracks().forEach(track => track.stop());
                myStream = null;
            }

            // Check if mediaDevices API is supported (Blocked by browser on HTTP IP addresses)
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                console.warn("navigator.mediaDevices.getUserMedia unavailable (Insecure Context HTTP)");
                showToast("Browser blocked camera over HTTP! Using synthetic fallback stream.", "error");

                myStream = createFallbackStream("Camera Restricted (HTTP)");
                localVideo.srcObject = myStream;
                document.getElementById('local-label').textContent = "You (Fallback Stream)";
                updateStatus("Fallback Stream Active (Camera Restricted)", "error");

                if (!peer) initializePeer();
                return;
            }

            try {
                myStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: true
                });
                isUsingFallbackStream = false;
                localVideo.srcObject = myStream;
                document.getElementById('local-label').textContent = "You (Camera Ready)";
                updateStatus("Camera & Mic Ready", "warning");
                showToast("Camera & Microphone initialized successfully!", "success");
            } catch (err) {
                console.warn("Media access error:", err);
                let reason = "Camera Unavailable";
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    reason = "Camera Permission Denied";
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    reason = "No Camera/Mic Found";
                } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                    reason = "Camera in use by another app";
                }

                updateStatus(`${reason} - Fallback Active`, "error");
                showToast(`${reason}! Fallback stream created for P2P connection.`, "error");

                myStream = createFallbackStream(reason);
                localVideo.srcObject = myStream;
                document.getElementById('local-label').textContent = `You (${reason})`;
            }

            if (!peer) {
                initializePeer();
            }
        }

        // 2. Initialize PeerJS connection with Google & Twilio STUN Servers
        function initializePeer() {
            updateStatus("Connecting to signaling server...", "warning");

            const peerOptions = {
                debug: 1,
                config: {
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' },
                        { urls: 'stun:stun1.l.google.com:19302' },
                        { urls: 'stun:stun2.l.google.com:19302' },
                        { urls: 'stun:stun3.l.google.com:19302' },
                        { urls: 'stun:stun4.l.google.com:19302' },
                        { urls: 'stun:global.stun.twilio.com:3478' }
                    ]
                }
            };

            peer = new Peer(peerOptions);

            peer.on('open', (id) => {
                myIdInput.value = id;
                updateStatus("Online / Ready for Call", "success");
                showToast("Connected to Peer Server. Your ID is ready!", "success");
            });

            // Listen for incoming calls
            peer.on('call', (call) => {
                incomingCallData = call;
                callerIdDisplay.textContent = call.peer;
                incomingModal.classList.remove('hidden');
                showToast(`Incoming call from ${call.peer.substring(0, 8)}...`, 'info');
            });

            peer.on('disconnected', () => {
                updateStatus("Disconnected from signaling server", "error");
                showToast("Signaling server disconnected. Reconnecting...", "error");
                if (peer && !peer.destroyed) {
                    peer.reconnect();
                }
            });

            peer.on('error', (err) => {
                console.error("PeerJS Error:", err);
                let message = "Peer connection error occurred.";

                if (err.type === 'peer-unavailable') {
                    message = "Remote Peer ID not found or offline. Check the ID!";
                } else if (err.type === 'invalid-id') {
                    message = "Invalid Peer ID format provided.";
                } else if (err.type === 'network') {
                    message = "Network connection failed. Check your internet connection.";
                } else if (err.type === 'browser-incompatible') {
                    message = "Your browser does not support WebRTC P2P.";
                }

                updateStatus(message, "error");
                showToast(message, "error");
                resetCallUI();
            });
        }

        // 3. Outgoing Call Handler
        function connectToPeer() {
            const peerIdInput = document.getElementById('peer-id-input');
            const peerId = peerIdInput.value.trim();

            if (!peer || !peer.open) {
                return showToast("Signaling server not connected yet! Please wait...", "error");
            }

            if (!peerId) {
                return showToast("Please paste or enter a remote Peer ID!", "error");
            }

            if (peerId === peer.id) {
                return showToast("You cannot call your own Peer ID!", "error");
            }

            const streamToSend = myStream || createFallbackStream("No Media Stream");

            updateStatus(`Calling ${peerId.substring(0, 8)}...`, "warning");
            showToast(`Initiating WebRTC call to ${peerId.substring(0, 8)}...`, "info");

            const call = peer.call(peerId, streamToSend);
            currentCall = call;
            handleCallEvents(call);
        }

        // 4. Accept Incoming Call
        function acceptCall() {
            incomingModal.classList.add('hidden');
            if (incomingCallData) {
                currentCall = incomingCallData;
                const streamToSend = myStream || createFallbackStream("No Media Stream");
                currentCall.answer(streamToSend);
                handleCallEvents(currentCall);
                incomingCallData = null;
            }
        }

        // 5. Decline Incoming Call
        function declineCall() {
            incomingModal.classList.add('hidden');
            if (incomingCallData) {
                incomingCallData.close();
                incomingCallData = null;
            }
            showToast("Incoming call declined", "info");
        }

        // 6. Handle Active Call Streams and Disconnections
        function handleCallEvents(call) {
            call.on('stream', (remoteStream) => {
                remoteVideo.srcObject = remoteStream;

                // Handle Browser Autoplay Policy safely
                const playPromise = remoteVideo.play();
                if (playPromise !== undefined) {
                    playPromise.catch(err => {
                        console.warn("Autoplay blocked by browser:", err);
                        autoplayPlayBtn.classList.remove('hidden');
                    });
                }

                remotePlaceholder.classList.add('hidden');
                remoteStatusDot.className = "w-2 h-2 rounded-full bg-emerald-500 animate-pulse";
                remoteStatusLabel.textContent = `Connected (${call.peer.substring(0, 8)}...)`;

                updateStatus("Connected - Live Call Active", "success");
                showToast("WebRTC Video Stream Connected!", "success");
            });

            call.on('close', () => {
                showToast("Call ended by peer.", "info");
                resetCallUI();
            });

            call.on('error', (err) => {
                console.error("Call stream error:", err);
                showToast("Call stream error encountered.", "error");
                resetCallUI();
            });
        }

        function enableRemoteAudioVideo() {
            autoplayPlayBtn.classList.add('hidden');
            remoteVideo.play();
        }

        // 7. Control Utilities
        function toggleAudio() {
            if (!myStream || !myStream.getAudioTracks().length) {
                return showToast("No audio input track active!", "error");
            }
            const audioTrack = myStream.getAudioTracks()[0];
            audioTrack.enabled = !audioTrack.enabled;
            const icon = document.getElementById('mic-icon');
            const btn = document.getElementById('mic-btn');

            if (audioTrack.enabled) {
                icon.className = "fa-solid fa-microphone text-base";
                btn.classList.remove('bg-rose-600/30', 'text-rose-400', 'border-rose-500/30');
                btn.classList.add('bg-slate-800', 'text-white');
                showToast("Microphone Unmuted", "info");
            } else {
                icon.className = "fa-solid fa-microphone-slash text-base";
                btn.classList.remove('bg-slate-800', 'text-white');
                btn.classList.add('bg-rose-600/30', 'text-rose-400', 'border-rose-500/30');
                showToast("Microphone Muted", "info");
            }
        }

        function toggleVideo() {
            if (!myStream || !myStream.getVideoTracks().length) {
                return showToast("No video input track active!", "error");
            }
            const videoTrack = myStream.getVideoTracks()[0];
            videoTrack.enabled = !videoTrack.enabled;
            const icon = document.getElementById('video-icon');
            const btn = document.getElementById('video-btn');
            const placeholder = document.getElementById('local-video-off-placeholder');

            if (videoTrack.enabled) {
                icon.className = "fa-solid fa-video text-base";
                btn.classList.remove('bg-rose-600/30', 'text-rose-400', 'border-rose-500/30');
                btn.classList.add('bg-slate-800', 'text-white');
                placeholder.classList.add('hidden');
                showToast("Camera Enabled", "info");
            } else {
                icon.className = "fa-solid fa-video-slash text-base";
                btn.classList.remove('bg-slate-800', 'text-white');
                btn.classList.add('bg-rose-600/30', 'text-rose-400', 'border-rose-500/30');
                placeholder.classList.remove('hidden');
                showToast("Camera Disabled", "info");
            }
        }

        async function toggleScreenShare() {
            if (!currentCall) {
                return showToast("Start or answer a call first to share screen!", "info");
            }

            try {
                if (!isScreenSharing) {
                    const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    const screenTrack = screenStream.getVideoTracks()[0];

                    if (currentCall && currentCall.peerConnection) {
                        const sender = currentCall.peerConnection.getSenders().find(s => s.track.kind === 'video');
                        if (sender) sender.replaceTrack(screenTrack);
                    }

                    localVideo.srcObject = screenStream;
                    isScreenSharing = true;
                    document.getElementById('screen-btn').classList.add('text-indigo-400', 'border-indigo-500');
                    showToast("Screen Sharing Started", "success");

                    screenTrack.onended = () => {
                        stopScreenShare();
                    };
                } else {
                    stopScreenShare();
                }
            } catch (err) {
                console.error("Screen share error:", err);
                showToast("Screen share canceled or not allowed.", "error");
            }
        }

        function stopScreenShare() {
            if (isScreenSharing && myStream) {
                const videoTrack = myStream.getVideoTracks()[0];
                if (currentCall && currentCall.peerConnection) {
                    const sender = currentCall.peerConnection.getSenders().find(s => s.track.kind === 'video');
                    if (sender && videoTrack) sender.replaceTrack(videoTrack);
                }
                localVideo.srcObject = myStream;
                isScreenSharing = false;
                document.getElementById('screen-btn').classList.remove('text-indigo-400', 'border-indigo-500');
                showToast("Screen Sharing Stopped", "info");
            }
        }

        function endCall() {
            if (currentCall) {
                currentCall.close();
                currentCall = null;
            }
            showToast("Call ended", "info");
            resetCallUI();
        }

        function resetCallUI() {
            remoteVideo.srcObject = null;
            remotePlaceholder.classList.remove('hidden');
            autoplayPlayBtn.classList.add('hidden');
            remoteStatusDot.className = "w-2 h-2 rounded-full bg-slate-500";
            remoteStatusLabel.textContent = "Remote Peer";

            if (peer && peer.open) {
                updateStatus("Online / Ready for Call", "success");
            } else {
                updateStatus("Call Ended / Offline", "warning");
            }
        }

        function copyMyId() {
            if (!myIdInput.value || myIdInput.value.includes('Connecting')) {
                return showToast("Peer ID is not generated yet!", "error");
            }
            navigator.clipboard.writeText(myIdInput.value).then(() => {
                showToast("Your Peer ID copied to clipboard!", "success");
            }).catch(() => {
                myIdInput.select();
                document.execCommand('copy');
                showToast("Your Peer ID copied to clipboard!", "success");
            });
        }

        // Initialize App on Window Load
        window.onload = initializeMedia;
    </script>
</body>

</html>