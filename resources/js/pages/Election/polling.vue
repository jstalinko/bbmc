<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { 
    Users, 
    Award, 
    Info, 
    ArrowLeft,
    RefreshCw,
    CheckCircle2,
    Vote,
    Building2,
    Globe,
    Percent
} from 'lucide-vue-next';

interface CandidateResult {
    calon_id: number;
    no_urut?: number | null;
    calon_name: string;
    calon_foto: string | null;
    total_vote: number;
    percentage: number;
}

const props = defineProps<{
    results: Array<CandidateResult>;
    tampilkanKandidat?: boolean;
    totalSuaraMasuk?: number;
    totalSuaraOffline?: number;
    totalSuaraOnline?: number;
    totalPemilih?: number;
    totalPemilihOffline?: number;
    totalPemilihOnline?: number;
    partisipasiSuara?: number;
    // Fallback props
    totalVotes?: number;
    totalVoters?: number;
    percentageVoted?: number;
}>();

const page = usePage();
const flashMessage = ref<string | null>(page.props.flash?.message || null);

const isCandidatesVisible = computed(() => props.tampilkanKandidat !== false);
const suaraMasuk = computed(() => props.totalSuaraMasuk ?? props.totalVotes ?? 0);
const suaraOffline = computed(() => props.totalSuaraOffline ?? 0);
const suaraOnline = computed(() => props.totalSuaraOnline ?? 0);
const pemilih = computed(() => props.totalPemilih ?? props.totalVoters ?? 0);
const pemilihOffline = computed(() => props.totalPemilihOffline ?? 0);
const pemilihOnline = computed(() => props.totalPemilihOnline ?? 0);
const partisipasi = computed(() => props.partisipasiSuara ?? props.percentageVoted ?? 0);

const refreshKeys = [
    'results',
    'tampilkanKandidat',
    'totalSuaraMasuk',
    'totalSuaraOffline',
    'totalSuaraOnline',
    'totalPemilih',
    'totalPemilihOffline',
    'totalPemilihOnline',
    'partisipasiSuara',
    'totalVotes',
    'totalVoters',
    'percentageVoted'
];

// Auto-refresh mechanism: reload page properties from server every 4 seconds
const countdown = ref(4);
const isRefreshing = ref(false);
let timerId: any = null;

