<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
    Dialog, DialogContent, DialogDescription,
    DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import {
    Search, RefreshCw, Vote, Users, AlertTriangle, ChevronLeft, ChevronRight,
    ArrowUpDown, UserX, Check, ExternalLink, Pencil, Trash2, ShieldCheck,
    Clock, CheckCircle2, XCircle, AlertCircle, PlusCircle, FileText, Eye, Upload
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Member {
    id: number;
    nama_lengkap: string;
    nama_panggilan?: string;
    no_kartu: string;
    no_wa?: string;
    status_keanggotaan: string;
    chapter: string;
    checkpoint?: string;
    region?: string;
    foto?: string;
    pollings_exists?: boolean;
}

interface OfflineLogItem {
    id: number;
    nama_pengurus: string;
    kode_akses: string;
    member_id: number;
    no_antrian: number;
    no_tps?: string | null;
    voting_status: 'antrean' | 'sudah_memilih' | 'tidak_memilih' | 'cancel_vote';
    created_at: string;
    updated_at: string;
    member?: Member;
}

interface CandidateOption {
    id: number;
    no_urut?: number | null;
    nama: string;
    chapter?: string;
}

interface RekapOfflineItem {
    id: number;
    calon_id?: number | null;
    jumlah_suara: number;
    penanggung_jawab: string;
    foto_bukti?: string | null;
    foto_bukti_url?: string | null;
    keterangan?: string | null;
    created_at: string;
    calon?: {
        id: number;
        no_urut?: number | null;
        member?: {
            nama_lengkap: string;
        };
    } | null;
}

interface Stats {
    total_offline_voters: number;
    total_antrean: number;
    total_sudah_memilih: number;
    total_tidak_memilih: number;
    total_batal: number;
    total_online_voted?: number;
    total_rekap_offline?: number;
    total_rekap_count?: number;
}

const props = defineProps<{
    logs?: {
        data: OfflineLogItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    members?: any;
    stats: Stats;
    rekap_offline?: RekapOfflineItem[];
    candidates?: CandidateOption[];
    filters: {
        search?: string;
        voting_status?: string;
        status_keanggotaan?: string;
        sort_by?: string;
        sort_dir?: string;
    };
    status_labels?: Record<string, string>;
}>();

const page = usePage();
const flash = computed(() => (page.props.flash as Record<string, any>) ?? {});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pemilihan Offline', href: '/pemilihan-offline' },
];

const logData = computed(() => {
    return props.logs?.data || [];
});

const paginationInfo = computed(() => {
    return props.logs || {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
        from: 0,
        to: 0,
        links: [],
    };
});

// ── Search & Filter State ───────────────────────────────────────────────────
const search = ref(props.filters?.search ?? '');
const votingStatus = ref(props.filters?.voting_status ?? 'all');
const statusKeanggotaan = ref(props.filters?.status_keanggotaan ?? 'all');
const sortBy = ref(props.filters?.sort_by ?? '');
const sortDir = ref(props.filters?.sort_dir ?? '');

let searchTimer: any;
watch([search, votingStatus, statusKeanggotaan], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters({ page: 1 });
    }, 350);
});

