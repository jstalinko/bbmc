<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Table, TableBody, TableCell, TableEmpty,
    TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
    Dialog, DialogContent, DialogDescription,
    DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Search, RefreshCw, X, ShieldAlert, ShieldCheck, CheckCircle2,
    XCircle, Vote, Users, AlertTriangle, ChevronLeft, ChevronRight,
    ChevronsLeft, ChevronsRight, ArrowUpDown, Filter, UserX, Check
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Member {
    id: number;
    nama_lengkap: string;
    nama_panggilan: string;
    no_kartu: string;
    no_wa: string;
    status_keanggotaan: string;
    chapter: string;
    checkpoint?: string;
    region?: string;
    terdaftar_sejak?: string;
    penalty?: string;
    penalty_reason?: string;
    offline_voter: boolean;
    pollings_exists?: boolean;
    foto?: string;
}

interface Stats {
    total_offline_voters: number;
    total_online_voted: number;
    total_eligible: number;
    total_members: number;
}

const props = defineProps<{
    members: {
        data: Member[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    stats: Stats;
    filters: {
        search?: string;
        status_offline?: string;
        status_keanggotaan?: string;
        sort_by?: string;
        sort_dir?: string;
    };
}>();

const page = usePage();
const flash = computed(() => (page.props.flash as Record<string, any>) ?? {});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pemilihan Offline', href: '/pemilihan-offline' },
];

// ── Search & Filter State ───────────────────────────────────────────────────
const search = ref(props.filters?.search ?? '');
const statusOffline = ref(props.filters?.status_offline ?? 'all');
const statusKeanggotaan = ref(props.filters?.status_keanggotaan ?? 'all');
const sortBy = ref(props.filters?.sort_by ?? '');
const sortDir = ref(props.filters?.sort_dir ?? '');

let searchTimer: any;
watch([search, statusOffline, statusKeanggotaan], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters({ page: 1 });
    }, 350);
});