const manualRefresh = () => {
    countdown.value = 4;
    isRefreshing.value = true;
    router.reload({
        only: refreshKeys,
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
};

onMounted(() => {
    timerId = setInterval(() => {
        if (countdown.value > 1) {
            countdown.value--;
        } else {
            countdown.value = 4;
            isRefreshing.value = true;
            router.reload({
                only: refreshKeys,
                onFinish: () => {
                    isRefreshing.value = false;
                }
            });
        }
    }, 1000);
});

onUnmounted(() => {
    if (timerId) clearInterval(timerId);
});

const getCandidatePhoto = (foto: string | null) => {
    if (!foto) return null;
    return `/storage/${foto}`;
};
</script>

<template>
    <Head title="Live Count Polling - El Presidente BBMC" />

    <!-- Ambient Light/Red Theme for Premium Live Count feel -->
    <div 
        class="relative min-h-screen w-full flex flex-col justify-between overflow-x-hidden text-zinc-950 font-sans selection:bg-red-600 selection:text-white bg-zinc-50/50"
    >
        <!-- Background Ambient Red Glow -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-[20%] -top-[30%] h-[80%] w-[80%] rounded-full bg-red-100/40 blur-[130px]"></div>
            <div class="absolute -right-[20%] top-[20%] h-[70%] w-[70%] rounded-full bg-red-50/40 blur-[130px]"></div>
            <div class="absolute inset-0 bg-[linear-gradient(rgba(220,38,38,0.012)_1px,transparent_1px),linear-gradient(90deg,rgba(220,38,38,0.012)_1px,transparent_1px)] bg-[size:32px_32px]"></div>
        </div>

        <!-- Header / Nav -->
        <header class="relative z-10 w-full border-b border-red-100/60 bg-white/80 backdrop-blur-md py-4 sm:py-6">
            <div class="mx-auto flex w-full max-w-full px-4 sm:px-8 lg:px-12 items-center justify-between">
                <Link :href="route('election.portal')" class="flex items-center gap-2 group text-zinc-600 hover:text-red-600 transition-colors text-xs font-bold uppercase tracking-wider">
                    <ArrowLeft class="h-4 w-4" />
                    <span>Portal</span>
                </Link>
                
                <div class="flex items-center gap-3">
                    <img src="/bbmc-logo.png" class="h-9 w-auto filter drop-shadow" alt="Logo" />
                    <span class="font-bebas text-xl tracking-wider text-red-600">BBMC ELECTION 2026</span>
                </div>

                <Link :href="route('election.login')" class="flex items-center gap-2 group text-zinc-600 hover:text-red-600 transition-colors text-xs font-bold uppercase tracking-wider">
                    <span>Masuk &amp; Pilih</span>
                </Link>
            </div>
        </header>

        <!-- Main Body -->
        <main class="relative z-10 mx-auto flex w-full max-w-full flex-col px-4 py-10 sm:px-8 lg:px-12">
            
            <!-- Hero Title Segment -->
            <div class="mb-10 text-center">
                <h1 class="font-bebas text-4xl sm:text-6xl tracking-wide text-transparent bg-clip-text bg-gradient-to-r from-zinc-950 via-red-600 to-red-800 leading-none">
                    HASIL PERHITUNGAN SUARA SEMENTARA
                </h1>
                <p class="mx-auto mt-2 max-w-lg text-[10px] tracking-[0.15em] text-zinc-600 font-semibold uppercase">
                    Pemilihan El Presidente Bikers Brotherhood MC Indonesia
                </p>
                <div class="mt-4 h-[2px] w-24 mx-auto bg-gradient-to-r from-transparent via-red-500 to-transparent"></div>
            </div>

            <!-- SUCCESS DIALOG FLASH MESSAGE -->
            <div v-if="flashMessage" class="mb-8 rounded-2xl border border-green-200 bg-green-50 p-5 shadow-lg shadow-green-100/50 flex gap-4 items-start transition-all duration-300">
                <div class="rounded-xl bg-green-500/10 border border-green-500/20 p-2 text-green-600 shrink-0">
                    <CheckCircle2 class="h-6 w-6" />
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-green-800 uppercase tracking-wide">Pemberian Suara Berhasil!</h3>
                    <p class="text-xs text-green-700 mt-0.5 leading-relaxed">
                        {{ flashMessage }}
                    </p>
                </div>
            </div>

            <!-- Dashboard Summary Card (Total Suara Masuk, Total Pemilih & Partisipasi Suara) -->
            <div class="rounded-3xl border border-red-100/80 bg-white p-6 sm:p-8 shadow-xl shadow-red-100/20 mb-8 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 opacity-[0.03] text-red-600 pointer-events-none">
                    <Users class="h-56 w-56" />
                </div>
                
                <!-- Top Bar: Title & Sync / Refresh Controls -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-6 mb-6 border-b border-zinc-100 gap-4 relative z-10">
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-50 text-red-600 border border-red-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                            Live Polling Dashboard
                        </span>
                        <h2 class="font-oswald text-xl sm:text-2xl font-bold uppercase tracking-wide text-zinc-950 mt-1">
                            Statistik &amp; Partisipasi Pemilihan
                        </h2>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5 text-xs text-zinc-500 font-mono">
                            <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin text-red-500': isRefreshing }" />
                            <span>Sync server: {{ countdown }}s</span>
                        </div>
                        <button 
                            @click="manualRefresh" 
                            class="rounded-xl bg-red-50 hover:bg-red-600 text-red-600 hover:text-white px-3.5 py-1.5 transition-all uppercase font-bold text-xs tracking-wider border border-red-200 shadow-sm"
                        >
                            Segarkan Data
                        </button>
                    </div>
                </div>

                <!-- Grid: 3 Main Cards covering all 7 metrics -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 relative z-10">
                    
                    <!-- Card 1: Suara Masuk -->
                    <div class="rounded-2xl border border-red-100/80 bg-gradient-to-br from-white to-red-50/30 p-5 shadow-sm space-y-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-xl bg-red-500/10 text-red-600">
                                    <Vote class="h-6 w-6" />
                                </div>
                                <div>
                                    <span class="block text-[11px] font-bold text-zinc-500 uppercase tracking-widest">
                                        Total Suara Masuk
                                    </span>
                                    <div class="text-3xl sm:text-4xl font-black text-zinc-950 font-mono tracking-tight leading-none mt-1">
                                        {{ suaraMasuk }} <span class="text-xs sm:text-sm font-bold text-zinc-400 uppercase font-sans">Suara</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sub-stats: Total Suara Offline & Total Suara Online -->
                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-red-100/60">
                            <div class="rounded-xl bg-white p-3 border border-zinc-100 shadow-xs">
                                <div class="flex items-center gap-1.5 text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
                                    <Building2 class="h-3.5 w-3.5 text-amber-600 shrink-0" />
                                    <span>Total Suara Offline</span>
                                </div>
                                <div class="text-xl sm:text-2xl font-black text-zinc-900 font-mono mt-1">
                                    {{ suaraOffline }} <span class="text-[10px] font-semibold text-zinc-400 font-sans">Suara</span>
                                </div>
                            </div>

                            <div class="rounded-xl bg-white p-3 border border-zinc-100 shadow-xs">
                                <div class="flex items-center gap-1.5 text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
                                    <Globe class="h-3.5 w-3.5 text-blue-600 shrink-0" />
                                    <span>Total Suara Online</span>
                                </div>
                                <div class="text-xl sm:text-2xl font-black text-zinc-900 font-mono mt-1">
                                    {{ suaraOnline }} <span class="text-[10px] font-semibold text-zinc-400 font-sans">Suara</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Total Pemilih -->
                    <div class="rounded-2xl border border-red-100/80 bg-gradient-to-br from-white to-red-50/30 p-5 shadow-sm space-y-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-xl bg-red-500/10 text-red-600">
                                    <Users class="h-6 w-6" />
                                </div>
                                <div>
                                    <span class="block text-[11px] font-bold text-zinc-500 uppercase tracking-widest">
                                        Total Pemilih
                                    </span>
                                    <div class="text-3xl sm:text-4xl font-black text-red-600 font-mono tracking-tight leading-none mt-1">
                                        {{ pemilih }} <span class="text-xs sm:text-sm font-bold text-zinc-400 uppercase font-sans">Anggota</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sub-stats: Total Pemilih Offline & Total Pemilih Online -->
                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-red-100/60">
                            <div class="rounded-xl bg-white p-3 border border-zinc-100 shadow-xs">
                                <div class="flex items-center gap-1.5 text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
                                    <Building2 class="h-3.5 w-3.5 text-amber-600 shrink-0" />
                                    <span>Total Pemilih Offline</span>
                                </div>
                                <div class="text-xl sm:text-2xl font-black text-zinc-900 font-mono mt-1">
                                    {{ pemilihOffline }} <span class="text-[10px] font-semibold text-zinc-400 font-sans">Anggota</span>
                                </div>
                            </div>

                            <div class="rounded-xl bg-white p-3 border border-zinc-100 shadow-xs">
                                <div class="flex items-center gap-1.5 text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
                                    <Globe class="h-3.5 w-3.5 text-blue-600 shrink-0" />
                                    <span>Total Pemilih Online</span>
                                </div>
                                <div class="text-xl sm:text-2xl font-black text-zinc-900 font-mono mt-1">
                                    {{ pemilihOnline }} <span class="text-[10px] font-semibold text-zinc-400 font-sans">Anggota</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Partisipasi Suara -->
                    <div class="rounded-2xl border border-emerald-100/80 bg-gradient-to-br from-white to-emerald-50/30 p-5 shadow-sm space-y-4 md:col-span-2 lg:col-span-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600">
                                    <Percent class="h-6 w-6" />
                                </div>
                                <div>
                                    <span class="block text-[11px] font-bold text-zinc-500 uppercase tracking-widest">
                                        Partisipasi Suara (Persentase)
                                    </span>
                                    <div class="text-3xl sm:text-4xl font-black text-emerald-600 font-mono tracking-tight leading-none mt-1">
                                        {{ partisipasi }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar & Ratio info -->
                        <div class="space-y-2 pt-3 border-t border-emerald-100/60">
                            <div class="flex items-center justify-between text-[11px] font-mono">
                                <span class="text-zinc-500">Suara Terkumpul</span>
                                <span class="font-bold text-zinc-800">{{ suaraMasuk }} / {{ pemilih }}</span>
                            </div>
                            <div class="relative w-full h-3 rounded-full bg-zinc-100 overflow-hidden border border-zinc-200/80">
                                <div 
                                    class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-700 ease-out"
                                    :style="{ width: Math.min(100, Math.max(0, partisipasi)) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Eligibility info footnote inside card -->
                <div class="mt-6 pt-4 border-t border-zinc-100/80 flex flex-wrap items-center justify-between gap-3 text-[11px] text-zinc-500 font-medium relative z-10">
                    <div class="flex items-center gap-2">
                        <Info class="h-4 w-4 text-red-500 shrink-0" />
                        <span>
                            <strong>Persyaratan Hak Pilih:</strong> Seluruh Anggota BBMC Indonesia dengan status <strong>CLEAN / NO PENALTY</strong>.
                        </span>
                    </div>
                    <div class="text-[10px] text-zinc-400 font-mono">
                        Suara offline tercatat dari log TPS &bull; Suara online diverifikasi OTP
                    </div>
                </div>
            </div>

            <!-- Candidates Results Section -->
            <div v-if="isCandidatesVisible" class="space-y-6">
                <!-- Title header -->
                <div class="flex items-center justify-between border-b border-red-100 pb-3">
                    <h3 class="font-oswald text-base font-bold text-zinc-950 uppercase tracking-wider flex items-center gap-2">
                        <Award class="h-4.5 w-4.5 text-red-500" />
                        <span>Kandidat El Presidente</span>
                    </h3>
                    <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Persentase Suara</span>
                </div>

                <!-- Empty State -->
                <div v-if="results.length === 0" class="text-center py-16 bg-white rounded-2xl border border-zinc-100 shadow-xl shadow-red-100/20">
                    <div class="mx-auto h-10 w-10 text-zinc-400 mb-3 flex items-center justify-center">
                        <Info class="h-6 w-6" />
                    </div>
                    <h4 class="font-oswald text-sm font-bold text-zinc-800 uppercase">Belum ada Calon Ditetapkan</h4>
                    <p class="text-[10px] text-zinc-500 mt-1 max-w-xs mx-auto leading-relaxed">
                        Belum ada data hasil perhitungan suara sementara karena belum ada calon El Presidente yang ditetapkan.
                    </p>
                </div>

                <!-- Candidate Results Inline Horizontal (Fit all on mobile without scroll) -->
                <div v-else class="flex flex-row flex-nowrap items-stretch justify-center gap-2 sm:gap-8 w-full pb-6 px-1">
                    <div 
                        v-for="candidate in results" 
                        :key="candidate.calon_id" 
                        class="group flex flex-col justify-between rounded-xl sm:rounded-3xl border border-red-100/80 bg-white p-2.5 sm:p-8 hover:border-red-500/80 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden shadow-xl shadow-red-100/30 min-w-0 flex-1 sm:min-w-[340px] max-w-2xl"
                    >
                        <!-- Top: Profile Photo & Name -->
                        <div class="flex flex-col items-center text-center">
                            <div class="h-14 w-14 sm:h-28 sm:w-28 shrink-0 rounded-full border-2 sm:border-4 border-white shadow-md sm:shadow-lg overflow-hidden bg-zinc-100 flex items-center justify-center relative ring-2 sm:ring-4 ring-red-500/15 group-hover:ring-red-500/30 transition-all duration-300">
                                <img 
                                    v-if="getCandidatePhoto(candidate.calon_foto)" 
                                    :src="getCandidatePhoto(candidate.calon_foto)" 
                                    class="h-full w-full object-cover" 
                                    alt="Foto Calon" 
                                />
                                <div v-else class="h-full w-full flex items-center justify-center text-xl sm:text-4xl font-black text-red-600 bg-red-50 font-oswald">
                                    {{ candidate.calon_name.charAt(0) }}
                                </div>
                            </div>

                            <div class="mt-1.5 sm:mt-4 flex items-center justify-center gap-1 sm:gap-1.5 flex-wrap">
                                <span class="inline-block rounded-full bg-red-50 px-1.5 sm:px-3 py-0.5 font-mono text-[7px] sm:text-[10px] font-bold uppercase tracking-wider text-red-600 border border-red-100">
                                    Calon
                                </span>
                                <span v-if="candidate.no_urut" class="inline-block rounded-full bg-amber-500 text-white px-1.5 sm:px-3 py-0.5 font-mono text-[7px] sm:text-[10px] font-bold uppercase tracking-wider shadow-sm">
                                    No. #{{ candidate.no_urut }}
                                </span>
                            </div>

                            <h4 class="font-oswald text-xs sm:text-xl font-bold text-zinc-950 uppercase tracking-wide group-hover:text-red-600 transition-colors mt-1 sm:mt-2 line-clamp-2 min-h-[2rem] sm:min-h-[3.5rem] flex items-center justify-center leading-tight sm:leading-normal break-words">
                                {{ candidate.calon_name }}
                            </h4>
                        </div>

                        <!-- Bottom: Vote Percentage, Count & Progress Bar -->
                        <div class="mt-2 sm:mt-6 pt-2 sm:pt-5 border-t border-zinc-100 space-y-1.5 sm:space-y-3">
                            <div class="flex flex-col sm:flex-row items-center sm:items-baseline justify-between text-zinc-600 gap-0.5 sm:gap-0">
                                <span class="text-[8px] sm:text-xs font-bold uppercase tracking-wider text-zinc-400">Perolehan</span>
                                <span class="font-mono text-[9px] sm:text-sm font-bold text-zinc-800">{{ candidate.total_vote }} Suara</span>
                            </div>

                            <div class="text-center py-0 sm:py-1">
                                <div class="text-lg sm:text-4xl font-black text-red-600 font-mono leading-none tracking-tight">
                                    {{ candidate.percentage }}%
                                </div>
                            </div>

                            <!-- Animated Progress Bar -->
                            <div class="relative w-full h-1.5 sm:h-3 rounded-full bg-zinc-100 overflow-hidden border border-zinc-200/80">
                                <div 
                                    class="h-full rounded-full bg-gradient-to-r from-red-600 to-red-500 transition-all duration-700 ease-out relative"
                                    :style="{ width: candidate.percentage + '%' }"
                                >
                                    <div class="absolute inset-0 bg-[linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent)] animate-[pulse_2s_infinite]"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="relative z-10 w-full border-t border-red-100 bg-white py-8">
            <div class="mx-auto w-full max-w-full px-4 sm:px-8 lg:px-12">
                <div class="flex flex-col items-center text-center">
                    <span class="font-bebas text-xs sm:text-sm tracking-[0.25em] text-zinc-400 select-none">Bikers Brotherhood MC Indonesia</span>
                    <span class="font-bebas text-lg sm:text-xl tracking-[0.15em] text-red-600 font-bold mt-2">
                        BROTHERHOOD, LOYAL, RESPECT, HONOR, PRIDE
                    </span>
                </div>
                <div class="my-6 h-[1px] w-full bg-red-100/50"></div>
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row text-center sm:text-left">
                    <p class="text-xs text-zinc-500">
                        &copy; {{ new Date().getFullYear() }} <span class="text-zinc-600 font-semibold">Bikers Brotherhood MC Indonesia</span>. All Rights Reserved.
                    </p>
                    <div class="flex items-center gap-2 text-[10px] sm:text-xs text-zinc-500">
                        <span>Managed and Developed by</span>
                        <span class="rounded bg-red-50/50 px-2 py-0.5 font-mono text-red-600 border border-red-100/50 shadow-sm">BBMC IT Division</span>
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>

<style scoped>
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