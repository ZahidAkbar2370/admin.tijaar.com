<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $faviconPath = \App\Models\Setting::get('favicon');
        $faviconUrl = $faviconPath ? \App\Support\UploadHelper::url($faviconPath) : asset('images/tijaar-logo.png');
    @endphp
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <title>@yield('title', 'Admin') – {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs-collapse/3.14.1/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Open Sans', 'sans-serif'] },
                    colors: { primary: '#1790d7', 'primary-dark': '#1277b8' }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>document.addEventListener('DOMContentLoaded', function(){ if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons(); });</script>
    <script>
    window.sweetConfirm = function(ev, text, title) {
        ev.preventDefault();
        var form = ev.target;
        Swal.fire({
            title: title || 'Are you sure?',
            text: text || 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, continue',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            customClass: { popup: 'rounded-2xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-2.5 font-semibold', cancelButton: 'rounded-xl px-6 py-2.5' }
        }).then(function(r) { if (r.isConfirmed) form.submit(); });
        return false;
    };
    </script>
    <style>
        html { font-size: 14px; -webkit-text-size-adjust: 100%; }
        @media (max-width: 768px) { html { font-size: 15px; } }
        body { -webkit-tap-highlight-color: transparent; }
        .admin-text-sm { font-size: 0.8125rem; }
        .sidebar-link { min-height: 44px; display: flex; align-items: center; }
        .sidebar-link.active { background: rgba(23,144,215,0.1); color: #1790d7; font-weight: 600; }
        .sidebar-link:hover:not(.active) { background: rgba(0,0,0,0.04); }
        .sidebar-sub { min-height: 40px; display: flex; align-items: center; }
        .sidebar-sub.active { background: rgba(23,144,215,0.08); color: #1790d7; font-weight: 600; border-left: 2px solid #1790d7; }
        .sidebar-sub:hover:not(.active) { background: rgba(0,0,0,0.03); }
        [x-cloak] { display: none !important; }
        .table-row-hover:hover { background: #f8fafc; }
        .column-toggle-item:hover { background: #f1f5f9; }
        .column-toggle-dropdown { z-index: 9999 !important; }
        .drawer-overlay { z-index: 9998; }
        .drawer-panel { z-index: 9999; will-change: transform; transform: translateZ(0); }
        .drawer-slide-enter { transform: translateX(100%); }
        .drawer-slide-enter-active { transform: translateX(0); transition: transform 0.35s cubic-bezier(0.32, 0.72, 0, 1); }
        .drawer-slide-leave { transform: translateX(0); }
        .drawer-slide-leave-active { transform: translateX(100%); transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1); }
        .drawer-overlay-enter { opacity: 0; }
        .drawer-overlay-enter-active { opacity: 1; transition: opacity 0.3s ease; }
        .drawer-overlay-leave { opacity: 1; }
        .drawer-overlay-leave-active { opacity: 0; transition: opacity 0.25s ease; }
        .dropzone-border { border: 2px dashed #cbd5e1; transition: all 0.2s; }
        .dropzone-border.drag-over, .dropzone-border:hover { border-color: #1790d7; background: rgba(23,144,215,0.05); }
        /* Responsive tables: horizontal scroll on small screens */
        .overflow-x-auto { -webkit-overflow-scrolling: touch; }
        .overflow-x-auto table { min-width: 640px; }
        /* Admin scrollbar: same style for main content */
        .admin-scrollbar { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
        .admin-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
        .admin-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .admin-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .admin-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        /* Sidebar: scrollable, scrollbar hidden */
        .admin-sidebar-nav { -ms-overflow-style: none; scrollbar-width: none; }
        .admin-sidebar-nav::-webkit-scrollbar { display: none; }
        @supports (padding: env(safe-area-inset-bottom)) {
            .safe-area-pb { padding-bottom: env(safe-area-inset-bottom); }
        }
        /* Rich text editors (Quill/CKEditor) — links match site primary color */
        .rich-text-content a,
        .rich-text-content a:link,
        .rich-text-content a:visited,
        .ql-editor a,
        .ql-editor a:link,
        .ql-editor a:visited {
            color: #1790d7 !important;
            text-decoration: underline;
            text-underline-offset: 2px;
            font-weight: 500;
        }
        .rich-text-content a:hover,
        .rich-text-content a:focus,
        .ql-editor a:hover,
        .ql-editor a:focus {
            color: #0d6fa8 !important;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50 min-h-screen safe-area-pb">
    @yield('content')
</body>
</html>
