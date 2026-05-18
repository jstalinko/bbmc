<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Globe, UserPlus, Award, Lock, ExternalLink, ArrowRight, ShieldCheck, ChevronRight } from 'lucide-vue-next';
</script>

<template>
    <Head title="BBMC Indonesia Web Portal">
        <!-- Preconnecting and importing Google Fonts for high-end vintage and rugged typography -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    </Head>

    <!-- Main Container: Bright, clean light canvas matching the member registration page layout -->
    <div 
        class="relative min-h-screen w-full flex flex-col justify-between overflow-x-hidden text-zinc-800 font-sans selection:bg-red-600 selection:text-white"
        style="background: linear-gradient(135deg, #fff5f5 0%, #fff9f7 40%, #fef2f2 100%);"
    >
        
        <!-- Ambient Mesh Gradients (Soft Crimson/Red ambient glow behind card contents) -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <!-- Left Top Soft Glow -->
            <div class="absolute -left-[20%] -top-[30%] h-[80%] w-[80%] rounded-full bg-red-100/50 blur-[130px] lg:blur-[180px]"></div>
            <!-- Right Center Soft Glow -->
            <div class="absolute -right-[20%] top-[20%] h-[70%] w-[70%] rounded-full bg-red-50/40 blur-[130px] lg:blur-[180px]"></div>
            
            <!-- Grid overlay for technical/premium feel -->
            <div class="absolute inset-0 bg-[linear-gradient(rgba(220,38,38,0.015)_1px,transparent_1px),linear-gradient(90deg,rgba(220,38,38,0.015)_1px,transparent_1px)] bg-[size:32px_32px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)]"></div>
        </div>

        <!-- Header / Navigation Bar -->
        <header class="relative z-10 w-full border-b border-red-100 bg-white/60 backdrop-blur-md px-4 py-3 sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <!-- Branding / Short Title -->
                <div class="flex items-center gap-3">
                    <img src="/bbmc-logo.png" class="h-9 w-auto filter drop-shadow-[0_2px_4px_rgba(220,38,38,0.15)]" alt="BBMC Mini Logo" />
                    <span class="font-bebas text-lg sm:text-xl tracking-wider text-red-600">BBMC INDONESIA</span>
                </div>
                
                <!-- Quick Auth Nav Buttons -->
                <nav class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-600/30 bg-red-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-red-600 transition-all hover:bg-red-600 hover:text-white hover:shadow-[0_4px_12px_rgba(220,38,38,0.15)]"
                    >
                        <span>Dashboard</span>
                        <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                   
                </nav>
            </div>
        </header>

        <!-- Main Portal Section -->
        <main class="relative z-10 mx-auto flex w-full max-w-7xl flex-col items-center px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            
            <!-- Hero / Emblem Section -->
            <div class="mb-14 flex flex-col items-center text-center">
                <!-- Large Centered Emblem with interactive scale and red glow pulsing -->
                <div class="group relative mb-6 cursor-pointer">
                    <!-- Glow aura background -->
                    <div class="absolute -inset-1 rounded-full bg-red-100 opacity-40 blur-xl transition duration-1000 group-hover:opacity-75 group-hover:duration-200 animate-pulse"></div>
                    <img 
                        src="/bbmc-logo.png" 
                        class="relative h-28 w-auto filter drop-shadow-[0_8px_16px_rgba(220,38,38,0.15)] transition-transform duration-500 ease-out group-hover:scale-105 sm:h-36" 
                        alt="Bikers Brotherhood MC Logo" 
                    />
                </div>

                <!-- Club Badge Tag -->
                <div class="mb-3 inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-white px-3 py-1 text-xs uppercase tracking-[0.2em] text-red-600 shadow-sm">
                    <ShieldCheck class="h-3.5 w-3.5" />
                    <span>Est. 1988 — Bandung</span>
                </div>

                <!-- Main Bold Club Header in high-end red & black styling -->
                <h1 class="font-bebas text-5xl tracking-wide text-transparent bg-clip-text bg-gradient-to-r from-zinc-950 via-red-600 to-red-800 sm:text-7xl lg:text-8xl">
                    BIKERS BROTHERHOOD
                </h1>
                
                <!-- Under-title text in Oswald font with heavy tracking -->
                <h2 class="mt-1 font-oswald text-lg font-semibold tracking-[0.3em] text-zinc-700 sm:text-2xl lg:text-3xl uppercase">
                    Motorcycle Club Indonesia
                </h2>
                
                <!-- Short Brand Statement -->
                <p class="mx-auto mt-4 max-w-xl text-xs leading-relaxed text-zinc-500 sm:text-sm">
                    Honor, Respect, Brotherhood. The largest motorcycle brotherhood in Indonesia, built on the solid foundations of pride, loyalty, and unconditional dedication. Welcome to our unified digital space.
                </p>
            </div>

            <!-- Square Rounded Menu Portals: Grid layout for 4 items -->
            <div class="grid w-full grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                
                <!-- Card 1: Official Website -->
                <a 
                    href="https://bikersbrotherhoodmc.id/" 
                    target="_blank"
                    class="group relative flex aspect-square flex-col justify-between rounded-2xl border border-red-100 bg-white p-6 shadow-xl shadow-red-100/50 transition-all duration-300 hover:-translate-y-2 hover:border-red-600/50 hover:shadow-2xl hover:shadow-red-200/60"
                >
                    <!-- Gradient accent glow border on hover -->
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-red-500/5 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                    
                    <div class="relative z-10 flex w-full items-start justify-between">
                        <!-- Icon representation inside a styled square wrapper -->
                        <div class="rounded-xl bg-red-50 border border-red-100 p-3 text-red-600 transition-transform duration-300 group-hover:scale-110">
                            <Globe class="h-6 w-6" />
                        </div>
                        <ExternalLink class="h-4 w-4 text-zinc-400 transition-colors group-hover:text-red-500" />
                    </div>

                    <div class="relative z-10 mt-6 text-left">
                        <span class="text-xs font-bold tracking-widest text-red-500 uppercase">PUBLIC SITE</span>
                        <h3 class="mt-1 font-oswald text-xl font-bold tracking-wide text-zinc-900">
                            OFFICIAL WEBSITE
                        </h3>
                        <p class="mt-2 text-xs leading-relaxed text-zinc-500 group-hover:text-zinc-600">
                            Explore the history of BBMC, browse photo galleries, view latest articles, chapter profiles, and official news updates.
                        </p>
                    </div>

                    <!-- Bottom Action Trigger -->
                    <div class="relative z-10 mt-4 flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-red-600 opacity-80 group-hover:opacity-100">
                        <span>Visit Site</span>
                        <ChevronRight class="h-3 w-3 transition-transform duration-300 group-hover:translate-x-1" />
                    </div>
                </a>

                <!-- Card 2: Registrasi Member -->
                <Link 
                    :href="route('member.register')"
                    class="group relative flex aspect-square flex-col justify-between rounded-2xl border border-red-100 bg-white p-6 shadow-xl shadow-red-100/50 transition-all duration-300 hover:-translate-y-2 hover:border-red-600/50 hover:shadow-2xl hover:shadow-red-200/60"
                >
                    <!-- Gradient accent glow border on hover -->
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-red-500/5 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

                    <div class="relative z-10 flex w-full items-start justify-between">
                        <div class="rounded-xl bg-red-50 border border-red-100 p-3 text-red-600 transition-transform duration-300 group-hover:scale-110">
                            <UserPlus class="h-6 w-6" />
                        </div>
                        <ArrowRight class="h-4 w-4 text-zinc-400 transition-colors group-hover:text-red-500" />
                    </div>

                    <div class="relative z-10 mt-6 text-left">
                        <span class="text-xs font-bold tracking-widest text-red-500 uppercase">MEMBERSHIP</span>
                        <h3 class="mt-1 font-oswald text-xl font-bold tracking-wide text-zinc-900">
                            MEMBER REGISTRATION
                        </h3>
                        <p class="mt-2 text-xs leading-relaxed text-zinc-500 group-hover:text-zinc-600">
                            Become a recognized brother. Submit registration credentials, fill in motorcycle details, upload profiles, and acquire your digital ID card.
                        </p>
                    </div>

                    <div class="relative z-10 mt-4 flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-red-600 opacity-80 group-hover:opacity-100">
                        <span>Register Now</span>
                        <ChevronRight class="h-3 w-3 transition-transform duration-300 group-hover:translate-x-1" />
                    </div>
                </Link>

                <!-- Card 3: El Presidente Pra-Election -->
                <Link 
                    href="/election/portal"
                    class="group relative flex aspect-square flex-col justify-between rounded-2xl border border-red-100 bg-white p-6 shadow-xl shadow-red-100/50 transition-all duration-300 hover:-translate-y-2 hover:border-red-600/50 hover:shadow-2xl hover:shadow-red-200/60"
                >
                    <!-- Gradient accent glow border on hover -->
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-red-500/5 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

                    <div class="relative z-10 flex w-full items-start justify-between">
                        <div class="rounded-xl bg-red-50 border border-red-100 p-3 text-red-600 transition-transform duration-300 group-hover:scale-110">
                            <Award class="h-6 w-6" />
                        </div>
                        <ArrowRight class="h-4 w-4 text-zinc-400 transition-colors group-hover:text-red-500" />
                    </div>

                    <div class="relative z-10 mt-6 text-left">
                        <span class="text-xs font-bold tracking-widest text-red-500 uppercase">DEMOCRACY</span>
                        <h3 class="mt-1 font-oswald text-xl font-bold tracking-wide text-zinc-900">
                            PRA-ELECTION PORTAL
                        </h3>
                        <p class="mt-2 text-xs leading-relaxed text-zinc-500 group-hover:text-zinc-600">
                            Explore candidate directories. Access El Presidente nomination details, campaign agenda, vision & mission declarations, and official rules.
                        </p>
                    </div>

                    <div class="relative z-10 mt-4 flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-red-600 opacity-80 group-hover:opacity-100">
                        <span>Enter Portal</span>
                        <ChevronRight class="h-3 w-3 transition-transform duration-300 group-hover:translate-x-1" />
                    </div>
                </Link>

                <!-- Card 4: El Presidente Election -->
                <Link 
                    href="/election/login"
                    class="group relative flex aspect-square flex-col justify-between rounded-2xl border border-red-100 bg-white p-6 shadow-xl shadow-red-100/50 transition-all duration-300 hover:-translate-y-2 hover:border-red-600/50 hover:shadow-2xl hover:shadow-red-200/60"
                >
                    <!-- Gradient accent glow border on hover -->
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-red-500/5 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

                    <div class="relative z-10 flex w-full items-start justify-between">
                        <div class="rounded-xl bg-red-50 border border-red-100 p-3 text-red-600 transition-transform duration-300 group-hover:scale-110">
                            <Lock class="h-6 w-6" />
                        </div>
                        <ArrowRight class="h-4 w-4 text-zinc-400 transition-colors group-hover:text-red-500" />
                    </div>

                    <div class="relative z-10 mt-6 text-left">
                        <span class="text-xs font-bold tracking-widest text-red-500 uppercase">SECURE CAST</span>
                        <h3 class="mt-1 font-oswald text-xl font-bold tracking-wide text-zinc-900">
                            PRESIDENTIAL ELECTION
                        </h3>
                        <p class="mt-2 text-xs leading-relaxed text-zinc-500 group-hover:text-zinc-600">
                            Official e-voting system. Authenticate using your membership ID to securely submit your vote for the next El Presidente.
                        </p>
                    </div>

                    <div class="relative z-10 mt-4 flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-red-600 opacity-80 group-hover:opacity-100">
                        <span>Cast Vote</span>
                        <ChevronRight class="h-3 w-3 transition-transform duration-300 group-hover:translate-x-1" />
                    </div>
                </Link>

            </div>
            
        </main>

        <!-- Footer System with brand typography and vintage elements -->
        <footer class="relative z-10 w-full border-t border-red-100 bg-white/40 py-10 backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <!-- Moto Segment -->
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center gap-4 w-full max-w-2xl mb-3">
                        <div class="h-[1px] flex-grow bg-red-100"></div>
                        <span class="font-bebas text-xs sm:text-sm tracking-[0.25em] text-zinc-400 select-none">Bikers Brotherhood MC Indonesia</span>
                        <div class="h-[1px] flex-grow bg-red-100"></div>
                    </div>
                    
                    <span class="font-bebas text-xl sm:text-3xl tracking-[0.15em] text-red-600 font-bold">
                        BROTHERHOOD, LOYAL, RESPECT, HONOR, PRIDE
                    </span>
                </div>

                <!-- Horizontal separator -->
                <div class="my-6 h-[1px] w-full bg-red-100"></div>

                <!-- Bottom Footer Details -->
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row text-center sm:text-left">
                    <p class="text-xs text-zinc-500">
                        &copy; {{ new Date().getFullYear() }} <span class="text-zinc-600 font-semibold">Bikers Brotherhood Motorcycle Club Indonesia</span>. All Rights Reserved.
                    </p>
                    
                    <div class="flex items-center gap-2 text-[10px] sm:text-xs text-zinc-500">
                        <span>Managed and Developed by</span>
                        <span class="rounded bg-red-50 px-2 py-0.5 font-mono text-red-600 border border-red-200 shadow-sm">BBMC IT Division</span>
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>

<style scoped>
/* Scoped keyframe styles for smooth transitions and subtle floating features */
.font-bebas {
    font-family: 'Bebas Neue', sans-serif;
}
.font-oswald {
    font-family: 'Oswald', sans-serif;
}
.font-sans {
    font-family: 'Outfit', 'Roboto', sans-serif;
}
</style>
