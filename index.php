<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusCall — Multi-User Video Conferences</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#312e81',
                            950: '#1e1b4b',
                        },
                        surface: {
                            base: '#070a13',
                            card: '#0e1526',
                            tile: '#131b31',
                            border: '#1f2b49',
                            highlight: '#2a3b63'
                        }
                    },
                    boxShadow: {
                        'glow-indigo': '0 0 25px -5px rgba(99, 102, 241, 0.35)',
                        'glow-emerald': '0 0 20px -5px rgba(16, 185, 129, 0.35)',
                        'dock': '0 20px 40px -15px rgba(0, 0, 0, 0.7)'
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'sound-wave': 'wave 1.2s ease-in-out infinite'
                    },
                    keyframes: {
                        wave: {
                            '0%, 100%': { height: '6px' },
                            '50%': { height: '18px' }
                        }
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- PeerJS WebRTC Wrapper -->
    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
    <style>
        /* Custom scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0e1526;
        }
        ::-webkit-scrollbar-thumb {
            background: #1f2b49;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #2a3b63;
        }
        /* Optional video mirror effect */
        .mirror-video {
            transform: scaleX(-1);
        }
    </style>
</head>

<body class="bg-surface-base text-slate-100 min-h-screen flex flex-col justify-between overflow-x-hidden font-sans selection:bg-brand-500 selection:text-white">

    <!-- Insecure Origin / HTTP Warning Banner -->
    <div id="http-warning-banner" class="hidden bg-amber-950/80 border-b border-amber-500/30 px-4 py-2.5 text-amber-200 text-xs sm:text-sm shadow-lg backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5">
            <div class="flex items-center space-x-2.5">
                <i class="fa-solid fa-triangle-exclamation text-amber-400 text-base flex-shrink-0"></i>
                <div>
                    <span class="font-semibold text-amber-300">Camera Blocked on HTTP LAN IP:</span>
                    <span class="text-amber-200/90 ml-1">Browsers require HTTPS or localhost for camera/mic permissions.</span>
                </div>
            </div>
            <div class="flex items-center space-x-2 flex-shrink-0 self-end sm:self-auto">
                <button onclick="copyFlagsUrl()" class="bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 px-3 py-1 rounded-lg font-medium transition active:scale-95 text-xs flex items-center space-x-1.5">
                    <i class="fa-regular fa-copy"></i>
                    <span>Copy Chrome Flag Link</span>
                </button>
                <button onclick="showHttpHelpModal()" class="bg-amber-500 hover:bg-amber-400 text-slate-950 px-3 py-1 rounded-lg font-bold transition active:scale-95 text-xs flex items-center space-x-1">
                    <i class="fa-solid fa-wrench"></i>
                    <span>Fix Guide</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Toast Container (placed cleanly below header) -->
    <div id="toast-container" class="fixed top-16 right-5 z-50 flex flex-col gap-2.5 pointer-events-none max-w-sm w-full px-4 sm:px-0"></div>

    <!-- ======================================================== -->
    <!-- SCREEN 1: PRE-JOIN ROOM LOBBY                             -->
    <!-- ======================================================== -->
    <div id="lobby-screen" class="flex-1 flex flex-col items-center justify-center p-4 sm:p-6 md:p-8">
        <div class="max-w-4xl w-full mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            <!-- Left Column: Video Preview & Test Controls -->
            <div class="lg:col-span-7 flex flex-col space-y-4">
                <div class="flex items-center space-x-3 mb-1">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-violet-500 flex items-center justify-center shadow-lg shadow-brand-500/30 text-white font-bold text-lg">
                        <i class="fa-solid fa-video"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-white flex items-center gap-2">
                            Nexus<span class="text-brand-400">Call</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-brand-500/20 text-brand-300 border border-brand-500/30">Rooms</span>
                        </h1>
                        <p class="text-xs text-slate-400">Secure WebRTC Multi-User Video Conferences</p>
                    </div>
                </div>

                <!-- Camera Preview Box -->
                <div class="relative bg-surface-card rounded-2xl overflow-hidden aspect-video border border-surface-border shadow-2xl flex items-center justify-center group">
                    <video id="lobby-preview-video" autoplay muted playsinline class="w-full h-full object-cover"></video>
                    
                    <div id="lobby-cam-off-placeholder" class="absolute inset-0 bg-surface-card flex flex-col items-center justify-center text-slate-500 space-y-2 hidden">
                        <div class="w-16 h-16 rounded-full bg-surface-tile border border-surface-border flex items-center justify-center text-slate-400 text-2xl">
                            <i class="fa-solid fa-video-slash"></i>
                        </div>
                        <p class="text-xs font-semibold text-slate-400">Camera is Turned Off</p>
                    </div>

                    <!-- Floating Preview Controls -->
                    <div class="absolute bottom-4 inset-x-0 flex items-center justify-center space-x-2 sm:space-x-3 z-10">
                        <button onclick="toggleLobbyAudio()" id="lobby-mic-btn" class="w-11 h-11 rounded-xl bg-surface-base/80 hover:bg-surface-base text-white border border-surface-border/80 backdrop-blur-md flex items-center justify-center transition shadow-lg active:scale-95" title="Toggle Mic Preview">
                            <i class="fa-solid fa-microphone text-sm" id="lobby-mic-icon"></i>
                        </button>
                        <button onclick="toggleLobbyVideo()" id="lobby-video-btn" class="w-11 h-11 rounded-xl bg-surface-base/80 hover:bg-surface-base text-white border border-surface-border/80 backdrop-blur-md flex items-center justify-center transition shadow-lg active:scale-95" title="Toggle Video Preview">
                            <i class="fa-solid fa-video text-sm" id="lobby-video-icon"></i>
                        </button>
                        <button onclick="toggleMirrorLocalVideo()" id="lobby-flip-btn" class="w-11 h-11 rounded-xl bg-surface-base/80 hover:bg-surface-base text-slate-300 hover:text-white border border-surface-border/80 backdrop-blur-md flex items-center justify-center transition shadow-lg active:scale-95" title="Flip / Mirror Video (Seedha / Ulta toggle)">
                            <i class="fa-solid fa-arrows-left-right text-brand-400 text-sm"></i>
                        </button>
                        <button onclick="reinitMedia()" class="px-3 py-2 rounded-xl bg-surface-base/80 hover:bg-surface-base text-slate-300 border border-surface-border/80 backdrop-blur-md flex items-center space-x-1.5 transition text-xs font-medium active:scale-95" title="Refresh Camera">
                            <i class="fa-solid fa-rotate-right text-brand-400 text-xs"></i>
                            <span>Reload</span>
                        </button>
                    </div>

                    <!-- Audio Activity Meter Indicator -->
                    <div class="absolute top-3 left-3 bg-surface-base/80 backdrop-blur-md px-2.5 py-1 rounded-lg border border-surface-border text-[11px] text-slate-300 flex items-center space-x-2">
                        <div class="flex items-center space-x-0.5">
                            <span class="w-1 h-3 bg-emerald-500 rounded-full animate-pulse"></span>
                            <span class="w-1 h-2 bg-emerald-400 rounded-full"></span>
                        </div>
                        <span id="lobby-media-status">Ready</span>
                    </div>
                </div>

                <p class="text-xs text-slate-500 text-center">
                    <i class="fa-solid fa-shield-halved text-brand-400 mr-1"></i>
                    Direct peer-to-peer encrypted connection. No video recording or tracking.
                </p>
            </div>

            <!-- Right Column: Join / Create Room Panel -->
            <div class="lg:col-span-5 bg-surface-card p-6 sm:p-7 rounded-3xl border border-surface-border shadow-2xl space-y-6">
                <div>
                    <h2 class="text-xl font-bold text-white tracking-tight">Ready to connect?</h2>
                    <p class="text-xs text-slate-400 mt-1">Configure your name and join or create a conference room.</p>
                </div>

                <div class="space-y-4">
                    <!-- User Name Field -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5" for="lobby-user-name">
                            Your Display Name <span class="text-brand-400">*</span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                            <input type="text" id="lobby-user-name" placeholder="e.g., Nouman Zaib" maxlength="30"
                                class="w-full bg-surface-base border border-surface-border rounded-xl pl-9 pr-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition font-medium">
                        </div>
                    </div>

                    <!-- Room ID / Code Field -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5" for="lobby-room-id">
                            Room Name or ID <span class="text-brand-400">*</span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-hashtag absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                            <input type="text" id="lobby-room-id" placeholder="e.g., team-weekly or daily-sync" maxlength="40"
                                class="w-full bg-surface-base border border-surface-border rounded-xl pl-9 pr-24 py-2.5 text-sm text-brand-300 font-mono placeholder-slate-500 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition">
                            <button onclick="generateRandomRoomId()" type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-brand-400 hover:text-brand-300 bg-brand-500/10 hover:bg-brand-500/20 border border-brand-500/20 px-2.5 py-1 rounded-lg transition active:scale-95">
                                <i class="fa-solid fa-dice mr-1"></i>Random
                            </button>
                        </div>
                    </div>

                    <!-- Join Actions -->
                    <div class="pt-2 space-y-3">
                        <button onclick="startMeetingFromLobby()" id="join-room-btn"
                            class="w-full py-3.5 px-5 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 hover:from-brand-500 hover:to-violet-500 text-white font-bold text-sm shadow-xl shadow-brand-600/25 transition active:scale-[0.98] flex items-center justify-center space-x-2">
                            <i class="fa-solid fa-door-open text-base"></i>
                            <span id="join-btn-label">Join Meeting Room</span>
                        </button>

                        <div class="relative flex py-1 items-center">
                            <div class="flex-grow border-t border-surface-border"></div>
                            <span class="flex-shrink mx-3 text-[11px] text-slate-500 uppercase tracking-widest font-semibold">Or</span>
                            <div class="flex-grow border-t border-surface-border"></div>
                        </div>

                        <button onclick="createInstantMeeting()"
                            class="w-full py-3 px-4 rounded-xl bg-surface-tile hover:bg-surface-highlight border border-surface-border text-slate-200 font-semibold text-xs transition active:scale-[0.98] flex items-center justify-center space-x-2">
                            <i class="fa-solid fa-plus text-brand-400"></i>
                            <span>Create Instant New Room</span>
                        </button>
                    </div>
                </div>

                <!-- Active Room Invitation Banner (if ?room= query param exists) -->
                <div id="lobby-invite-alert" class="hidden p-3 rounded-xl bg-brand-950/60 border border-brand-500/30 text-xs text-brand-200 space-y-1">
                    <p class="font-bold flex items-center gap-1.5 text-brand-300">
                        <i class="fa-solid fa-envelope-open-text"></i>
                        <span>You were invited to a room!</span>
                    </p>
                    <p class="text-slate-300">Click <strong>Join Meeting Room</strong> above to connect directly with participants.</p>
                </div>

            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- SCREEN 2: ACTIVE MEETING ROOM                             -->
    <!-- ======================================================== -->
    <div id="meeting-screen" class="hidden flex-1 flex flex-col justify-between h-screen overflow-hidden">
        
        <!-- Top Meeting Header Bar -->
        <header class="bg-surface-card/90 backdrop-blur-md border-b border-surface-border px-4 sm:px-6 py-3 flex items-center justify-between z-30 shadow-md">
            <!-- Left: Brand & Room ID Badge -->
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="flex items-center space-x-2 cursor-pointer" onclick="confirmLeaveRoom()">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-brand-600 to-violet-500 flex items-center justify-center text-white text-xs font-bold shadow-md shadow-brand-500/20">
                        <i class="fa-solid fa-video"></i>
                    </div>
                    <span class="font-bold text-white text-sm sm:text-base hidden sm:inline">Nexus<span class="text-brand-400">Call</span></span>
                </div>

                <div class="h-5 w-px bg-surface-border hidden sm:block"></div>

                <!-- Room ID Pill with Fast Copy -->
                <div class="flex items-center bg-surface-base/90 border border-surface-border rounded-xl p-1 pr-2.5 space-x-2 shadow-inner">
                    <div class="px-2 py-0.5 rounded-lg bg-brand-500/20 text-brand-300 text-[11px] font-mono font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-hashtag text-[10px]"></i>
                        <span id="meeting-room-name-display">room</span>
                    </div>
                    <button onclick="copyInviteLink()" class="text-slate-400 hover:text-white text-xs flex items-center gap-1 transition" title="Copy Shareable Invite Link">
                        <i class="fa-solid fa-link text-brand-400"></i>
                        <span class="text-[11px] font-medium hidden md:inline">Copy Link</span>
                    </button>
                </div>
            </div>

            <!-- Center: Meeting Duration & Status -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <div class="px-3 py-1 bg-surface-base border border-surface-border rounded-full text-xs font-mono text-slate-300 flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span id="call-timer">00:00</span>
                </div>
            </div>

            <!-- Right: Participants Drawer & Fast Invite -->
            <div class="flex items-center space-x-2">
                <button onclick="copyInviteLink()" class="px-3 py-1.5 rounded-xl bg-brand-600/20 hover:bg-brand-600/30 text-brand-300 border border-brand-500/30 text-xs font-medium transition flex items-center space-x-1.5 active:scale-95 shadow-sm">
                    <i class="fa-solid fa-user-plus text-xs"></i>
                    <span class="hidden sm:inline">Invite</span>
                </button>

                <button onclick="toggleParticipantsDrawer()" class="px-3 py-1.5 rounded-xl bg-surface-tile hover:bg-surface-highlight text-slate-200 border border-surface-border text-xs font-medium transition flex items-center space-x-2 active:scale-95">
                    <i class="fa-solid fa-users text-xs text-brand-400"></i>
                    <span id="participant-count-badge">1</span>
                </button>
            </div>
        </header>

        <!-- Main Video Conference Grid -->
        <main class="flex-1 p-3 sm:p-5 overflow-y-auto flex items-center justify-center">
            <!-- Autoplay Click-to-Play Overlay (if browser blocks remote audio) -->
            <div id="autoplay-play-btn" class="fixed inset-0 z-40 bg-surface-base/80 backdrop-blur-md flex flex-col items-center justify-center text-white space-y-4 hidden p-4">
                <div class="w-16 h-16 rounded-2xl bg-brand-600 flex items-center justify-center text-2xl shadow-xl shadow-brand-500/30 animate-bounce">
                    <i class="fa-solid fa-volume-high"></i>
                </div>
                <div class="text-center max-w-sm">
                    <h3 class="text-lg font-bold">Unmute Audio & Video</h3>
                    <p class="text-xs text-slate-400 mt-1">Your browser blocked audio autoplay. Click below to listen to participants.</p>
                </div>
                <button onclick="enableRemoteAudioVideo()" class="bg-gradient-to-r from-brand-600 to-violet-600 hover:from-brand-500 hover:to-violet-500 text-white px-6 py-2.5 rounded-xl font-bold text-sm shadow-xl transition active:scale-95">
                    Click to Unmute Room
                </button>
            </div>

            <!-- Dynamic Responsive Video Grid -->
            <div id="video-grid" class="w-full max-w-7xl h-full flex flex-wrap items-center justify-center gap-4 transition-all duration-300">
                <!-- Local Tile (Always exists in room) -->
                <div id="local-participant-tile" class="participant-tile relative bg-surface-card rounded-2xl overflow-hidden shadow-2xl border border-surface-border/80 flex items-center justify-center group transition-all duration-300 w-full max-w-xl aspect-video">
                    <video id="meeting-local-video" autoplay muted playsinline class="w-full h-full object-cover"></video>
                    
                    <!-- Camera Off Avatar -->
                    <div id="meeting-local-cam-off" class="absolute inset-0 bg-surface-card flex flex-col items-center justify-center text-slate-400 space-y-2 hidden">
                        <div id="local-avatar-initials" class="w-20 h-20 rounded-full bg-gradient-to-tr from-brand-700 to-violet-600 border border-brand-400/30 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                            U
                        </div>
                        <p class="text-xs text-slate-400 font-medium">Camera is Off</p>
                    </div>

                    <!-- Bottom Info Badge -->
                    <div class="absolute bottom-3 left-3 bg-surface-base/80 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs font-semibold border border-surface-border flex items-center space-x-2 shadow-md">
                        <span id="local-mic-badge" class="text-emerald-400 text-xs">
                            <i class="fa-solid fa-microphone"></i>
                        </span>
                        <span id="local-display-name-label" class="text-slate-200">You</span>
                        <span class="text-[10px] text-brand-300 bg-brand-500/20 px-1.5 py-0.5 rounded font-bold">Host</span>
                    </div>

                    <!-- Top Right Flip / Mirror Toggle Button -->
                    <div class="absolute top-3 right-3 flex items-center space-x-1.5 z-10">
                        <button onclick="toggleMirrorLocalVideo()" class="bg-surface-base/80 hover:bg-surface-base text-slate-300 hover:text-white border border-surface-border px-2.5 py-1 rounded-xl text-xs font-medium transition flex items-center gap-1.5 backdrop-blur-md shadow-md active:scale-95" title="Flip / Mirror Video (Seedha / Ulta toggle)">
                            <i class="fa-solid fa-arrows-left-right text-brand-400 text-xs"></i>
                            <span class="text-[11px] hidden sm:inline">Flip View</span>
                        </button>
                    </div>
                </div>

                <!-- Remote video tiles will be appended dynamically here by JavaScript -->
            </div>
        </main>

        <!-- Floating Ergonomic Control Dock -->
        <footer class="p-4 sm:p-5 flex items-center justify-center z-30">
            <div class="bg-surface-card/95 backdrop-blur-xl border border-surface-border/90 px-4 sm:px-6 py-3 rounded-2xl sm:rounded-3xl shadow-dock flex items-center space-x-2 sm:space-x-4">
                
                <!-- Mic Toggle -->
                <button onclick="toggleAudio()" id="mic-btn" class="w-12 h-12 rounded-2xl bg-surface-tile hover:bg-surface-highlight text-white flex items-center justify-center transition shadow-md border border-surface-border active:scale-95 group relative" title="Toggle Microphone (M)">
                    <i class="fa-solid fa-microphone text-base" id="mic-icon"></i>
                    <span class="absolute -top-8 bg-surface-base text-[10px] text-slate-300 px-2 py-0.5 rounded border border-surface-border opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap">Mute / Unmute</span>
                </button>

                <!-- Camera Toggle -->
                <button onclick="toggleVideo()" id="video-btn" class="w-12 h-12 rounded-2xl bg-surface-tile hover:bg-surface-highlight text-white flex items-center justify-center transition shadow-md border border-surface-border active:scale-95 group relative" title="Toggle Camera (V)">
                    <i class="fa-solid fa-video text-base" id="video-icon"></i>
                    <span class="absolute -top-8 bg-surface-base text-[10px] text-slate-300 px-2 py-0.5 rounded border border-surface-border opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap">Camera On/Off</span>
                </button>

                <!-- Screen Sharing -->
                <button onclick="toggleScreenShare()" id="screen-btn" class="w-12 h-12 rounded-2xl bg-surface-tile hover:bg-surface-highlight text-white flex items-center justify-center transition shadow-md border border-surface-border active:scale-95 group relative" title="Share Screen (S)">
                    <i class="fa-solid fa-desktop text-base" id="screen-icon"></i>
                    <span class="absolute -top-8 bg-surface-base text-[10px] text-slate-300 px-2 py-0.5 rounded border border-surface-border opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap">Share Screen</span>
                </button>

                <div class="h-6 w-px bg-surface-border mx-1"></div>

                <!-- Fast Copy Invite Link -->
                <button onclick="copyInviteLink()" class="w-12 h-12 rounded-2xl bg-brand-600/20 hover:bg-brand-600/30 text-brand-300 flex items-center justify-center transition shadow-md border border-brand-500/30 active:scale-95 group relative" title="Copy Invite Link">
                    <i class="fa-solid fa-share-nodes text-base"></i>
                    <span class="absolute -top-8 bg-surface-base text-[10px] text-slate-300 px-2 py-0.5 rounded border border-surface-border opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap">Share Link</span>
                </button>

                <!-- Participants Drawer Toggle -->
                <button onclick="toggleParticipantsDrawer()" class="w-12 h-12 rounded-2xl bg-surface-tile hover:bg-surface-highlight text-slate-200 flex items-center justify-center transition shadow-md border border-surface-border active:scale-95 group relative" title="View Participants">
                    <i class="fa-solid fa-user-group text-base text-slate-300"></i>
                    <span class="absolute -top-8 bg-surface-base text-[10px] text-slate-300 px-2 py-0.5 rounded border border-surface-border opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap">Participants</span>
                </button>

                <div class="h-6 w-px bg-surface-border mx-1"></div>

                <!-- Leave Meeting (Hang up) -->
                <button onclick="confirmLeaveRoom()" class="px-5 h-12 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs sm:text-sm flex items-center space-x-2 transition shadow-lg shadow-rose-600/25 active:scale-95" title="Leave Meeting">
                    <i class="fa-solid fa-phone-slash"></i>
                    <span class="hidden sm:inline">Leave</span>
                </button>

            </div>
        </footer>

    </div>

    <!-- ======================================================== -->
    <!-- SLIDE-OVER PARTICIPANTS DRAWER                            -->
    <!-- ======================================================== -->
    <div id="participants-drawer" class="fixed inset-y-0 right-0 z-50 w-80 max-w-full bg-surface-card border-l border-surface-border shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300">
        <div class="p-4 border-b border-surface-border flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-users text-brand-400"></i>
                <h3 class="text-sm font-bold text-white">Participants (<span id="drawer-count">1</span>)</h3>
            </div>
            <button onclick="toggleParticipantsDrawer()" class="text-slate-400 hover:text-white p-1 rounded-lg transition">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <div class="p-3 border-b border-surface-border/50 bg-surface-base/50">
            <button onclick="copyInviteLink()" class="w-full py-2 px-3 rounded-xl bg-brand-600/20 hover:bg-brand-600/30 text-brand-300 border border-brand-500/30 text-xs font-semibold transition flex items-center justify-center space-x-2 active:scale-95">
                <i class="fa-solid fa-link text-xs"></i>
                <span>Copy Shareable Link</span>
            </button>
        </div>

        <div id="participants-list" class="flex-1 p-3 overflow-y-auto space-y-2">
            <!-- Dynamically populated participant cards -->
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL: HTTP LAN IP CAMERA PERMISSION GUIDE                 -->
    <!-- ======================================================== -->
    <div id="http-help-modal" class="fixed inset-0 z-50 bg-surface-base/80 backdrop-blur-md flex items-center justify-center hidden p-4">
        <div class="bg-surface-card border border-surface-border p-6 rounded-3xl shadow-2xl max-w-lg w-full space-y-4">
            <div class="flex justify-between items-center border-b border-surface-border pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-amber-400"></i>
                    Allow Camera on Local Network (HTTP)
                </h3>
                <button onclick="hideHttpHelpModal()" class="text-slate-400 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <div class="text-xs text-slate-300 space-y-3 leading-relaxed">
                <p class="text-slate-400">Chrome and Edge block camera & mic on non-localhost HTTP addresses (<span class="font-mono text-brand-400 font-semibold" id="help-origin-ip"></span>). Follow these 3 easy steps:</p>

                <div class="bg-surface-base p-3.5 rounded-2xl border border-surface-border space-y-2">
                    <p class="font-semibold text-amber-400 flex items-center gap-1.5">
                        <span class="bg-amber-500/20 text-amber-300 w-5 h-5 rounded-full flex items-center justify-center text-xs">1</span>
                        Copy Flag Address
                    </p>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="chrome://flags/#unsafely-treat-insecure-origin-as-secure" class="bg-surface-tile border border-surface-border px-2.5 py-1.5 rounded-lg text-brand-300 font-mono w-full select-all text-xs outline-none">
                        <button onclick="copyFlagsUrl()" class="bg-brand-600/30 text-brand-300 border border-brand-500/30 px-3 py-1.5 rounded-lg hover:bg-brand-600/50 transition font-medium flex-shrink-0">Copy</button>
                    </div>
                </div>

                <div class="bg-surface-base p-3.5 rounded-2xl border border-surface-border space-y-1.5">
                    <p class="font-semibold text-amber-400 flex items-center gap-1.5">
                        <span class="bg-amber-500/20 text-amber-300 w-5 h-5 rounded-full flex items-center justify-center text-xs">2</span>
                        Enter Server URL & Enable
                    </p>
                    <p>Paste the link into a new tab. In <em>"Insecure origins treated as secure"</em>, enter:</p>
                    <p class="font-mono text-brand-400 font-bold bg-surface-tile p-2 rounded-lg border border-surface-border text-center select-all" id="help-origin-url"></p>
                    <p class="text-slate-400">Change setting from <span class="text-rose-400 font-medium">Disabled</span> to <span class="text-emerald-400 font-bold">Enabled</span>.</p>
                </div>

                <div class="bg-surface-base p-3.5 rounded-2xl border border-surface-border space-y-1">
                    <p class="font-semibold text-amber-400 flex items-center gap-1.5">
                        <span class="bg-amber-500/20 text-amber-300 w-5 h-5 rounded-full flex items-center justify-center text-xs">3</span>
                        Relaunch Browser
                    </p>
                    <p class="text-slate-400">Click <strong>Relaunch</strong> button. Reload NexusCall and camera will work instantly!</p>
                </div>
            </div>
            <div class="pt-2 flex justify-end">
                <button onclick="hideHttpHelpModal()" class="px-5 py-2 bg-brand-600 hover:bg-brand-500 text-white font-medium rounded-xl text-xs transition shadow-lg">Got it, Close</button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- JAVASCRIPT: APPLICATION CONTROLLER & WEBRTC ENGINE        -->
    <!-- ======================================================== -->
    <script>
        // State Variables
        let myStream = null;
        let peer = null;
        let currentRoomId = '';
        let myPeerId = '';
        let myUserName = '';
        let isAudioMuted = false;
        let isVideoMuted = false;
        let isScreenSharing = false;
        let isLocalMirrored = false; // Default: unmirrored (seedha / normal view so text on camera & canvas is normal)
        let heartbeatTimer = null;
        let callDurationTimer = null;
        let callSeconds = 0;
        let isUsingFallbackStream = false;

        // Map: peerId -> { call, stream, userName }
        const activePeers = new Map();

        // DOM Element References
        const lobbyScreen = document.getElementById('lobby-screen');
        const meetingScreen = document.getElementById('meeting-screen');
        const lobbyPreviewVideo = document.getElementById('lobby-preview-video');
        const lobbyCamOffPlaceholder = document.getElementById('lobby-cam-off-placeholder');
        const lobbyUserNameInput = document.getElementById('lobby-user-name');
        const lobbyRoomIdInput = document.getElementById('lobby-room-id');
        const meetingLocalVideo = document.getElementById('meeting-local-video');
        const meetingLocalCamOff = document.getElementById('meeting-local-cam-off');
        const localDisplayNameLabel = document.getElementById('local-display-name-label');
        const localAvatarInitials = document.getElementById('local-avatar-initials');
        const videoGrid = document.getElementById('video-grid');
        const callTimerEl = document.getElementById('call-timer');
        const participantCountBadge = document.getElementById('participant-count-badge');
        const drawerCount = document.getElementById('drawer-count');
        const participantsDrawer = document.getElementById('participants-drawer');
        const participantsList = document.getElementById('participants-list');
        const meetingRoomNameDisplay = document.getElementById('meeting-room-name-display');
        const autoplayPlayBtn = document.getElementById('autoplay-play-btn');

        // ==========================================
        // HELPER: GET USER INITIALS (Zoom / Google Meet format)
        // e.g., "danger" -> "D", "Nouman Zaib" -> "NZ"
        // ==========================================
        function getInitials(name) {
            if (!name) return 'U';
            const clean = name.trim();
            const parts = clean.split(/\s+/);
            if (parts.length === 1) {
                return parts[0].charAt(0).toUpperCase();
            }
            return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
        }

        // ==========================================
        // 1. NOTIFICATIONS (TOASTS)
        // ==========================================
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `pointer-events-auto px-4 py-3 rounded-2xl text-xs font-medium shadow-2xl border flex items-center space-x-2.5 transition-all duration-300 transform translate-y-2 opacity-0 ${
                type === 'error' ? 'bg-rose-950/90 text-rose-300 border-rose-800/80' :
                type === 'success' ? 'bg-emerald-950/90 text-emerald-300 border-emerald-800/80' :
                'bg-surface-card/95 text-brand-300 border-surface-border'
            }`;

            const icon = type === 'error' ? 'fa-circle-exclamation text-rose-400' :
                         type === 'success' ? 'fa-circle-check text-emerald-400' :
                         'fa-circle-info text-brand-400';
            toast.innerHTML = `<i class="fa-solid ${icon} text-sm flex-shrink-0"></i><span>${message}</span>`;

            container.appendChild(toast);
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('opacity-0', '-translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // ==========================================
        // 2. HTTP SECURITY CONTEXT HELPER
        // ==========================================
        function checkHttpSecureContext() {
            const isLocalhost = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            const isHttps = location.protocol === 'https:';
            const originUrl = `${location.protocol}//${location.host}`;

            document.getElementById('help-origin-url').textContent = originUrl;
            document.getElementById('help-origin-ip').textContent = location.host;

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
                showToast("Chrome flag link copied! Paste into a new tab.", "success");
            }).catch(() => {
                showToast("URL: chrome://flags/#unsafely-treat-insecure-origin-as-secure", "info");
            });
        }

        // ==========================================
        // 3. SYNTHETIC MEDIA FALLBACK GENERATOR
        // ==========================================
        function createFallbackStream(label = "Camera Blocked / Unavailable") {
            isUsingFallbackStream = true;
            const canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 480;
            const ctx = canvas.getContext('2d');

            function draw() {
                const grad = ctx.createLinearGradient(0, 0, 640, 480);
                grad.addColorStop(0, '#070a13');
                grad.addColorStop(1, '#1e1b4b');
                ctx.fillStyle = grad;
                ctx.fillRect(0, 0, 640, 480);

                const time = Date.now() * 0.003;
                const pulse = Math.sin(time) * 4 + 65;

                ctx.fillStyle = 'rgba(99, 102, 241, 0.15)';
                ctx.beginPath();
                ctx.arc(320, 200, pulse + 20, 0, Math.PI * 2);
                ctx.fill();

                ctx.fillStyle = '#312e81';
                ctx.beginPath();
                ctx.arc(320, 200, pulse, 0, Math.PI * 2);
                ctx.fill();

                // Head & body
                ctx.fillStyle = '#a5b4fc';
                ctx.beginPath();
                ctx.arc(320, 180, 25, 0, Math.PI * 2);
                ctx.fill();

                ctx.beginPath();
                ctx.arc(320, 250, 45, Math.PI, 0);
                ctx.fill();

                ctx.fillStyle = '#f8fafc';
                ctx.font = 'bold 18px system-ui, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(label, 320, 310);

                ctx.fillStyle = '#818cf8';
                ctx.font = '12px system-ui, sans-serif';
                ctx.fillText('P2P Fallback Stream Active', 320, 335);

                requestAnimationFrame(draw);
            }
            draw();

            const stream = canvas.captureStream(30);

            // Add silent audio track for full SDP media negotiation
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = audioCtx.createOscillator();
                const dst = audioCtx.createMediaStreamDestination();
                const gain = audioCtx.createGain();
                gain.gain.value = 0;
                osc.connect(gain);
                gain.connect(dst);
                osc.start();
                dst.stream.getAudioTracks().forEach(t => stream.addTrack(t));
            } catch (e) {
                console.warn("Audio Context fallback error:", e);
            }

            return stream;
        }

        // ==========================================
        // 4. MIRROR / FLIP VIDEO CONTROLLER
        // (Allows user to toggle between normal/seedha view and mirror view)
        // ==========================================
        function toggleMirrorLocalVideo() {
            isLocalMirrored = !isLocalMirrored;
            applyMirrorState();
            showToast(isLocalMirrored ? "Mirror mode ON (Mirrored view)" : "Mirror mode OFF (Normal / Seedha view)", "info");
        }

        function applyMirrorState() {
            // If using fallback canvas stream or screen sharing, NEVER mirror so text is not reversed
            if (isUsingFallbackStream || isScreenSharing) {
                meetingLocalVideo.classList.remove('mirror-video');
                lobbyPreviewVideo.classList.remove('mirror-video');
                return;
            }

            if (isLocalMirrored) {
                meetingLocalVideo.classList.add('mirror-video');
                lobbyPreviewVideo.classList.add('mirror-video');
            } else {
                meetingLocalVideo.classList.remove('mirror-video');
                lobbyPreviewVideo.classList.remove('mirror-video');
            }
        }

        // ==========================================
        // 5. LOCAL MEDIA INITIALIZATION
        // ==========================================
        async function initializeLocalMedia() {
            checkHttpSecureContext();

            if (myStream) {
                myStream.getTracks().forEach(t => t.stop());
                myStream = null;
            }

            const statusEl = document.getElementById('lobby-media-status');

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                console.warn("getUserMedia unavailable (HTTP insecure context or unsupported)");
                statusEl.textContent = "Fallback Active (HTTP)";
                statusEl.className = "text-amber-400";
                myStream = createFallbackStream("Camera Restricted (HTTP)");
                lobbyPreviewVideo.srcObject = myStream;
                applyMirrorState();
                return;
            }

            try {
                myStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: true
                });
                isUsingFallbackStream = false;
                lobbyPreviewVideo.srcObject = myStream;
                statusEl.textContent = "Cam & Mic Ready";
                statusEl.className = "text-emerald-400";
                applyMirrorState();
            } catch (err) {
                console.warn("Camera/Mic access error:", err);
                let reason = "Camera Unavailable";
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    reason = "Camera Permission Denied";
                } else if (err.name === 'NotFoundError') {
                    reason = "No Camera/Mic Found";
                }

                statusEl.textContent = reason;
                statusEl.className = "text-rose-400";
                myStream = createFallbackStream(reason);
                lobbyPreviewVideo.srcObject = myStream;
                applyMirrorState();
            }
        }

        function reinitMedia() {
            initializeLocalMedia();
            showToast("Camera access refreshed", "info");
        }

        // ==========================================
        // 6. LOBBY CONTROLS & ROOM GENERATION
        // ==========================================
        function toggleLobbyAudio() {
            if (!myStream || !myStream.getAudioTracks().length) return;
            const track = myStream.getAudioTracks()[0];
            track.enabled = !track.enabled;
            isAudioMuted = !track.enabled;

            const btn = document.getElementById('lobby-mic-btn');
            const icon = document.getElementById('lobby-mic-icon');
            if (track.enabled) {
                btn.className = "w-11 h-11 rounded-xl bg-surface-base/80 hover:bg-surface-base text-white border border-surface-border/80 backdrop-blur-md flex items-center justify-center transition shadow-lg active:scale-95";
                icon.className = "fa-solid fa-microphone text-sm";
            } else {
                btn.className = "w-11 h-11 rounded-xl bg-rose-600/30 text-rose-400 border border-rose-500/40 backdrop-blur-md flex items-center justify-center transition shadow-lg active:scale-95";
                icon.className = "fa-solid fa-microphone-slash text-sm";
            }
        }

        function toggleLobbyVideo() {
            if (!myStream || !myStream.getVideoTracks().length) return;
            const track = myStream.getVideoTracks()[0];
            track.enabled = !track.enabled;
            isVideoMuted = !track.enabled;

            const btn = document.getElementById('lobby-video-btn');
            const icon = document.getElementById('lobby-video-icon');
            if (track.enabled) {
                btn.className = "w-11 h-11 rounded-xl bg-surface-base/80 hover:bg-surface-base text-white border border-surface-border/80 backdrop-blur-md flex items-center justify-center transition shadow-lg active:scale-95";
                icon.className = "fa-solid fa-video text-sm";
                lobbyCamOffPlaceholder.classList.add('hidden');
            } else {
                btn.className = "w-11 h-11 rounded-xl bg-rose-600/30 text-rose-400 border border-rose-500/40 backdrop-blur-md flex items-center justify-center transition shadow-lg active:scale-95";
                icon.className = "fa-solid fa-video-slash text-sm";
                lobbyCamOffPlaceholder.classList.remove('hidden');
            }
        }

        function generateRandomRoomId() {
            const adjectives = ['swift', 'bright', 'cozy', 'vibrant', 'quiet', 'stellar', 'epic', 'nexus', 'prime'];
            const nouns = ['meet', 'talk', 'sync', 'chat', 'room', 'space', 'hub', 'call', 'lounge'];
            const num = Math.floor(100 + Math.random() * 900);
            const adj = adjectives[Math.floor(Math.random() * adjectives.length)];
            const noun = nouns[Math.floor(Math.random() * nouns.length)];
            const code = `${adj}-${noun}-${num}`;
            lobbyRoomIdInput.value = code;
            return code;
        }

        function createInstantMeeting() {
            const randomCode = generateRandomRoomId();
            startMeetingFromLobby(randomCode);
        }

        // ==========================================
        // 7. ENTERING MEETING & WEBRTC MESH
        // ==========================================
        async function startMeetingFromLobby(overrideRoomId = null) {
            const name = lobbyUserNameInput.value.trim();
            const room = (overrideRoomId || lobbyRoomIdInput.value.trim()).replace(/[^a-zA-Z0-9_-]/g, '');

            if (!name) {
                lobbyUserNameInput.focus();
                return showToast("Please enter your display name!", "error");
            }

            if (!room) {
                lobbyRoomIdInput.focus();
                return showToast("Please enter or generate a Room ID!", "error");
            }

            myUserName = name;
            currentRoomId = room;
            localStorage.setItem('nexuscall_user_name', myUserName);

            // Update URL query parameter without full reload
            const newUrl = `${window.location.origin}${window.location.pathname}?room=${currentRoomId}`;
            window.history.pushState({ path: newUrl }, '', newUrl);

            // Switch to Meeting UI
            lobbyScreen.classList.add('hidden');
            meetingScreen.classList.remove('hidden');

            meetingRoomNameDisplay.textContent = currentRoomId;
            localDisplayNameLabel.textContent = `${myUserName} (You)`;
            localAvatarInitials.textContent = getInitials(myUserName);

            // Transfer media stream to meeting local video
            meetingLocalVideo.srcObject = myStream;
            applyMirrorState();

            if (isVideoMuted) {
                meetingLocalCamOff.classList.remove('hidden');
            }

            // Sync meeting control buttons with lobby state
            syncControlButtons();

            // Start call duration timer
            startCallTimer();

            // Initialize PeerJS and join room mesh
            await initializePeerAndJoinRoom();
        }

        // ==========================================
        // 8. PEERJS INITIALIZATION & SIGNALING
        // ==========================================
        async function initializePeerAndJoinRoom() {
            showToast("Connecting to signaling network...", "info");

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

            peer.on('open', async (id) => {
                myPeerId = id;
                console.log("Connected to PeerServer with ID:", myPeerId);

                // Register with backend room API
                try {
                    const response = await fetch('room.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'join',
                            room_id: currentRoomId,
                            peer_id: myPeerId,
                            user_name: myUserName
                        })
                    });
                    const data = await response.json();

                    if (data.status === 'success') {
                        showToast(`Connected to Room: ${currentRoomId}`, "success");
                        updateParticipantsUI(data.peers || []);

                        // Call all existing peers in the room
                        const existingPeers = data.peers || [];
                        existingPeers.forEach(remoteUser => {
                            callRemotePeer(remoteUser.peer_id, remoteUser.user_name);
                        });

                        // Start periodic heartbeat
                        startHeartbeat();
                    } else {
                        showToast(data.message || "Failed to register with room server.", "error");
                    }
                } catch (e) {
                    console.error("Room join error:", e);
                    showToast("Could not contact room backend. Check server connection.", "error");
                }
            });

            // Handle incoming WebRTC calls from newcomers
            peer.on('call', (incomingCall) => {
                const callerName = incomingCall.metadata?.name || 'Guest';
                console.log("Incoming call from:", incomingCall.peer, callerName);

                const streamToSend = myStream || createFallbackStream("Fallback Media");
                incomingCall.answer(streamToSend);
                setupCallEvents(incomingCall, callerName);
            });

            peer.on('error', (err) => {
                console.error("PeerJS error:", err);
                let message = "WebRTC peer connection error.";
                if (err.type === 'peer-unavailable') {
                    message = "Participant disconnected.";
                } else if (err.type === 'network') {
                    message = "Network connection lost. Reconnecting...";
                }
                showToast(message, "error");
            });

            peer.on('disconnected', () => {
                if (peer && !peer.destroyed) {
                    peer.reconnect();
                }
            });
        }

        // ==========================================
        // 9. CALLING & STREAM MANAGEMENT
        // ==========================================
        function callRemotePeer(remotePeerId, remoteUserName) {
            if (activePeers.has(remotePeerId)) return; // Already connected

            console.log("Initiating call to peer:", remotePeerId, remoteUserName);
            const streamToSend = myStream || createFallbackStream("Fallback Media");

            const call = peer.call(remotePeerId, streamToSend, {
                metadata: { name: myUserName, peerId: myPeerId }
            });

            if (call) {
                setupCallEvents(call, remoteUserName);
            }
        }

        function setupCallEvents(call, remoteUserName) {
            const remotePeerId = call.peer;

            call.on('stream', (remoteStream) => {
                console.log("Received stream from:", remotePeerId, remoteUserName);
                activePeers.set(remotePeerId, { call, stream: remoteStream, userName: remoteUserName });
                addOrUpdateRemoteVideoTile(remotePeerId, remoteUserName, remoteStream);
                updateParticipantBadges();
            });

            call.on('close', () => {
                console.log("Call closed by:", remotePeerId);
                removeRemoteVideoTile(remotePeerId);
            });

            call.on('error', (err) => {
                console.error("Call error from:", remotePeerId, err);
                removeRemoteVideoTile(remotePeerId);
            });
        }

        function addOrUpdateRemoteVideoTile(remotePeerId, remoteUserName, stream) {
            let tile = document.getElementById(`tile-${remotePeerId}`);
            if (!tile) {
                tile = document.createElement('div');
                tile.id = `tile-${remotePeerId}`;
                tile.className = "participant-tile relative bg-surface-card rounded-2xl overflow-hidden shadow-2xl border border-surface-border/80 flex items-center justify-center group transition-all duration-300 w-full max-w-xl aspect-video";

                const initials = getInitials(remoteUserName);

                tile.innerHTML = `
                    <video id="video-${remotePeerId}" autoplay playsinline class="w-full h-full object-cover"></video>
                    
                    <div id="cam-off-${remotePeerId}" class="absolute inset-0 bg-surface-card flex flex-col items-center justify-center text-slate-400 space-y-2 hidden">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-violet-700 to-indigo-600 border border-violet-400/30 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                            ${initials}
                        </div>
                        <p class="text-xs text-slate-400 font-medium">${remoteUserName}</p>
                    </div>

                    <div class="absolute bottom-3 left-3 bg-surface-base/80 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs font-semibold border border-surface-border flex items-center space-x-2 shadow-md">
                        <span class="text-emerald-400 text-xs">
                            <i class="fa-solid fa-microphone"></i>
                        </span>
                        <span class="text-slate-200">${remoteUserName}</span>
                    </div>
                `;

                videoGrid.appendChild(tile);
                showToast(`${remoteUserName} joined the meeting`, "info");
            }

            const video = tile.querySelector('video');
            video.srcObject = stream;

            const playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.catch(err => {
                    console.warn("Autoplay restriction encountered:", err);
                    autoplayPlayBtn.classList.remove('hidden');
                });
            }

            adjustVideoGridLayout();
        }

        function removeRemoteVideoTile(remotePeerId) {
            const peerData = activePeers.get(remotePeerId);
            const userName = peerData ? peerData.userName : 'Participant';

            activePeers.delete(remotePeerId);

            const tile = document.getElementById(`tile-${remotePeerId}`);
            if (tile) {
                tile.remove();
                showToast(`${userName} left the meeting`, "info");
            }

            updateParticipantBadges();
            adjustVideoGridLayout();
        }

        function enableRemoteAudioVideo() {
            autoplayPlayBtn.classList.add('hidden');
            activePeers.forEach(({ stream }, peerId) => {
                const video = document.getElementById(`video-${peerId}`);
                if (video) video.play();
            });
        }

        // ==========================================
        // 10. DYNAMIC RESPONSIVE GRID LAYOUT
        // ==========================================
        function adjustVideoGridLayout() {
            const totalCount = activePeers.size + 1; // local + remotes
            const tiles = document.querySelectorAll('.participant-tile');

            tiles.forEach(tile => {
                tile.classList.remove('max-w-xl', 'max-w-lg', 'max-w-md', 'max-w-sm', 'w-full', 'w-[48%]', 'w-[31%]', 'w-[23%]');

                if (totalCount === 1) {
                    tile.classList.add('w-full', 'max-w-2xl');
                } else if (totalCount === 2) {
                    tile.classList.add('w-full', 'sm:w-[48%]', 'max-w-xl');
                } else if (totalCount <= 4) {
                    tile.classList.add('w-full', 'sm:w-[48%]', 'max-w-lg');
                } else if (totalCount <= 6) {
                    tile.classList.add('w-full', 'sm:w-[48%]', 'md:w-[31%]', 'max-w-md');
                } else {
                    tile.classList.add('w-full', 'sm:w-[48%]', 'md:w-[31%]', 'lg:w-[23%]', 'max-w-sm');
                }
            });
        }

        // ==========================================
        // 11. HEARTBEAT & PEER DISCOVERY
        // ==========================================
        function startHeartbeat() {
            if (heartbeatTimer) clearInterval(heartbeatTimer);

            heartbeatTimer = setInterval(async () => {
                if (!currentRoomId || !myPeerId) return;

                try {
                    const response = await fetch('room.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'heartbeat',
                            room_id: currentRoomId,
                            peer_id: myPeerId
                        })
                    });
                    const data = await response.json();

                    if (data.status === 'success') {
                        const serverPeers = data.peers || [];
                        updateParticipantsUI(serverPeers);

                        // Discover new peers that we haven't connected to yet
                        serverPeers.forEach(sp => {
                            if (!activePeers.has(sp.peer_id) && myPeerId > sp.peer_id) {
                                callRemotePeer(sp.peer_id, sp.user_name);
                            }
                        });

                        // Prune peers who dropped off the server registry
                        const activeServerPeerIds = new Set(serverPeers.map(p => p.peer_id));
                        activePeers.forEach((data, peerId) => {
                            if (!activeServerPeerIds.has(peerId)) {
                                removeRemoteVideoTile(peerId);
                            }
                        });
                    }
                } catch (e) {
                    console.warn("Heartbeat error:", e);
                }
            }, 4000);
        }

        // ==========================================
        // 12. PARTICIPANTS DRAWER & COUNTERS
        // ==========================================
        function updateParticipantsUI(serverPeers) {
            const total = serverPeers.length + 1;
            participantCountBadge.textContent = total;
            drawerCount.textContent = total;

            const myInitials = getInitials(myUserName);

            let html = `
                <!-- You (Local) -->
                <div class="p-3 bg-surface-base rounded-2xl border border-surface-border flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-xs">
                            ${myInitials}
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-white">${myUserName} <span class="text-brand-400 font-normal">(You)</span></p>
                            <span class="text-[10px] text-emerald-400 font-medium">Host / Connected</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 text-xs text-slate-400">
                        <i class="fa-solid ${isAudioMuted ? 'fa-microphone-slash text-rose-400' : 'fa-microphone text-emerald-400'}"></i>
                        <i class="fa-solid ${isVideoMuted ? 'fa-video-slash text-rose-400' : 'fa-video text-slate-300'}"></i>
                    </div>
                </div>
            `;

            serverPeers.forEach(peer => {
                const initials = getInitials(peer.user_name);
                html += `
                    <div class="p-3 bg-surface-base rounded-2xl border border-surface-border flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-surface-tile text-slate-300 border border-surface-border flex items-center justify-center font-bold text-xs">
                                ${initials}
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-white">${peer.user_name}</p>
                                <span class="text-[10px] text-slate-500 font-mono">${peer.peer_id.substring(0, 8)}...</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 text-xs text-slate-400">
                            <i class="fa-solid fa-microphone text-emerald-400"></i>
                            <i class="fa-solid fa-video text-slate-300"></i>
                        </div>
                    </div>
                `;
            });

            participantsList.innerHTML = html;
        }

        function updateParticipantBadges() {
            const count = activePeers.size + 1;
            participantCountBadge.textContent = count;
            drawerCount.textContent = count;
        }

        function toggleParticipantsDrawer() {
            participantsDrawer.classList.toggle('translate-x-full');
        }

        // ==========================================
        // 13. CALL TIMER
        // ==========================================
        function startCallTimer() {
            callSeconds = 0;
            if (callDurationTimer) clearInterval(callDurationTimer);

            callDurationTimer = setInterval(() => {
                callSeconds++;
                const mins = String(Math.floor(callSeconds / 60)).padStart(2, '0');
                const secs = String(callSeconds % 60).padStart(2, '0');
                callTimerEl.textContent = `${mins}:${secs}`;
            }, 1000);
        }

        // ==========================================
        // 14. IN-CALL CONTROLS (MIC, VIDEO, SCREEN)
        // ==========================================
        function toggleAudio() {
            if (!myStream || !myStream.getAudioTracks().length) {
                return showToast("No audio track detected!", "error");
            }
            const track = myStream.getAudioTracks()[0];
            track.enabled = !track.enabled;
            isAudioMuted = !track.enabled;

            const icon = document.getElementById('mic-icon');
            const btn = document.getElementById('mic-btn');
            const badge = document.getElementById('local-mic-badge');

            if (track.enabled) {
                icon.className = "fa-solid fa-microphone text-base";
                btn.className = "w-12 h-12 rounded-2xl bg-surface-tile hover:bg-surface-highlight text-white flex items-center justify-center transition shadow-md border border-surface-border active:scale-95 group relative";
                badge.className = "text-emerald-400 text-xs";
                badge.innerHTML = `<i class="fa-solid fa-microphone"></i>`;
                showToast("Microphone Unmuted", "info");
            } else {
                icon.className = "fa-solid fa-microphone-slash text-base";
                btn.className = "w-12 h-12 rounded-2xl bg-rose-600/30 text-rose-400 border border-rose-500/40 flex items-center justify-center transition shadow-md active:scale-95 group relative";
                badge.className = "text-rose-400 text-xs";
                badge.innerHTML = `<i class="fa-solid fa-microphone-slash"></i>`;
                showToast("Microphone Muted", "info");
            }
        }

        function toggleVideo() {
            if (!myStream || !myStream.getVideoTracks().length) {
                return showToast("No video track detected!", "error");
            }
            const track = myStream.getVideoTracks()[0];
            track.enabled = !track.enabled;
            isVideoMuted = !track.enabled;

            const icon = document.getElementById('video-icon');
            const btn = document.getElementById('video-btn');

            if (track.enabled) {
                icon.className = "fa-solid fa-video text-base";
                btn.className = "w-12 h-12 rounded-2xl bg-surface-tile hover:bg-surface-highlight text-white flex items-center justify-center transition shadow-md border border-surface-border active:scale-95 group relative";
                meetingLocalCamOff.classList.add('hidden');
                showToast("Camera Turned On", "info");
            } else {
                icon.className = "fa-solid fa-video-slash text-base";
                btn.className = "w-12 h-12 rounded-2xl bg-rose-600/30 text-rose-400 border border-rose-500/40 flex items-center justify-center transition shadow-md active:scale-95 group relative";
                meetingLocalCamOff.classList.remove('hidden');
                showToast("Camera Turned Off", "info");
            }
        }

        function syncControlButtons() {
            if (isAudioMuted) {
                document.getElementById('mic-icon').className = "fa-solid fa-microphone-slash text-base";
                document.getElementById('mic-btn').className = "w-12 h-12 rounded-2xl bg-rose-600/30 text-rose-400 border border-rose-500/40 flex items-center justify-center transition shadow-md active:scale-95 group relative";
            }
            if (isVideoMuted) {
                document.getElementById('video-icon').className = "fa-solid fa-video-slash text-base";
                document.getElementById('video-btn').className = "w-12 h-12 rounded-2xl bg-rose-600/30 text-rose-400 border border-rose-500/40 flex items-center justify-center transition shadow-md active:scale-95 group relative";
            }
        }

        async function toggleScreenShare() {
            if (!isScreenSharing) {
                try {
                    const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    const screenTrack = screenStream.getVideoTracks()[0];

                    // Replace track across all active peer connections
                    activePeers.forEach(({ call }) => {
                        if (call && call.peerConnection) {
                            const sender = call.peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                            if (sender) sender.replaceTrack(screenTrack);
                        }
                    });

                    meetingLocalVideo.srcObject = screenStream;
                    isScreenSharing = true;
                    applyMirrorState();

                    const screenBtn = document.getElementById('screen-btn');
                    screenBtn.className = "w-12 h-12 rounded-2xl bg-brand-600 text-white flex items-center justify-center transition shadow-glow-indigo border border-brand-400 active:scale-95 group relative";

                    showToast("Screen Sharing Started", "success");

                    screenTrack.onended = () => {
                        stopScreenShare();
                    };
                } catch (err) {
                    console.warn("Screen share cancelled or failed:", err);
                }
            } else {
                stopScreenShare();
            }
        }

        function stopScreenShare() {
            if (isScreenSharing && myStream) {
                const videoTrack = myStream.getVideoTracks()[0];

                activePeers.forEach(({ call }) => {
                    if (call && call.peerConnection) {
                        const sender = call.peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender && videoTrack) sender.replaceTrack(videoTrack);
                    }
                });

                meetingLocalVideo.srcObject = myStream;
                isScreenSharing = false;
                applyMirrorState();

                const screenBtn = document.getElementById('screen-btn');
                screenBtn.className = "w-12 h-12 rounded-2xl bg-surface-tile hover:bg-surface-highlight text-white flex items-center justify-center transition shadow-md border border-surface-border active:scale-95 group relative";

                showToast("Screen Sharing Stopped", "info");
            }
        }

        // ==========================================
        // 15. SHAREABLE INVITE LINK GENERATION
        // ==========================================
        function copyInviteLink() {
            if (!currentRoomId) {
                return showToast("Join a room first to get the invite link!", "error");
            }

            const inviteUrl = `${window.location.origin}${window.location.pathname}?room=${currentRoomId}`;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(inviteUrl).then(() => {
                    showToast("Invite link copied to clipboard! Share it with anyone.", "success");
                }).catch(() => {
                    fallbackCopy(inviteUrl);
                });
            } else {
                fallbackCopy(inviteUrl);
            }

            // If on mobile device with native sharing capability
            if (navigator.share && /Mobi|Android/i.test(navigator.userAgent)) {
                navigator.share({
                    title: `Join my NexusCall Meeting: ${currentRoomId}`,
                    text: `Join my real-time video call on NexusCall!`,
                    url: inviteUrl
                }).catch(() => {});
            }
        }

        function fallbackCopy(text) {
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
            showToast("Invite link copied to clipboard!", "success");
        }

        // ==========================================
        // 16. LEAVE ROOM & CLEANUP
        // ==========================================
        function confirmLeaveRoom() {
            if (confirm("Are you sure you want to leave this meeting?")) {
                leaveMeetingRoom();
            }
        }

        async function leaveMeetingRoom() {
            // Notify backend
            if (currentRoomId && myPeerId) {
                try {
                    await fetch('room.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'leave',
                            room_id: currentRoomId,
                            peer_id: myPeerId
                        })
                    });
                } catch (e) {}
            }

            // Stop screen share
            if (isScreenSharing) {
                stopScreenShare();
            }

            // Close all active peer calls
            activePeers.forEach(({ call }) => {
                try { call.close(); } catch (e) {}
            });
            activePeers.clear();

            // Destroy PeerJS instance
            if (peer) {
                peer.destroy();
                peer = null;
            }

            // Clear intervals
            if (heartbeatTimer) clearInterval(heartbeatTimer);
            if (callDurationTimer) clearInterval(callDurationTimer);

            // Remove all remote tiles
            const remoteTiles = document.querySelectorAll('.participant-tile:not(#local-participant-tile)');
            remoteTiles.forEach(tile => tile.remove());

            // Reset UI states
            meetingScreen.classList.add('hidden');
            lobbyScreen.classList.remove('hidden');
            participantsDrawer.classList.add('translate-x-full');

            // Restore preview video to lobby
            lobbyPreviewVideo.srcObject = myStream;
            applyMirrorState();

            showToast("You have left the meeting.", "info");
        }

        // Send beacon on tab close / unload to prune participant immediately
        window.addEventListener('beforeunload', () => {
            if (currentRoomId && myPeerId) {
                const payload = JSON.stringify({
                    action: 'leave',
                    room_id: currentRoomId,
                    peer_id: myPeerId
                });
                navigator.sendBeacon('room.php', payload);
            }
        });

        // Keyboard Shortcuts (M for Mic, V for Cam, S for Screen)
        window.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

            if (e.key === 'm' || e.key === 'M') {
                e.preventDefault();
                toggleAudio();
            } else if (e.key === 'v' || e.key === 'V') {
                e.preventDefault();
                toggleVideo();
            } else if (e.key === 's' || e.key === 'S') {
                e.preventDefault();
                toggleScreenShare();
            }
        });

        // ==========================================
        // 17. INITIAL PAGE LOAD & URL PARSER
        // ==========================================
        window.addEventListener('DOMContentLoaded', () => {
            // Restore saved name if available
            const savedName = localStorage.getItem('nexuscall_user_name');
            if (savedName) {
                lobbyUserNameInput.value = savedName;
            }

            // Parse ?room= from URL query params
            const urlParams = new URLSearchParams(window.location.search);
            const roomFromUrl = urlParams.get('room');

            if (roomFromUrl) {
                const cleanRoom = roomFromUrl.replace(/[^a-zA-Z0-9_-]/g, '');
                lobbyRoomIdInput.value = cleanRoom;
                document.getElementById('lobby-invite-alert').classList.remove('hidden');
                document.getElementById('join-btn-label').textContent = `Join Room "${cleanRoom}"`;
            } else {
                generateRandomRoomId();
            }

            // Start local media preview
            initializeLocalMedia();
        });
    </script>
</body>

</html>