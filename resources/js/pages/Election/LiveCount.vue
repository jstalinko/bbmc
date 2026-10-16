<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Users,
    Award,
    Info,
    RefreshCw,
    Vote,
    Building2,
    Globe,
    Percent,
    ExternalLink,
    BarChart3
} from 'lucide-vue-next';

interface CandidateResult {
    calon_id: number;
    no_urut?: number | null;
    no_kartu?: string | null;
    chapter?: string | null;
    calon_name: string;
    calon_panggilan?: string | null;
    calon_foto: string | null;
    online_vote: number;
    offline_vote: number;
    total_vote: number;
    percentage: number;
}

const props = defineProps<{
    results: Array<CandidateResult>;
    totalSuaraMasuk: number;
    totalSuaraOffline: number;
    totalSuaraOnline: number;
    totalPemilih: number;
    totalPemilihOffline: number;
    totalPemilihOnline: number;
    partisipasiSuara: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Live Count', href: '/dashboard/live-count' },
];

const suaraMasuk = computed(() => props.totalSuaraMasuk ?? 0);
const suaraOffline = computed(() => props.totalSuaraOffline ?? 0);
const suaraOnline = computed(() => props.totalSuaraOnline ?? 0);
const pemilih = computed(() => props.totalPemilih ?? 0);
const pemilihOffline = computed(() => props.totalPemilihOffline ?? 0);
const pemilihOnline = computed(() => props.totalPemilihOnline ?? 0);
const partisipasi = computed(() => props.partisipasiSuara ?? 0);

const refreshKeys = [
    'results',
    'totalSuaraMasuk',
    'totalSuaraOffline',
    'totalSuaraOnline',
    'totalPemilih',
    'totalPemilihOffline',
    'totalPemilihOnline',
    'partisipasiSuara',
];

const countdown = ref(5);
const isRefreshing = ref(false);
let timerId: any = null;