function applyFilters(overrides: Record<string, any> = {}) {
    router.get('/pemilihan-offline', {
        search: overrides.search !== undefined ? overrides.search : search.value,
        status_offline: overrides.status_offline !== undefined ? overrides.status_offline : statusOffline.value,
        status_keanggotaan: overrides.status_keanggotaan !== undefined ? overrides.status_keanggotaan : statusKeanggotaan.value,
        sort_by: overrides.sort_by !== undefined ? overrides.sort_by : sortBy.value,
        sort_dir: overrides.sort_dir !== undefined ? overrides.sort_dir : sortDir.value,
        page: overrides.page ?? props.members.current_page,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearFilters() {
    search.value = '';
    statusOffline.value = 'all';
    statusKeanggotaan.value = 'all';
    sortBy.value = '';
    sortDir.value = '';
    applyFilters({ search: '', status_offline: 'all', status_keanggotaan: 'all', sort_by: '', sort_dir: '', page: 1 });
}

function toggleSort(col: string) {
    let nextDir = 'asc';
    if (sortBy.value === col) {
        nextDir = sortDir.value === 'asc' ? 'desc' : '';
    }
    sortBy.value = nextDir ? col : '';
    sortDir.value = nextDir;
    applyFilters({ page: 1 });
}

// ── Confirmation Modal State ────────────────────────────────────────────────
const targetMember = ref<Member | null>(null);
const modalAction = ref<'mark' | 'unmark'>('mark');
const isProcessing = ref(false);

function openMarkModal(member: Member) {
    targetMember.value = member;
    modalAction.value = 'mark';
}

function openUnmarkModal(member: Member) {
    targetMember.value = member;
    modalAction.value = 'unmark';
}

function closeModal() {
    targetMember.value = null;
    isProcessing.value = false;
}

function confirmAction() {
    if (!targetMember.value) return;

    isProcessing.value = true;
    const url = modalAction.value === 'mark'
        ? `/pemilihan-offline/${targetMember.value.id}/mark`
        : `/pemilihan-offline/${targetMember.value.id}/unmark`;

    router.post(url, {}, {
        preserveScroll: true,
        onFinish: () => {
            isProcessing.value = false;
            closeModal();
        },
    });
}

// Helpers
function formatKta(nocard: string) {
    if (!nocard) return '—';
    const padded = String(nocard).padStart(4, '0');
    return `BBMC 38 2026 ${padded}`;
}

function isEligible(member: Member) {
    const status = (member.status_keanggotaan || '').toUpperCase();
    const isStatusOk = status === 'LIFE MEMBER' || status === 'SS DIPONEGORO';
    const isPenaltyClean = !member.penalty || member.penalty === '' || member.penalty === 'clean';
    return isStatusOk && isPenaltyClean;
}
</script>

<template>
    <Head title="Pemilihan Offline - Admin Panel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6 max-w-7xl mx-auto w-full">
            
            <!-- Flash Message Alerts -->
            <div 
                v-if="flash.success" 
                class="flex items-center gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400 shadow-sm animate-in fade-in"
            >
                <CheckCircle2 class="h-5 w-5 shrink-0 text-emerald-600" />
                <span class="font-medium">{{ flash.success }}</span>
            </div>

            <div 
                v-if="page.props.errors && Object.keys(page.props.errors).length > 0" 
                class="flex items-center gap-3 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-400 shadow-sm animate-in fade-in"
            >
                <AlertTriangle class="h-5 w-5 shrink-0 text-red-600" />
                <span class="font-medium">{{ Object.values(page.props.errors)[0] }}</span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="flex items-center gap-2.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        <Vote class="h-7 w-7 text-red-600" />
                        <span>Pemilihan Offline</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-zinc-500 mt-1">
                        Tandai anggota yang memilih secara langsung/offline. Anggota yang ditandai sebagai pemilih offline tidak dapat login untuk memilih online.
                    </p>
                </div>
            </div>

            <!-- Statistics Overview Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-emerald-200/60 bg-emerald-50/50 p-4 dark:border-emerald-950 dark:bg-emerald-950/20 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Pemilih Offline</span>
                        <div class="p-2 rounded-xl bg-emerald-600 text-white shadow-sm">
                            <Vote class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-emerald-900 dark:text-emerald-200">{{ stats.total_offline_voters }}</span>
                        <span class="text-xs text-emerald-600 font-medium">Anggota</span>
                    </div>
                    <p class="text-[11px] text-emerald-700/80 mt-1">Ditandai memilih langsung di TPS/venue</p>
                </div>

                <div class="rounded-2xl border border-blue-200/60 bg-blue-50/50 p-4 dark:border-blue-950 dark:bg-blue-950/20 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">Sudah Vote Online</span>
                        <div class="p-2 rounded-xl bg-blue-600 text-white shadow-sm">
                            <CheckCircle2 class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-blue-900 dark:text-blue-200">{{ stats.total_online_voted }}</span>
                        <span class="text-xs text-blue-600 font-medium">Suara Masuk</span>
                    </div>
                    <p class="text-[11px] text-blue-700/80 mt-1">Telah memberikan suara via web portal</p>
                </div>

                <div class="rounded-2xl border border-amber-200/60 bg-amber-50/50 p-4 dark:border-amber-950 dark:bg-amber-950/20 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">Hak Suara (Eligible)</span>
                        <div class="p-2 rounded-xl bg-amber-600 text-white shadow-sm">
                            <ShieldCheck class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-amber-900 dark:text-amber-200">{{ stats.total_eligible }}</span>
                        <span class="text-xs text-amber-600 font-medium">Hak Suara</span>
                    </div>
                    <p class="text-[11px] text-amber-700/80 mt-1">Life Member & SS Diponegoro (Clean)</p>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total Anggota</span>
                        <div class="p-2 rounded-xl bg-zinc-700 text-white shadow-sm">
                            <Users class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-zinc-900 dark:text-zinc-100">{{ stats.total_members }}</span>
                        <span class="text-xs text-zinc-500 font-medium">Terdaftar</span>
                    </div>
                    <p class="text-[11px] text-zinc-500 mt-1">Seluruh anggota di database BBMC</p>
                </div>
            </div>

            <!-- Search, Filter & Quick Toolbar -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                    
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input
                            v-model="search"
                            type="text"
                            placeholder="Cari berdasarkan Nama Lengkap, Panggilan, atau No. KTA (contoh: 0023)..."
                            class="pl-10 pr-9 h-11 text-sm rounded-xl border-zinc-200 dark:border-zinc-700 focus-visible:ring-red-500"
                        />
                        <button
                            v-if="search"
                            type="button"
                            @click="search = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Filter Dropdowns -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Filter Offline Status -->
                        <div class="flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800 p-1 rounded-xl text-xs font-medium">
                            <button
                                type="button"
                                @click="statusOffline = 'all'"
                                class="px-3 py-1.5 rounded-lg transition-colors"
                                :class="statusOffline === 'all' ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-xs font-bold' : 'text-zinc-500 hover:text-zinc-800'"
                            >
                                Semua
                            </button>
                            <button
                                type="button"
                                @click="statusOffline = 'offline'"
                                class="px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1"
                                :class="statusOffline === 'offline' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-zinc-500 hover:text-zinc-800'"
                            >
                                <span>Pemilih Offline</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-emerald-700/60 text-white font-mono">
                                    {{ stats.total_offline_voters }}
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="statusOffline = 'not_offline'"
                                class="px-3 py-1.5 rounded-lg transition-colors"
                                :class="statusOffline === 'not_offline' ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-xs font-bold' : 'text-zinc-500 hover:text-zinc-800'"
                            >
                                Belum Ditandai
                            </button>
                        </div>

                        <!-- Filter Status Keanggotaan -->
                        <select
                            v-model="statusKeanggotaan"
                            class="h-11 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 text-xs font-semibold text-zinc-700 dark:text-zinc-200 outline-none focus:ring-2 focus:ring-red-500"
                        >
                            <option value="all">Semua Status Keanggotaan</option>
                            <option value="LIFE MEMBER">LIFE MEMBER</option>
                            <option value="SS DIPONEGORO">SS DIPONEGORO</option>
                            <option value="HONORARY">HONORARY</option>
                            <option value="VIRGIN">VIRGIN</option>
                            <option value="PROSPECT">PROSPECT</option>
                        </select>

                        <!-- Clear filter -->
                        <Button
                            v-if="search || statusOffline !== 'all' || statusKeanggotaan !== 'all'"
                            variant="ghost"
                            size="sm"
                            @click="clearFilters"
                            class="h-11 text-xs text-zinc-500 hover:text-red-600"
                        >
                            <RefreshCw class="h-3.5 w-3.5 mr-1" />
                            Reset
                        </Button>
                    </div>

                </div>
            </div>

            <!-- Members Table Card -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-zinc-50/75 dark:bg-zinc-800/50 hover:bg-zinc-50/75">
                                <TableHead class="w-[180px] font-bold text-xs uppercase tracking-wider">
                                    <button 
                                        type="button" 
                                        class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900"
                                        @click="toggleSort('no_kartu')"
                                    >
                                        <span>No. KTA</span>
                                        <ArrowUpDown class="h-3.5 w-3.5" />
                                    </button>
                                </TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider">
                                    <button 
                                        type="button" 
                                        class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900"
                                        @click="toggleSort('nama_lengkap')"
                                    >
                                        <span>Nama Anggota</span>
                                        <ArrowUpDown class="h-3.5 w-3.5" />
                                    </button>
                                </TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider">
                                    Status Keanggotaan
                                </TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider">
                                    Chapter / Wilayah
                                </TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider text-center">
                                    Status Hak Suara
                                </TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider text-center">
                                    Status Pemilihan
                                </TableHead>
                                <TableHead class="w-[200px] text-right font-bold text-xs uppercase tracking-wider">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        
                        <TableBody>
                            <TableRow v-if="members.data.length === 0">
                                <TableCell colspan="7" class="text-center py-12 text-zinc-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <UserX class="h-8 w-8 text-zinc-300" />
                                        <p class="font-medium text-sm">Tidak ada data anggota yang cocok dengan pencarian.</p>
                                        <p class="text-xs text-zinc-400">Coba ubah kata kunci pencarian nama atau nomor KTA.</p>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow 
                                v-for="m in members.data" 
                                :key="m.id"
                                class="transition-colors hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40"
                                :class="{'bg-emerald-50/40 dark:bg-emerald-950/10': m.offline_voter}"
                            >
                                <!-- No. KTA -->
                                <TableCell class="font-mono text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                    <span class="px-2.5 py-1 rounded-md bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                                        {{ m.no_kartu ? formatKta(m.no_kartu) : '—' }}
                                    </span>
                                </TableCell>

                                <!-- Nama & Detail -->
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="relative shrink-0">
                                            <img
                                                v-if="m.foto"
                                                :src="'/storage/' + m.foto"
                                                :alt="m.nama_lengkap"
                                                class="h-10 w-10 rounded-full object-cover border border-zinc-200 dark:border-zinc-700"
                                            />
                                            <div
                                                v-else
                                                class="h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center font-bold text-zinc-500 text-xs uppercase"
                                            >
                                                {{ (m.nama_lengkap || 'M').substring(0, 2) }}
                                            </div>
                                            <!-- Indicator Dot -->
                                            <span 
                                                v-if="m.offline_voter" 
                                                class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900"
                                                title="Pemilih Offline"
                                            />
                                        </div>
                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100">{{ m.nama_lengkap }}</span>
                                                <span v-if="m.nama_panggilan" class="text-xs text-zinc-500 italic">
                                                    "{{ m.nama_panggilan }}"
                                                </span>
                                            </div>
                                            <p class="text-xs text-zinc-500 font-mono">{{ m.no_wa || '—' }}</p>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Status Keanggotaan -->
                                <TableCell>
                                    <div class="flex flex-col gap-1 items-start">
                                        <Badge
                                            v-if="m.status_keanggotaan === 'LIFE MEMBER'"
                                            class="bg-amber-500/15 text-amber-700 border-amber-300 dark:bg-amber-950/30 dark:text-amber-300 font-bold text-[10px]"
                                            variant="outline"
                                        >
                                            ⭐ LIFE MEMBER
                                        </Badge>
                                        <Badge
                                            v-else-if="m.status_keanggotaan === 'SS DIPONEGORO'"
                                            class="bg-blue-500/15 text-blue-700 border-blue-300 dark:bg-blue-950/30 dark:text-blue-300 font-bold text-[10px]"
                                            variant="outline"
                                        >
                                            🛡️ SS DIPONEGORO
                                        </Badge>
                                        <Badge
                                            v-else
                                            variant="secondary"
                                            class="text-[10px] text-zinc-600"
                                        >
                                            {{ m.status_keanggotaan || '—' }}
                                        </Badge>

                                        <!-- Penalty Indicator -->
                                        <span 
                                            v-if="m.penalty && m.penalty !== 'clean'"
                                            class="text-[10px] font-bold text-red-600 bg-red-50 dark:bg-red-950/40 px-1.5 py-0.5 rounded border border-red-200"
                                        >
                                            PENALTY: {{ m.penalty.toUpperCase() }}
                                        </span>
                                    </div>
                                </TableCell>

                                <!-- Chapter / Wilayah -->
                                <TableCell class="text-xs text-zinc-600 dark:text-zinc-400">
                                    <div class="font-medium text-zinc-800 dark:text-zinc-200">{{ m.chapter || '—' }}</div>
                                    <div v-if="m.checkpoint" class="text-[11px] text-zinc-500">{{ m.checkpoint }}</div>
                                </TableCell>

                                <!-- Status Hak Suara -->
                                <TableCell class="text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span 
                                            v-if="isEligible(m)"
                                            class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 px-2.5 py-1 rounded-full border border-emerald-200 dark:border-emerald-900"
                                        >
                                            <Check class="h-3 w-3 text-emerald-600" />
                                            Berhak Memilih
                                        </span>
                                        <span 
                                            v-else
                                            class="inline-flex items-center gap-1 text-[10px] font-semibold text-zinc-500 bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded-full"
                                        >
                                            Tidak Berhak
                                        </span>
                                    </div>
                                </TableCell>

                                <!-- Status Pemilihan (Online vs Offline) -->
                                <TableCell class="text-center">
                                    <div class="inline-flex flex-col gap-1 items-center">
                                        <!-- Status Offline -->
                                        <div v-if="m.offline_voter">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-600 text-white shadow-xs">
                                                <Vote class="h-3.5 w-3.5" />
                                                PEMILIH OFFLINE
                                            </span>
                                        </div>
                                        <div v-else>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium text-zinc-500 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                                                Bukan Offline
                                            </span>
                                        </div>

                                        <!-- Status Online -->
                                        <div v-if="m.pollings_exists">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/40 px-2 py-0.5 rounded border border-blue-200">
                                                <CheckCircle2 class="h-3 w-3 text-blue-600" />
                                                Sudah Vote Online
                                            </span>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Actions -->
                                <TableCell class="text-right">
                                    <!-- Jika sudah offline voter: Tombol Batalkan -->
                                    <div v-if="m.offline_voter" class="flex justify-end">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="openUnmarkModal(m)"
                                            class="h-8 text-xs font-semibold border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700 dark:border-red-900/60 dark:hover:bg-red-950/40"
                                        >
                                            <XCircle class="h-3.5 w-3.5 mr-1" />
                                            Batalkan Offline
                                        </Button>
                                    </div>

                                    <!-- Jika belum offline voter: Cek apakah sudah vote online -->
                                    <div v-else class="flex justify-end">
                                        <Button
                                            v-if="m.pollings_exists"
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            disabled
                                            class="h-8 text-xs font-medium opacity-60 cursor-not-allowed"
                                            title="Anggota sudah memberikan suara secara online"
                                        >
                                            Sudah Vote Online
                                        </Button>
                                        
                                        <Button
                                            v-else
                                            type="button"
                                            size="sm"
                                            @click="openMarkModal(m)"
                                            class="h-8 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs"
                                        >
                                            <Vote class="h-3.5 w-3.5 mr-1" />
                                            Tandai Pemilih Offline
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination Bar -->
                <div 
                    v-if="members.total > 0"
                    class="flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900"
                >
                    <div class="text-xs text-zinc-500">
                        Menampilkan <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ members.from || 0 }}</span> - <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ members.to || 0 }}</span> dari <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ members.total }}</span> anggota
                    </div>

                    <div class="flex items-center gap-1.5">
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-8 w-8 p-0"
                            :disabled="members.current_page <= 1"
                            @click="applyFilters({ page: 1 })"
                            title="Halaman Pertama"
                        >
                            <ChevronsLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-8 w-8 p-0"
                            :disabled="members.current_page <= 1"
                            @click="applyFilters({ page: members.current_page - 1 })"
                            title="Sebelumnya"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        
                        <span class="px-3 text-xs font-medium text-zinc-600 dark:text-zinc-400">
                            Hal. {{ members.current_page }} dari {{ members.last_page }}
                        </span>

                        <Button
                            variant="outline"
                            size="sm"
                            class="h-8 w-8 p-0"
                            :disabled="members.current_page >= members.last_page"
                            @click="applyFilters({ page: members.current_page + 1 })"
                            title="Selanjutnya"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-8 w-8 p-0"
                            :disabled="members.current_page >= members.last_page"
                            @click="applyFilters({ page: members.last_page })"
                            title="Halaman Terakhir"
                        >
                            <ChevronsRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Confirmation Dialog -->
            <Dialog :open="!!targetMember" @update:open="(val) => !val && closeModal()">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full mb-2" :class="modalAction === 'mark' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'">
                            <Vote v-if="modalAction === 'mark'" class="h-6 w-6" />
                            <AlertTriangle v-else class="h-6 w-6" />
                        </div>
                        <DialogTitle class="text-center text-lg font-bold">
                            {{ modalAction === 'mark' ? 'Konfirmasi Tandai Pemilih Offline' : 'Batalkan Status Pemilih Offline' }}
                        </DialogTitle>
                        <DialogDescription class="text-center text-xs text-zinc-500 mt-1">
                            Verifikasi data anggota sebelum melanjutkan aksi
                        </DialogDescription>
                    </DialogHeader>

                    <div v-if="targetMember" class="space-y-4 py-2">
                        <!-- Member Summary Card -->
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50 p-4 space-y-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-zinc-500">Nama Anggota:</span>
                                <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ targetMember.nama_lengkap }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-zinc-500">No. KTA:</span>
                                <span class="font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ targetMember.no_kartu ? formatKta(targetMember.no_kartu) : '—' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-zinc-500">Status Keanggotaan:</span>
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ targetMember.status_keanggotaan }}</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-zinc-500">Chapter:</span>
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ targetMember.chapter }}</span>
                            </div>
                        </div>

                        <!-- Consequence Warning Banner -->
                        <div 
                            v-if="modalAction === 'mark'"
                            class="rounded-xl border border-amber-300 bg-amber-50 p-3.5 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300 flex items-start gap-2.5"
                        >
                            <AlertTriangle class="h-4.5 w-4.5 text-amber-600 shrink-0 mt-0.5" />
                            <div class="space-y-1">
                                <p class="font-bold">Konsekuensi Penandaan Offline:</p>
                                <p class="leading-relaxed">
                                    Setelah ditandai, anggota ini <strong>TIDAK AKAN BISA</strong> login untuk memilih secara online di portal Pra-Election.
                                </p>
                            </div>
                        </div>

                        <div 
                            v-else
                            class="rounded-xl border border-blue-200 bg-blue-50 p-3.5 text-xs text-blue-900 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-300 flex items-start gap-2.5"
                        >
                            <ShieldCheck class="h-4.5 w-4.5 text-blue-600 shrink-0 mt-0.5" />
                            <div class="space-y-1">
                                <p class="font-bold">Pembatalan Pemilih Offline:</p>
                                <p class="leading-relaxed">
                                    Status offline akan dicabut. Anggota ini dapat kembali login memilih online jika memenuhi persyaratan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="flex flex-row gap-2 justify-end sm:justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="closeModal"
                            :disabled="isProcessing"
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :disabled="isProcessing"
                            :class="modalAction === 'mark' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white'"
                            @click="confirmAction"
                        >
                            <RefreshCw v-if="isProcessing" class="h-4 w-4 animate-spin mr-1.5" />
                            <span>{{ modalAction === 'mark' ? 'Ya, Tandai Pemilih Offline' : 'Ya, Batalkan Status Offline' }}</span>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AppLayout>
</template>