function applyFilters(overrides: Record<string, any> = {}) {
    router.get('/pemilihan-offline', {
        search: overrides.search !== undefined ? overrides.search : search.value,
        voting_status: overrides.voting_status !== undefined ? overrides.voting_status : votingStatus.value,
        status_keanggotaan: overrides.status_keanggotaan !== undefined ? overrides.status_keanggotaan : statusKeanggotaan.value,
        sort_by: overrides.sort_by !== undefined ? overrides.sort_by : sortBy.value,
        sort_dir: overrides.sort_dir !== undefined ? overrides.sort_dir : sortDir.value,
        page: overrides.page ?? paginationInfo.value.current_page,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearFilters() {
    search.value = '';
    votingStatus.value = 'all';
    statusKeanggotaan.value = 'all';
    sortBy.value = '';
    sortDir.value = '';
    applyFilters({ search: '', voting_status: 'all', status_keanggotaan: 'all', sort_by: '', sort_dir: '', page: 1 });
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

function formatKta(noKartu?: string) {
    if (!noKartu) return '—';
    return `BBMC 38 2026 ${String(noKartu).padStart(4, '0')}`;
}

// ── Edit Modal State & Form ──────────────────────────────────────────────────
const isEditOpen = ref(false);
const editingLog = ref<OfflineLogItem | null>(null);

const editForm = useForm({
    no_antrian: 1,
    no_tps: '',
    nama_pengurus: '',
    voting_status: 'antrean',
});

function openEditModal(log: OfflineLogItem) {
    editingLog.value = log;
    editForm.no_antrian = log.no_antrian;
    editForm.no_tps = log.no_tps || '';
    editForm.nama_pengurus = log.nama_pengurus;
    editForm.voting_status = log.voting_status;
    editForm.clearErrors();
    isEditOpen.value = true;
}

function submitEdit() {
    if (!editingLog.value) return;

    editForm.put(`/pemilihan-offline/${editingLog.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditOpen.value = false;
            editingLog.value = null;
        },
    });
}

// ── Delete Modal State ───────────────────────────────────────────────────────
const isDeleteOpen = ref(false);
const deletingLog = ref<OfflineLogItem | null>(null);
const isDeleting = ref(false);

function openDeleteModal(log: OfflineLogItem) {
    deletingLog.value = log;
    isDeleteOpen.value = true;
}

function confirmDelete() {
    if (!deletingLog.value) return;

    isDeleting.value = true;
    router.delete(`/pemilihan-offline/${deletingLog.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            isDeleteOpen.value = false;
            deletingLog.value = null;
        },
    });
}

// ── Rekapitulasi Suara Offline State & Form ──────────────────────────────────
const isRekapModalOpen = ref(false);
const previewUrl = ref<string | null>(null);

const rekapForm = useForm<{
    calon_id: string | number;
    jumlah_suara: number | string;
    penanggung_jawab: string;
    keterangan: string;
    foto_bukti: File | null;
}>({
    calon_id: '',
    jumlah_suara: '',
    penanggung_jawab: '',
    keterangan: '',
    foto_bukti: null,
});

function openRekapModal() {
    rekapForm.reset();
    rekapForm.clearErrors();
    previewUrl.value = null;
    isRekapModalOpen.value = true;
}

function handleFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        rekapForm.foto_bukti = file;
        if (file.type.startsWith('image/')) {
            previewUrl.value = URL.createObjectURL(file);
        } else {
            previewUrl.value = null;
        }
    } else {
        rekapForm.foto_bukti = null;
        previewUrl.value = null;
    }
}

function submitRekap() {
    rekapForm.post('/pemilihan-offline/rekap', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isRekapModalOpen.value = false;
            rekapForm.reset();
            previewUrl.value = null;
        },
    });
}

// ── Delete Rekap Modal State ─────────────────────────────────────────────────
const isDeleteRekapOpen = ref(false);
const deletingRekap = ref<RekapOfflineItem | null>(null);
const isDeletingRekap = ref(false);

function openDeleteRekapModal(item: RekapOfflineItem) {
    deletingRekap.value = item;
    isDeleteRekapOpen.value = true;
}

