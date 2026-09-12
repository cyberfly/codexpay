@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script>document.documentElement.classList.remove('dark');</script>
    </head>
    <body class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-900 antialiased selection:bg-indigo-200 selection:text-indigo-950">
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-[36rem] overflow-hidden">
            <div class="absolute left-1/2 top-[-17rem] size-[42rem] -translate-x-1/2 rounded-full bg-indigo-100/70 blur-3xl"></div>
            <div class="absolute right-[-9rem] top-24 size-72 rounded-full bg-sky-100/80 blur-3xl"></div>
        </div>

        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3.5 sm:px-6 lg:px-8" aria-label="Navigasi utama">
                <a href="{{ route('home') }}" class="group flex items-center gap-3" aria-label="{{ config('app.name') }}">
                    <span class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-200 transition duration-300 group-hover:-translate-y-0.5 group-hover:shadow-indigo-300">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" aria-hidden="true"><path d="M12 3 4 7.5v9L12 21l8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/></svg>
                    </span>
                    <span class="text-base font-bold tracking-tight text-slate-950 sm:text-lg">{{ config('app.name') }}</span>
                </a>

                <div class="flex items-center gap-1 text-sm sm:gap-5">
                    <a href="{{ route('products.index') }}" @class(['hidden rounded-lg px-3 py-2 font-medium transition sm:inline', 'bg-indigo-50 text-indigo-700' => request()->routeIs('products.*'), 'text-slate-600 hover:text-slate-950' => ! request()->routeIs('products.*')])>Produk</a>
                    @guest
                        <a href="{{ route('login') }}" class="rounded-lg px-2.5 py-2 font-medium text-slate-600 transition hover:text-slate-950 sm:px-3">Log masuk</a>
                        <a href="{{ route('register') }}" class="rounded-xl bg-slate-900 px-3.5 py-2.5 font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-md sm:px-4">Daftar</a>
                    @endguest

                    @auth
                        @if (auth()->user()->is_admin)
                            <a href="{{ route('dashboard') }}" class="hidden rounded-lg px-3 py-2 font-medium text-slate-600 transition hover:text-slate-950 sm:inline">Dashboard</a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-xl bg-slate-900 px-3.5 py-2.5 font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-md sm:px-4">Log keluar</button>
                        </form>
                    @endauth
                </div>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-7xl px-5 sm:px-6 lg:px-8">{{ $slot }}</main>

        <footer class="mt-16 border-t border-slate-200 bg-white/80">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p>&copy; {{ now()->year }} {{ config('app.name') }}. Semua hak terpelihara.</p>
                <p>Produk digital yang dibina untuk memudahkan langkah seterusnya.</p>
            </div>
        </footer>
    </body>
</html>
