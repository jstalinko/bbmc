<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { 
    Users, 
    Award, 
    Info, 
    ArrowLeft,
    RefreshCw,
    CheckCircle2
} from 'lucide-vue-next';

interface CandidateResult {
    calon_id: number;
    calon_name: string;
    calon_foto: string | null;
    total_vote: number;
    percentage: number;
}

const props = defineProps<{
    results: Array<CandidateResult>;
    totalVotes: number;
}>();

const page = usePage();
const flashMessage = ref<string | null>(page.props.flash?.message || null);

// Auto-refresh mechanism: reload page properties from server every 4 seconds
const countdown = ref(4);
const isRefreshing = ref(false);
let timerId: any = null;

onMounted(() => {
    timerId = setInterval(() => {
        if (countdown.value > 1) {
            countdown.value--;
        } else {
            countdown.value = 4;
            isRefreshing.value = true;
            router.reload({
                only: ['results', 'totalVotes'],
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

        <!-- Header -->
        <header class="relative z-10 w-full border-b border-red-100 bg-white/60 backdrop-blur-md px-4 py-4 sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
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
        <main class="relative z-10 mx-auto flex w-full max-w-4xl flex-col px-4 py-10 sm:px-6 lg:px-8">
            
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

            <!-- Dashboard Summary Card (Total votes & Sync Status) -->
            <div class="rounded-2xl border border-red-100/60 bg-white p-6 shadow-xl shadow-red-100/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 mb-8 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-5 text-red-600 pointer-events-none">
                    <Users class="h-32 w-32" />
                </div>
                <div>
                    <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Total Suara Masuk</span>
                    <div class="text-4xl font-black text-zinc-950 mt-1 font-mono tracking-tight flex items-baseline gap-2">
                        <span>{{ totalVotes }}</span>
                    </div>
                </div>

                <div class="flex flex-col sm:items-end justify-between gap-3 z-10">
                    <div class="flex items-center gap-3 text-xs text-zinc-500">
                        <span class="flex items-center gap-1.5 font-mono">
                            <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin text-red-500': isRefreshing }" />
                            <span>Sync server berikutnya dalam {{ countdown }}s</span>
                        </span>
                        <button 
                            @click="() => { countdown = 4; router.reload({ only: ['results', 'totalVotes'] }); }" 
                            class="rounded-lg bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 transition-colors uppercase font-bold text-[11px] tracking-wider border border-red-100/80"
                        >
                            Segarkan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Candidates Results Section -->
            <div class="space-y-6">
                <!-- Title header -->
                <div class="flex items-center justify-between border-b border-red-100 pb-3">
                    <h3 class="font-oswald text-base font-bold text-zinc-950 uppercase tracking-wider flex items-center gap-2">
                        <Award class="h-4.5 w-4.5 text-red-500" />
                        <span>Kandidat El Presidente</span>
                    </h3>
                    <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Presentase Suara</span>
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

                <!-- Candidate Results Grid -->
                <div v-else class="grid grid-cols-1 gap-4">
                    <div 
                        v-for="candidate in results" 
                        :key="candidate.calon_id" 
                        class="group flex flex-col sm:flex-row items-center gap-5 rounded-2xl border border-red-100/60 bg-white p-5 hover:border-red-400 hover:shadow-red-200/30 transition-all duration-300 relative overflow-hidden shadow-xl shadow-red-100/20"
                    >
                        <!-- Large Circle Photo -->
                        <div class="h-20 w-20 shrink-0 rounded-full border-2 border-white overflow-hidden bg-zinc-100 shadow-md flex items-center justify-center relative ring-4 ring-red-500/10 group-hover:ring-red-500/20 transition-all">
                            <img 
                                v-if="getCandidatePhoto(candidate.calon_foto)" 
                                :src="getCandidatePhoto(candidate.calon_foto)" 
                                class="h-full w-full object-cover" 
                                alt="Foto Calon" 
                            />
                            <div v-else class="h-full w-full flex items-center justify-center text-2xl font-black text-red-600 bg-red-50">
                                {{ candidate.calon_name.charAt(0) }}
                            </div>
                        </div>

                        <!-- Candidate Info, Bar, & Stats -->
                        <div class="flex-1 w-full space-y-2">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-oswald text-md font-bold text-zinc-950 uppercase tracking-wide group-hover:text-red-600 transition-colors">
                                        {{ candidate.calon_name }}
                                    </h4>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-black text-red-600 font-mono leading-none transition-all duration-300">{{ candidate.percentage }}%</div>
                                    <div class="text-[10px] text-zinc-500 font-mono mt-0.5 transition-all duration-300">{{ candidate.total_vote }} Suara</div>
                                </div>
                            </div>

                            <!-- Animated Custom Progress Bar -->
                            <div class="relative w-full h-3 rounded-full bg-zinc-100 overflow-hidden border border-zinc-200/80">
                                <div 
                                    class="h-full rounded-full bg-gradient-to-r from-red-600 to-red-500 transition-all duration-700 ease-out relative"
                                    :style="{ width: candidate.percentage + '%' }"
                                >
                                    <!-- Shiny overlay on progress bar -->
                                    <div class="absolute inset-0 bg-[linear-gradient(90deg,transparent,rgba(255,255,255,0.15),transparent)] animate-[pulse_2s_infinite]"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="relative z-10 w-full border-t border-red-100 bg-white py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
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