function confirmDeleteRekap() {
    if (!deletingRekap.value) return;

    isDeletingRekap.value = true;
    router.delete(`/pemilihan-offline/rekap/${deletingRekap.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeletingRekap.value = false;
            isDeleteRekapOpen.value = false;
            deletingRekap.value = null;
        },
    });
}

// ── Bukti Viewer Modal State ─────────────────────────────────────────────────
const isBuktiViewerOpen = ref(false);
const viewingBukti = ref<{ url: string; title: string } | null>(null);

function openBuktiViewer(item: RekapOfflineItem) {
    if (!item.foto_bukti_url) return;
    viewingBukti.value = {
        url: item.foto_bukti_url,
        title: `Bukti Rekap: ${item.penanggung_jawab} (${item.jumlah_suara} Suara)`,
    };
    isBuktiViewerOpen.value = true;
}

// Helper status badge classes
function statusBadgeClass(status: string) {
    if (status === 'antrean') return 'bg-amber-100 text-amber-800 border-amber-300';
    if (status === 'sudah_memilih') return 'bg-green-100 text-green-800 border-green-300';
    if (status === 'tidak_memilih') return 'bg-gray-100 text-gray-800 border-gray-300';
    return 'bg-red-100 text-red-800 border-red-300';
}

function statusIcon(status: string) {
    if (status === 'antrean') return '⏳';
    if (status === 'sudah_memilih') return '✅';
    if (status === 'tidak_memilih') return '⛔';
    return '❌';
}

function statusLabel(status: string) {
    if (status === 'antrean') return 'Dalam Antrean';
    if (status === 'sudah_memilih') return 'Sudah Memilih';
    if (status === 'tidak_memilih') return 'Tidak Memilih';
    if (status === 'cancel_vote') return 'Batal Memilih';
    return status;
}

function formatTime(iso: string) {
    if (!iso) return '—';
    try {
        const d = new Date(iso);
        return d.toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
    } catch {
        return iso;
    }
}
</script>

<template>
    <Head title="Pemilihan Offline" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">

            <!-- Flash & Error Alerts -->
            <div
                v-if="flash.success"
                class="flex items-center gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-300 shadow-sm animate-in fade-in"
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
                        Daftar kehadiran anggota dan rekapitulasi perolehan suara pemilihan di lokasi TPS.
                    </p>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2 shrink-0 flex-wrap">
                    <Button
                        type="button"
                        @click="openRekapModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs tracking-wide shadow-sm transition-all active:scale-95"
                    >
                        <PlusCircle class="h-4 w-4" />
                        <span>Rekap Suara Offline</span>
                    </Button>

                    <a
                        href="/offline/verify"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white font-bold text-xs tracking-wide shadow-sm transition-all active:scale-95 border border-zinc-700"
                    >
                        <ShieldCheck class="h-4 w-4" />
                        <span>Verifikasi Pemilih</span>
                        <ExternalLink class="h-3.5 w-3.5 opacity-80" />
                    </a>
                </div>
            </div>

            <!-- Notice Banner: Info Pemilihan Offline -->
            <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-4 text-xs text-blue-900 flex items-start gap-3">
                <AlertCircle class="h-5 w-5 text-blue-600 shrink-0 mt-0.5" />
                <div class="space-y-0.5">
                    <p class="font-bold">Panduan Pemilihan Offline & Rekapitulasi Suara:</p>
                    <p class="text-blue-800 leading-relaxed">
                        Petugas di TPS melayani verifikasi kehadiran pemilih secara langsung di lokasi. Setelah pemungutan suara selesai, gunakan tombol <strong>Rekap Suara Offline</strong> untuk mencatat hasil penghitungan fisik surat suara.
                    </p>
                </div>
            </div>

            <!-- Statistics Overview Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Total Pemilih Offline -->
                <div class="rounded-2xl border border-red-200/60 bg-red-50/50 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-red-700">Kehadiran di TPS</span>
                        <div class="p-2 rounded-xl bg-red-600 text-white shadow-sm">
                            <Vote class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-black text-red-950">{{ stats.total_offline_voters }}</span>
                        <span class="text-xs text-red-600 ml-1">orang</span>
                    </div>
                </div>

                <!-- Total Suara Rekap Offline -->
                <div class="rounded-2xl border border-rose-200/60 bg-gradient-to-br from-rose-50/80 to-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-rose-700">Total Suara Rekap</span>
                        <div class="p-2 rounded-xl bg-rose-600 text-white shadow-sm">
                            <PlusCircle class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-black text-rose-950">{{ stats.total_rekap_offline || 0 }}</span>
                        <span class="text-xs text-rose-600 ml-1">suara</span>
                    </div>
                </div>

                <!-- Dalam Antrean -->
                <div class="rounded-2xl border border-amber-200/60 bg-amber-50/50 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-amber-700">Dalam Antrean</span>
                        <div class="p-2 rounded-xl bg-amber-500 text-white shadow-sm">
                            <Clock class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-black text-amber-950">{{ stats.total_antrean }}</span>
                        <span class="text-xs text-amber-600 ml-1">menunggu</span>
                    </div>
                </div>

                <!-- Sudah Memilih -->
                <div class="rounded-2xl border border-emerald-200/60 bg-emerald-50/50 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Sudah Memilih</span>
                        <div class="p-2 rounded-xl bg-emerald-600 text-white shadow-sm">
                            <CheckCircle2 class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-black text-emerald-950">{{ stats.total_sudah_memilih }}</span>
                        <span class="text-xs text-emerald-600 ml-1">suara masuk</span>
                    </div>
                </div>

                <!-- Tidak Memilih -->
                <div class="rounded-2xl border border-zinc-200/80 bg-zinc-50/80 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-zinc-600">Tidak Memilih</span>
                        <div class="p-2 rounded-xl bg-zinc-500 text-white shadow-sm">
                            <XCircle class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-black text-zinc-900">{{ stats.total_tidak_memilih }}</span>
                        <span class="text-xs text-zinc-500 ml-1">absen</span>
                    </div>
                </div>

                <!-- Batal Vote -->
                <div class="rounded-2xl border border-purple-200/60 bg-purple-50/50 p-4 shadow-sm col-span-2 lg:col-span-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-purple-700">Batal Memilih</span>
                        <div class="p-2 rounded-xl bg-purple-600 text-white shadow-sm">
                            <AlertTriangle class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-black text-purple-950">{{ stats.total_batal }}</span>
                        <span class="text-xs text-purple-600 ml-1">batal</span>
                    </div>
                </div>
            </div>

            <!-- Rekapitulasi Suara Offline Section -->
            <div class="rounded-2xl border border-red-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-red-100 dark:border-zinc-800 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <Vote class="h-5 w-5 text-red-600" />
                            <h2 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                Rekapitulasi Suara Offline
                            </h2>
                            <Badge variant="outline" class="bg-red-50 text-red-700 border-red-200 font-mono text-xs font-bold">
                                {{ stats.total_rekap_offline || 0 }} Suara
                            </Badge>
                        </div>
                        <p class="text-xs text-zinc-500 mt-1">
                            Perolehan suara dari penghitungan surat suara fisik di TPS yang otomatis terhitung ke hasil suara pemilihan.
                        </p>
                    </div>

                    <Button
                        type="button"
                        @click="openRekapModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs tracking-wide shadow-sm shrink-0 self-start sm:self-auto"
                    >
                        <PlusCircle class="h-4 w-4" />
                        <span>Rekap Suara Offline</span>
                    </Button>
                </div>

                <!-- Rekap Table / Empty State -->
                <div v-if="!rekap_offline || rekap_offline.length === 0" class="text-center py-8 px-4 rounded-xl border border-dashed border-zinc-200 bg-zinc-50/50 dark:border-zinc-800 dark:bg-zinc-800/30">
                    <FileText class="h-8 w-8 text-zinc-400 mx-auto mb-2" />
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Belum Ada Rekapitulasi Suara Offline</p>
                    <p class="text-xs text-zinc-500 mt-1 max-w-sm mx-auto">
                        Klik tombol "Rekap Suara Offline" untuk mencatat hasil penghitungan suara dari TPS.
                    </p>
                </div>

                <div v-else class="overflow-x-auto rounded-xl border border-zinc-100 dark:border-zinc-800">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-zinc-50/75 dark:bg-zinc-800/50">
                                <TableHead class="font-bold text-xs uppercase tracking-wider">Tanggal & Waktu</TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider">Kandidat Terkait</TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider text-center">Jumlah Suara</TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider">Penanggung Jawab</TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider">Keterangan / TPS</TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider text-center">Bukti / Foto</TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in rekap_offline" :key="item.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50">
                                <TableCell class="font-mono text-xs text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                    {{ formatTime(item.created_at) }}
                                </TableCell>
                                <TableCell class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                    <div v-if="item.calon" class="flex items-center gap-1.5">
                                        <Badge v-if="item.calon.no_urut" class="bg-amber-500 text-white text-[10px] px-1.5 py-0 font-mono">
                                            #{{ item.calon.no_urut }}
                                        </Badge>
                                        <span>{{ item.calon.member?.nama_lengkap || `Calon #${item.calon.id}` }}</span>
                                    </div>
                                    <span v-else class="text-zinc-400 italic">Umum / Semua Calon</span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black font-mono bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400">
                                        +{{ item.jumlah_suara }} Suara
                                    </span>
                                </TableCell>
                                <TableCell class="text-xs text-zinc-700 dark:text-zinc-300 font-medium">
                                    {{ item.penanggung_jawab }}
                                </TableCell>
                                <TableCell class="text-xs text-zinc-500 max-w-xs truncate">
                                    {{ item.keterangan || '—' }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <div v-if="item.foto_bukti_url" class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="openBuktiViewer(item)"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 hover:text-red-700 hover:underline"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                            <span>Lihat Bukti</span>
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-zinc-400 italic">—</span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="openDeleteRekapModal(item)"
                                        class="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/40"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <!-- Search input -->
                    <div class="relative flex-1 max-w-md">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input
                            v-model="search"
                            type="text"
                            placeholder="Cari no antrean, KTA, nama, TPS, petugas..."
                            class="pl-10 h-11 text-sm bg-zinc-50/50 dark:bg-zinc-800/50"
                        />
                    </div>

                    <!-- Filter dropdowns -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Status Pemilihan -->
                        <select
                            v-model="votingStatus"
                            class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-zinc-700 outline-none focus:border-red-400 focus:ring-1 focus:ring-red-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            <option value="all">Semua Status Pemilihan</option>
                            <option value="antrean">⏳ Dalam Antrean</option>
                            <option value="sudah_memilih">✅ Sudah Memilih</option>
                            <option value="tidak_memilih">⛔ Tidak Memilih</option>
                            <option value="cancel_vote">❌ Batal Memilih</option>
                        </select>

                        <!-- Status Keanggotaan -->
                        <select
                            v-model="statusKeanggotaan"
                            class="h-11 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-semibold text-zinc-700 outline-none focus:border-red-400 focus:ring-1 focus:ring-red-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            <option value="all">Semua Keanggotaan</option>
                            <option value="LIFE MEMBER">LIFE MEMBER</option>
                            <option value="SS DIPONEGORO">SS DIPONEGORO</option>
                            <option value="HONORARY">HONORARY</option>
                            <option value="VIRGIN">VIRGIN</option>
                            <option value="PROSPECT">PROSPECT</option>
                        </select>

                        <!-- Clear filter -->
                        <Button
                            v-if="search || votingStatus !== 'all' || statusKeanggotaan !== 'all'"
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

            <!-- Offline Logs Table Card -->
            <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-zinc-50/75 dark:bg-zinc-800/50 hover:bg-zinc-50/75">
                                <TableHead class="w-[120px] font-bold text-xs uppercase tracking-wider text-center">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900"
                                        @click="toggleSort('no_antrian')"
                                    >
                                        <span>Antrean</span>
                                        <ArrowUpDown class="h-3.5 w-3.5" />
                                    </button>
                                </TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider">
                                    Anggota & No. KTA
                                </TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider">
                                    Chapter / Status
                                </TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider text-center">
                                    TPS
                                </TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider text-center">
                                    Status Pemilihan
                                </TableHead>
                                <TableHead class="font-bold text-xs uppercase tracking-wider">
                                    Petugas Verifikasi
                                </TableHead>
                                <TableHead class="w-[160px] text-right font-bold text-xs uppercase tracking-wider">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-if="logData.length === 0">
                                <TableCell colspan="7" class="text-center py-12 text-zinc-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <UserX class="h-8 w-8 text-zinc-300" />
                                        <p class="font-medium text-sm">Belum ada data pemilih offline yang sesuai.</p>
                                        <p class="text-xs text-zinc-400">
                                            Data akan bertambah saat pemilih hadir dan diverifikasi oleh petugas di TPS.
                                        </p>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow
                                v-for="log in logData"
                                :key="log.id"
                                class="transition-colors hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40"
                            >
                                <!-- No. Antrean -->
                                <TableCell class="text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-red-100 text-red-800 font-black text-sm border border-red-200">
                                        #{{ log.no_antrian }}
                                    </span>
                                </TableCell>

                                <!-- Anggota & KTA -->
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="relative shrink-0">
                                            <img
                                                v-if="log.member?.foto"
                                                :src="'/storage/' + log.member.foto"
                                                :alt="log.member.nama_lengkap"
                                                class="h-10 w-10 rounded-full object-cover border border-zinc-200 dark:border-zinc-700"
                                            />
                                            <div
                                                v-else
                                                class="h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center font-bold text-zinc-500 text-xs uppercase"
                                            >
                                                {{ (log.member?.nama_lengkap || 'M').substring(0, 2) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-bold text-sm text-zinc-900 dark:text-zinc-100">
                                                {{ log.member ? log.member.nama_lengkap : 'Anggota #' + log.member_id }}
                                            </div>
                                            <div class="font-mono text-xs font-bold text-red-600 mt-0.5">
                                                {{ formatKta(log.member?.no_kartu) }}
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Chapter / Keanggotaan -->
                                <TableCell>
                                    <div class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ log.member?.chapter || '—' }}
                                    </div>
                                    <div class="text-[11px] text-zinc-500">
                                        {{ log.member?.status_keanggotaan || '—' }}
                                    </div>
                                </TableCell>

                                <!-- TPS -->
                                <TableCell class="text-center">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700">
                                        {{ log.no_tps || '—' }}
                                    </span>
                                </TableCell>

                                <!-- Status Pemilihan -->
                                <TableCell class="text-center">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide border shadow-xs"
                                        :class="statusBadgeClass(log.voting_status)"
                                    >
                                        <span>{{ statusIcon(log.voting_status) }}</span>
                                        <span>{{ statusLabel(log.voting_status) }}</span>
                                    </span>
                                </TableCell>

                                <!-- Petugas Verifikasi -->
                                <TableCell>
                                    <div class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                                        {{ log.nama_pengurus }}
                                    </div>
                                    <div class="text-[10px] text-zinc-400 font-mono">
                                        {{ formatTime(log.created_at) }}
                                    </div>
                                </TableCell>

                                <!-- Aksi (Edit & Hapus) -->
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Edit Button -->
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="openEditModal(log)"
                                            class="h-8 px-2.5 text-xs font-semibold text-zinc-700 hover:text-zinc-900 hover:bg-zinc-100 border-zinc-200"
                                            title="Edit Data Pemilih TPS"
                                        >
                                            <Pencil class="h-3.5 w-3.5 mr-1 text-zinc-500" />
                                            Edit
                                        </Button>

                                        <!-- Delete Button -->
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="openDeleteModal(log)"
                                            class="h-8 px-2.5 text-xs font-semibold text-red-600 hover:bg-red-50 hover:text-red-700 border-red-200"
                                            title="Hapus Data Pemilih TPS"
                                        >
                                            <Trash2 class="h-3.5 w-3.5 mr-1" />
                                            Hapus
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="paginationInfo.links && paginationInfo.links.length > 3"
                    class="p-4 border-t border-zinc-200/80 bg-zinc-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-zinc-600"
                >
                    <div>
                        Menampilkan <strong>{{ paginationInfo.from || 0 }}</strong> - <strong>{{ paginationInfo.to || 0 }}</strong> dari <strong>{{ paginationInfo.total || 0 }}</strong> pemilih
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            v-for="(link, idx) in paginationInfo.links"
                            :key="idx"
                            @click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })"
                            :disabled="!link.url || link.active"
                            v-html="link.label"
                            class="px-2.5 py-1 rounded border transition-colors"
                            :class="link.active ? 'bg-red-600 text-white font-bold border-red-600' : (!link.url ? 'opacity-40 cursor-not-allowed border-zinc-200' : 'bg-white hover:bg-zinc-100 text-zinc-700 border-zinc-200')"
                        />
                    </div>
                </div>
            </div>

        </div>

        <!-- ── MODAL EDIT DATA PEMILIH OFFLINE ──────────────────────────────── -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Pencil class="h-5 w-5 text-red-600" />
                        <span>Edit Data Pemilih TPS</span>
                    </DialogTitle>
                    <DialogDescription>
                        Ubah nomor antrean atau status kehadiran untuk anggota <strong>{{ editingLog?.member?.nama_lengkap }}</strong> ({{ formatKta(editingLog?.member?.no_kartu) }}).
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-4 py-2">
                    <!-- Nomor Antrean -->
                    <div>
                        <Label for="edit_no_antrian" class="text-xs font-semibold">Nomor Antrean *</Label>
                        <Input
                            id="edit_no_antrian"
                            type="number"
                            min="1"
                            v-model="editForm.no_antrian"
                            class="mt-1"
                            required
                        />
                        <p v-if="editForm.errors.no_antrian" class="text-xs text-red-500 mt-1">{{ editForm.errors.no_antrian }}</p>
                    </div>

                    <!-- TPS -->
                    <div>
                        <Label for="edit_no_tps" class="text-xs font-semibold">Nomor / Lokasi TPS</Label>
                        <Input
                            id="edit_no_tps"
                            type="text"
                            placeholder="Cth: TPS 1"
                            v-model="editForm.no_tps"
                            class="mt-1"
                        />
                        <p v-if="editForm.errors.no_tps" class="text-xs text-red-500 mt-1">{{ editForm.errors.no_tps }}</p>
                    </div>

                    <!-- Petugas Verifikasi -->
                    <div>
                        <Label for="edit_nama_pengurus" class="text-xs font-semibold">Nama Petugas TPS *</Label>
                        <Input
                            id="edit_nama_pengurus"
                            type="text"
                            v-model="editForm.nama_pengurus"
                            class="mt-1"
                            required
                        />
                        <p v-if="editForm.errors.nama_pengurus" class="text-xs text-red-500 mt-1">{{ editForm.errors.nama_pengurus }}</p>
                    </div>

                    <!-- Status Pemilihan -->
                    <div>
                        <Label for="edit_voting_status" class="text-xs font-semibold">Status Pemilihan *</Label>
                        <select
                            id="edit_voting_status"
                            v-model="editForm.voting_status"
                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 outline-none focus:border-red-400 focus:ring-1 focus:ring-red-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            <option value="antrean">⏳ Dalam Antrean</option>
                            <option value="sudah_memilih">✅ Sudah Memilih</option>
                            <option value="tidak_memilih">⛔ Tidak Memilih</option>
                            <option value="cancel_vote">❌ Batal Memilih</option>
                        </select>
                        <p v-if="editForm.errors.voting_status" class="text-xs text-red-500 mt-1">{{ editForm.errors.voting_status }}</p>
                    </div>

                    <DialogFooter class="pt-4 flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isEditOpen = false"
                            class="text-xs font-semibold"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold"
                        >
                            {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ── MODAL KONFIRMASI HAPUS ──────────────────────────────────────── -->
        <Dialog :open="isDeleteOpen" @update:open="isDeleteOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-red-600">
                        <AlertTriangle class="h-5 w-5" />
                        <span>Hapus Data Pemilih TPS?</span>
                    </DialogTitle>
                    <DialogDescription class="pt-2 text-zinc-600 leading-relaxed">
                        Apakah Anda yakin ingin menghapus data antrean <strong>#{{ deletingLog?.no_antrian }}</strong> untuk anggota <strong>{{ deletingLog?.member?.nama_lengkap }}</strong>?
                        <br />
                        <span class="block mt-2 text-xs text-zinc-500">
                            Jika dihapus, anggota dapat didaftarkan kembali di lokasi TPS atau dapat memilih secara online.
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="pt-4 flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isDeleteOpen = false"
                        :disabled="isDeleting"
                        class="text-xs font-semibold"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        @click="confirmDelete"
                        :disabled="isDeleting"
                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold"
                    >
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Data' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── MODAL REKAPITULASI SUARA OFFLINE ────────────────────────────── -->
        <Dialog :open="isRekapModalOpen" @update:open="isRekapModalOpen = $event">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-zinc-900 dark:text-zinc-100 font-bold">
                        <Vote class="h-5 w-5 text-red-600" />
                        <span>Rekapitulasi Suara Offline</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs text-zinc-500">
                        Masukkan perolehan suara dari penghitungan surat suara fisik di TPS. Hasil ini otomatis ditambahkan ke total suara pemilihan.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitRekap" class="space-y-4 pt-2">
                    <!-- Kandidat Terkait -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                            Pilih Calon (Opsional)
                        </Label>
                        <select
                            v-model="rekapForm.calon_id"
                            class="w-full h-10 rounded-lg border border-zinc-200 bg-white px-3 text-xs font-medium text-zinc-800 outline-none focus:border-red-400 focus:ring-1 focus:ring-red-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            <option value="">Umum (Tanpa Calon Khusus)</option>
                            <option
                                v-for="c in candidates"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.no_urut ? `No. #${c.no_urut} - ` : '' }}{{ c.nama }}{{ c.chapter ? ` (${c.chapter})` : '' }}
                            </option>
                        </select>
                        <p class="text-[11px] text-zinc-400">
                            Jika calon dipilih, suara akan otomatis menambah perolehan suara calon tersebut pada hasil pemilihan.
                        </p>
                    </div>

                    <!-- Jumlah Suara -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                            Jumlah Suara <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            type="number"
                            min="1"
                            v-model="rekapForm.jumlah_suara"
                            placeholder="Contoh: 50"
                            required
                            class="h-10 text-xs font-mono font-bold"
                        />
                        <p v-if="rekapForm.errors.jumlah_suara" class="text-xs text-red-600">
                            {{ rekapForm.errors.jumlah_suara }}
                        </p>
                    </div>

                    <!-- Penanggung Jawab -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                            Nama Penanggung Jawab / Saksi TPS <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            type="text"
                            v-model="rekapForm.penanggung_jawab"
                            placeholder="Contoh: Kang Bambang (Saksi TPS 1)"
                            required
                            class="h-10 text-xs"
                        />
                        <p v-if="rekapForm.errors.penanggung_jawab" class="text-xs text-red-600">
                            {{ rekapForm.errors.penanggung_jawab }}
                        </p>
                    </div>

                    <!-- Keterangan / TPS -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                            Lokasi TPS / Catatan (Opsional)
                        </Label>
                        <Input
                            type="text"
                            v-model="rekapForm.keterangan"
                            placeholder="Contoh: TPS 1 Lapangan Gasibu Bandung"
                            class="h-10 text-xs"
                        />
                    </div>

                    <!-- Foto / Bukti Dokumen -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                            Foto Bukti / Berita Acara (Opsional)
                        </Label>
                        <Input
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/webp,application/pdf"
                            @change="handleFileChange"
                            class="h-10 text-xs cursor-pointer file:text-xs file:font-semibold"
                        />
                        <p class="text-[11px] text-zinc-400">
                            Format foto (JPG, PNG) atau dokumen PDF, maksimal 10 MB.
                        </p>
                        <!-- Preview Thumbnail -->
                        <div v-if="previewUrl" class="mt-2 relative inline-block rounded-lg overflow-hidden border border-zinc-200">
                            <img :src="previewUrl" alt="Preview Bukti" class="h-28 w-auto object-cover" />
                        </div>
                        <p v-if="rekapForm.errors.foto_bukti" class="text-xs text-red-600">
                            {{ rekapForm.errors.foto_bukti }}
                        </p>
                    </div>

                    <DialogFooter class="pt-3 flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isRekapModalOpen = false"
                            class="text-xs font-semibold"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            :disabled="rekapForm.processing"
                            class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold"
                        >
                            {{ rekapForm.processing ? 'Menyimpan...' : 'Simpan Rekapitulasi Suara' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ── MODAL VIEWER BUKTI REKAP ─────────────────────────────────────── -->
        <Dialog :open="isBuktiViewerOpen" @update:open="isBuktiViewerOpen = $event">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                        {{ viewingBukti?.title || 'Bukti Rekapitulasi Suara' }}
                    </DialogTitle>
                </DialogHeader>
                <div class="mt-2 flex flex-col items-center justify-center bg-zinc-100 dark:bg-zinc-800 rounded-xl p-2 max-h-[70vh] overflow-auto">
                    <img
                        v-if="viewingBukti?.url && !viewingBukti.url.endsWith('.pdf')"
                        :src="viewingBukti.url"
                        alt="Bukti Rekapitulasi"
                        class="max-h-[65vh] w-auto rounded-lg object-contain"
                    />
                    <div v-else class="p-6 text-center">
                        <FileText class="h-12 w-12 text-zinc-400 mx-auto mb-2" />
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Dokumen bukti berformat PDF.</p>
                        <a
                            :href="viewingBukti?.url"
                            target="_blank"
                            class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 text-white text-xs font-bold"
                        >
                            <ExternalLink class="h-3.5 w-3.5" />
                            <span>Buka PDF di Tab Baru</span>
                        </a>
                    </div>
                </div>
                <DialogFooter class="pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isBuktiViewerOpen = false"
                        class="text-xs font-semibold"
                    >
                        Tutup
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── MODAL KONFIRMASI HAPUS REKAP ──────────────────────────────────── -->
        <Dialog :open="isDeleteRekapOpen" @update:open="isDeleteRekapOpen = $event">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-red-600 font-bold">
                        <AlertTriangle class="h-5 w-5" />
                        <span>Hapus Rekapitulasi Suara Offline?</span>
                    </DialogTitle>
                    <DialogDescription class="pt-2 text-zinc-600 dark:text-zinc-400 leading-relaxed text-xs">
                        Apakah Anda yakin ingin menghapus data rekapitulasi sebanyak
                        <strong>{{ deletingRekap?.jumlah_suara }} suara</strong>
                        oleh penanggung jawab <strong>{{ deletingRekap?.penanggung_jawab }}</strong>?
                        <br /><br />
                        <span class="text-red-600 font-semibold">
                            Perolehan suara offline ini akan dikurangi dari total suara pemilihan.
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="pt-4 flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isDeleteRekapOpen = false"
                        :disabled="isDeletingRekap"
                        class="text-xs font-semibold"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        @click="confirmDeleteRekap"
                        :disabled="isDeletingRekap"
                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold"
                    >
                        {{ isDeletingRekap ? 'Menghapus...' : 'Ya, Hapus Rekap' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