const manualRefresh = () => {
    countdown.value = 5;
    isRefreshing.value = true;
    router.reload({
        only: refreshKeys,
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

onMounted(() => {
    timerId = setInterval(() => {
        if (countdown.value > 1) {
            countdown.value--;
        } else {
            countdown.value = 5;
            isRefreshing.value = true;
            router.reload({
                only: refreshKeys,
                onFinish: () => {
                    isRefreshing.value = false;
                },
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
    <Head title="Live Count - Dashboard Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6 max-w-7xl mx-auto w-full">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-50 text-red-600 border border-red-200 mb-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                        <span>Real-Time Admin Monitor</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight flex items-center gap-2">
                        <BarChart3 class="h-6 w-6 text-red-600" />
                        <span>Live Count Pemilihan El Presidente</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-muted-foreground mt-0.5">
                        Pantau langsung total suara masuk, partisipasi pemilih, dan perolehan suara masing-masing calon El Presidente secara real-time.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground font-mono bg-muted/50 px-3 py-1.5 rounded-xl border">
                        <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin text-red-500': isRefreshing }" />
                        <span>Auto-sync: {{ countdown }}s</span>
                    </div>
                    <button
                        @click="manualRefresh"
                        class="rounded-xl bg-red-600 hover:bg-red-700 text-white px-4 py-2 transition-all uppercase font-bold text-xs tracking-wider shadow-sm cursor-pointer"
                    >
                        Segarkan Data
                    </button>
                    <Link
                        href="/election/polling"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-card hover:bg-muted px-3.5 py-2 text-xs font-semibold transition-all"
                    >
                        <span>Halaman Publik</span>
                        <ExternalLink class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </div>

            <!-- Main Summary Card (All 8 Metrics) -->
            <div class="rounded-3xl border border-red-100/80 dark:border-zinc-800 bg-card p-6 sm:p-8 shadow-sm relative overflow-hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 relative z-10">
                    <!-- Card 1: Total Suara Masuk, Offline, Online -->
                    <div class="rounded-2xl border border-red-100/80 dark:border-zinc-800 bg-gradient-to-br from-white to-red-50/30 dark:from-zinc-900 dark:to-zinc-900/50 p-5 shadow-xs space-y-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-xl bg-red-500/10 text-red-600">
                                    <Vote class="h-6 w-6" />
                                </div>
                                <div>
                                    <span class="block text-[11px] font-bold text-zinc-500 uppercase tracking-widest">
                                        Total Suara Masuk
                                    </span>
                                    <div class="text-3xl sm:text-4xl font-black text-zinc-950 dark:text-zinc-100 font-mono tracking-tight leading-none mt-1">
                                        {{ suaraMasuk }} <span class="text-xs sm:text-sm font-bold text-zinc-400 uppercase font-sans">Suara</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-red-100/60 dark:border-zinc-800">
                            <div class="rounded-xl bg-white dark:bg-zinc-800/70 p-3 border border-zinc-100 dark:border-zinc-700/60 shadow-2xs">
                                <div class="flex items-center gap-1.5 text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
                                    <Building2 class="h-3.5 w-3.5 text-amber-600 shrink-0" />
                                    <span>Total Suara Offline</span>
                                </div>
                                <div class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-zinc-100 font-mono mt-1">
                                    {{ suaraOffline }} <span class="text-[10px] font-semibold text-zinc-400 font-sans">Suara</span>
                                </div>
                            </div>

                            <div class="rounded-xl bg-white dark:bg-zinc-800/70 p-3 border border-zinc-100 dark:border-zinc-700/60 shadow-2xs">
                                <div class="flex items-center gap-1.5 text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
                                    <Globe class="h-3.5 w-3.5 text-blue-600 shrink-0" />
                                    <span>Total Suara Online</span>
                                </div>
                                <div class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-zinc-100 font-mono mt-1">
                                    {{ suaraOnline }} <span class="text-[10px] font-semibold text-zinc-400 font-sans">Suara</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Total Pemilih, Pemilih Offline, Pemilih Online -->
                    <div class="rounded-2xl border border-red-100/80 dark:border-zinc-800 bg-gradient-to-br from-white to-red-50/30 dark:from-zinc-900 dark:to-zinc-900/50 p-5 shadow-xs space-y-4">
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

                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-red-100/60 dark:border-zinc-800">
                            <div class="rounded-xl bg-white dark:bg-zinc-800/70 p-3 border border-zinc-100 dark:border-zinc-700/60 shadow-2xs">
                                <div class="flex items-center gap-1.5 text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
                                    <Building2 class="h-3.5 w-3.5 text-amber-600 shrink-0" />
                                    <span>Total Pemilih Offline</span>
                                </div>
                                <div class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-zinc-100 font-mono mt-1">
                                    {{ pemilihOffline }} <span class="text-[10px] font-semibold text-zinc-400 font-sans">Anggota</span>
                                </div>
                            </div>

                            <div class="rounded-xl bg-white dark:bg-zinc-800/70 p-3 border border-zinc-100 dark:border-zinc-700/60 shadow-2xs">
                                <div class="flex items-center gap-1.5 text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
                                    <Globe class="h-3.5 w-3.5 text-blue-600 shrink-0" />
                                    <span>Total Pemilih Online</span>
                                </div>
                                <div class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-zinc-100 font-mono mt-1">
                                    {{ pemilihOnline }} <span class="text-[10px] font-semibold text-zinc-400 font-sans">Anggota</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Partisipasi Suara (Persentase) & Suara Terkumpul -->
                    <div class="rounded-2xl border border-emerald-100/80 dark:border-zinc-800 bg-gradient-to-br from-white to-emerald-50/30 dark:from-zinc-900 dark:to-zinc-900/50 p-5 shadow-xs space-y-4 md:col-span-2 lg:col-span-1 flex flex-col justify-between">
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

                        <div class="space-y-2 pt-3 border-t border-emerald-100/60 dark:border-zinc-800">
                            <div class="flex items-center justify-between text-xs font-mono">
                                <span class="text-zinc-500 font-semibold">Suara Terkumpul</span>
                                <span class="font-bold text-zinc-900 dark:text-zinc-100 text-sm">{{ suaraMasuk }} / {{ pemilih }}</span>
                            </div>
                            <div class="relative w-full h-3 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden border border-zinc-200/80 dark:border-zinc-700">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-700 ease-out"
                                    :style="{ width: Math.min(100, Math.max(0, partisipasi)) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footnote -->
                <div class="mt-6 pt-4 border-t border-zinc-100 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-3 text-[11px] text-muted-foreground font-medium">
                    <div class="flex items-center gap-2">
                        <Info class="h-4 w-4 text-red-500 shrink-0" />
                        <span>
                            <strong>Persyaratan Hak Pilih:</strong> Seluruh Anggota BBMC Indonesia dengan status <strong>CLEAN / NO PENALTY</strong>.
                        </span>
                    </div>
                    <div class="text-[10px] font-mono">
                        Data langsung dari database (Tanpa penundaan / throttle)
                    </div>
                </div>
            </div>

            <!-- Perolehan Suara Calon El Presidente Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h2 class="text-base sm:text-lg font-bold uppercase tracking-wide flex items-center gap-2">
                        <Award class="h-5 w-5 text-red-600" />
                        <span>Perolehan Suara Calon El Presidente</span>
                    </h2>
                    <span class="rounded-full bg-red-50 dark:bg-red-950/40 px-3 py-1 font-mono text-xs font-bold text-red-600 border border-red-200 dark:border-red-800/40">
                        {{ results.length }} Calon Ditetapkan
                    </span>
                </div>

                <!-- Empty State -->
                <div v-if="results.length === 0" class="text-center py-16 bg-card rounded-2xl border shadow-xs">
                    <div class="mx-auto h-10 w-10 text-muted-foreground mb-3 flex items-center justify-center">
                        <Info class="h-6 w-6" />
                    </div>
                    <h4 class="text-sm font-bold uppercase">Belum Ada Calon Ditetapkan</h4>
                    <p class="text-xs text-muted-foreground mt-1 max-w-xs mx-auto leading-relaxed">
                        Belum ada data perolehan suara karena belum ada calon El Presidente yang berstatus ditetapkan.
                    </p>
                </div>

                <!-- Candidate Cards Grid -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="candidate in results"
                        :key="candidate.calon_id"
                        class="group flex flex-col justify-between rounded-2xl border border-red-100/80 dark:border-zinc-800 bg-card p-6 hover:border-red-500/80 hover:shadow-lg transition-all duration-300 relative overflow-hidden shadow-xs"
                    >
                        <!-- Profile Photo & Identity -->
                        <div class="flex flex-col items-center text-center">
                            <div class="h-24 w-24 shrink-0 rounded-full border-4 border-white dark:border-zinc-800 shadow-md overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center relative ring-4 ring-red-500/15 group-hover:ring-red-500/30 transition-all duration-300">
                                <img
                                    v-if="getCandidatePhoto(candidate.calon_foto)"
                                    :src="getCandidatePhoto(candidate.calon_foto)!"
                                    class="h-full w-full object-cover"
                                    alt="Foto Calon"
                                />
                                <div v-else class="h-full w-full flex items-center justify-center text-3xl font-black text-red-600 bg-red-50">
                                    {{ candidate.calon_name.charAt(0) }}
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-center gap-1.5 flex-wrap">
                                <span class="inline-block rounded-full bg-red-50 dark:bg-red-950/40 px-2.5 py-0.5 font-mono text-[10px] font-bold uppercase tracking-wider text-red-600 border border-red-100 dark:border-red-800/40">
                                    Calon El Presidente
                                </span>
                                <span v-if="candidate.no_urut" class="inline-block rounded-full bg-amber-500 text-white px-2.5 py-0.5 font-mono text-[10px] font-bold uppercase tracking-wider shadow-xs">
                                    No. Urut #{{ candidate.no_urut }}
                                </span>
                            </div>

                            <h3 class="text-lg font-bold uppercase tracking-wide group-hover:text-red-600 transition-colors mt-2 leading-snug">
                                {{ candidate.calon_name }}
                            </h3>
                            <p v-if="candidate.calon_panggilan" class="text-xs text-red-600 font-semibold uppercase tracking-wider mt-0.5">
                                "{{ candidate.calon_panggilan }}"
                            </p>
                            <div v-if="candidate.no_kartu || candidate.chapter" class="mt-2 flex flex-wrap items-center justify-center gap-1.5 text-[10px] font-mono text-muted-foreground">
                                <span v-if="candidate.no_kartu" class="bg-muted px-2 py-0.5 rounded border">KTA: {{ candidate.no_kartu }}</span>
                                <span v-if="candidate.chapter" class="bg-muted px-2 py-0.5 rounded border">{{ candidate.chapter }}</span>
                            </div>
                        </div>

                        <!-- Vote Totals, Breakdown & Progress Bar -->
                        <div class="mt-6 pt-4 border-t space-y-3">
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Total Perolehan</span>
                                <span class="font-mono text-base font-black text-zinc-900 dark:text-zinc-100">{{ candidate.total_vote }} Suara</span>
                            </div>

                            <!-- Online vs Offline Vote Breakdown -->
                            <div class="grid grid-cols-2 gap-2 text-center">
                                <div class="rounded-xl bg-muted/50 p-2 border">
                                    <span class="block text-[10px] font-semibold text-muted-foreground uppercase">Suara Online</span>
                                    <span class="font-mono text-xs font-bold text-blue-600">{{ candidate.online_vote }} Suara</span>
                                </div>
                                <div class="rounded-xl bg-muted/50 p-2 border">
                                    <span class="block text-[10px] font-semibold text-muted-foreground uppercase">Suara Offline</span>
                                    <span class="font-mono text-xs font-bold text-amber-600">{{ candidate.offline_vote }} Suara</span>
                                </div>
                            </div>

                            <div class="text-center py-1">
                                <div class="text-3xl font-black text-red-600 font-mono leading-none tracking-tight">
                                    {{ candidate.percentage }}%
                                </div>
                            </div>

                            <div class="relative w-full h-3 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden border border-zinc-200/80 dark:border-zinc-700">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-red-600 to-red-500 transition-all duration-700 ease-out"
                                    :style="{ width: Math.min(100, Math.max(0, candidate.percentage)) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
