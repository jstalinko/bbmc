<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    AlertTriangle,
    Check,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Crown,
    Eye,
    Hash,
    Search,
    Trash2,
    UserCheck,
    X,
    XSquare,
} from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    candidates: Object,
    assignedNoUruts: {
        type: Array,
        default: () => [],
    },
    filters: Object,
});
const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Calon Presidente', href: '/dashboard/candidate' },
];

// ── Search ──────────────────────────────────────────────────────────────────
const search = ref(props.filters?.search ?? '');
let searchTimer;
watch(search, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/dashboard/candidate', { search: val }, { preserveState: true, replace: true });
    }, 400);
});
function clearSearch() {
    search.value = '';
}

// ── View Modal ───────────────────────────────────────────────────────────────
const viewTarget = ref(null);
function openView(candidate) {
    viewTarget.value = candidate;
}
function closeView() {
    viewTarget.value = null;
}

// ── Status Change Modal ───────────────────────────────────────────────────────
const statusTarget = ref(null);
const pendingStatus = ref('');
const pendingNoUrut = ref('');
const isUpdatingStatus = ref(false);
const statusError = ref('');

function getNextSuggestedNoUrut() {
    const used = new Set();
    (props.assignedNoUruts || []).forEach((n) => {
        const num = Number(n);
        if (!isNaN(num) && num > 0) used.add(num);
    });
    (props.candidates?.data || []).forEach((c) => {
        if (c.status === 'ditetapkan' && c.no_urut) {
            const num = Number(c.no_urut);
            if (!isNaN(num) && num > 0) used.add(num);
        }
    });
    let suggestion = 1;
    while (used.has(suggestion)) {
        suggestion++;
    }
    return suggestion;
}

function focusNoUrutInput() {
    nextTick(() => {
        setTimeout(() => {
            const inputEl = document.getElementById('pending-no-urut-input');
            if (inputEl) {
                inputEl.focus();
                inputEl.select();
            }
        }, 150);
    });
}

function confirmStatusChange(candidate, status) {
    statusTarget.value = candidate;
    pendingStatus.value = status;
    statusError.value = '';

    if (status === 'ditetapkan') {
        if (candidate.no_urut !== null && candidate.no_urut !== undefined && candidate.no_urut !== '') {
            pendingNoUrut.value = candidate.no_urut;
        } else {
            pendingNoUrut.value = getNextSuggestedNoUrut();
        }
        focusNoUrutInput();
    } else {
        pendingNoUrut.value = '';
    }
}

function openEditNoUrut(candidate) {
    statusTarget.value = candidate;
    pendingStatus.value = 'ditetapkan';
    statusError.value = '';
    pendingNoUrut.value =
        candidate.no_urut !== null && candidate.no_urut !== undefined && candidate.no_urut !== '' ? candidate.no_urut : getNextSuggestedNoUrut();
    focusNoUrutInput();
}

function openEditNoUrutFromView(candidate) {
    viewTarget.value = null;
    openEditNoUrut(candidate);
}

function closeStatusConfirm() {
    statusTarget.value = null;
    pendingStatus.value = '';
    pendingNoUrut.value = '';
    statusError.value = '';
}

function submitStatusChange() {
    if (!statusTarget.value) return;
    statusError.value = '';

    const isDitetapkan = pendingStatus.value === 'ditetapkan' || (statusTarget.value.status === 'ditetapkan' && pendingStatus.value !== 'ditolak');

    if (isDitetapkan) {
        const val = String(pendingNoUrut.value ?? '').trim();
        if (!val) {
            statusError.value = 'Nomor urut wajib diisi untuk calon yang ditetapkan.';
            return;
        }
        const num = Number(val);
        if (isNaN(num) || num < 1 || !Number.isInteger(num)) {
            statusError.value = 'Nomor urut harus berupa angka bulat positif (minimal 1).';
            return;
        }

        const duplicate = (props.candidates?.data || []).find(
            (c) => c.member_id !== statusTarget.value.member_id && c.status === 'ditetapkan' && Number(c.no_urut) === num,
        );
        if (duplicate) {
            statusError.value = `Nomor urut ${num} sudah digunakan oleh calon "${duplicate.member?.nama_lengkap || 'lain'}". Silakan pilih nomor urut yang lain.`;
            return;
        }
    }

    isUpdatingStatus.value = true;
    router.put(
        `/dashboard/candidate/${statusTarget.value.id}`,
        {
            status: pendingStatus.value || statusTarget.value.status,
            no_urut: isDitetapkan ? Number(pendingNoUrut.value) : pendingStatus.value === 'ditolak' ? null : null,
        },
        {
            onSuccess: () => {
                isUpdatingStatus.value = false;
                // Update the viewTarget details if it's currently open
                if (viewTarget.value && viewTarget.value.id === statusTarget.value.id) {
                    viewTarget.value.status = pendingStatus.value || statusTarget.value.status;
                    viewTarget.value.no_urut = isDitetapkan ? Number(pendingNoUrut.value) : null;
                }
                closeStatusConfirm();
            },
            onError: (err) => {
                isUpdatingStatus.value = false;
                if (err?.no_urut) {
                    statusError.value = err.no_urut;
                } else if (err?.status) {
                    statusError.value = err.status;
                } else {
                    statusError.value = 'Gagal menyimpan status calon. Silakan periksa kembali input Anda.';
                }
            },
        },
    );
}

// ── Delete Dialog ────────────────────────────────────────────────────────────
const deleteTarget = ref(null);
const isDeleting = ref(false);
function confirmDelete(candidate) {
    viewTarget.value = null;
    deleteTarget.value = candidate;
}
function cancelDelete() {
    deleteTarget.value = null;
}
function doDelete() {
    if (!deleteTarget.value) return;
    isDeleting.value = true;
    router.delete(`/dashboard/candidate/${deleteTarget.value.id}`, {
        onFinish: () => {
            isDeleting.value = false;
            deleteTarget.value = null;
            closeView();
        },
    });
}

// ── Helpers ──────────────────────────────────────────────────────────────────
const statusConfig = {
    mengajukan: {
        label: 'Pencalonan Diri',
        class: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800/50',
    },
    diajukan: {
        label: 'Diajukan Anggota',
        class: 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800/50',
    },
    ditetapkan: {
        label: 'Ditetapkan (Calon)',
        class: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800/50',
    },
    ditolak: {
        label: 'Ditolak',
        class: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800/50',
    },
};

function getStatusLabel(s) {
    return statusConfig[s]?.label ?? s;
}
function statusBadgeClass(s) {
    return statusConfig[s]?.class ?? 'bg-gray-100 text-gray-600 border-gray-200';
}

function getCandidatePhoto(c) {
    if (!c) return null;
    if (c.foto_calon) return `/storage/${c.foto_calon}`;
    if (c.member?.foto) return `/storage/${c.member.foto}`;
    return null;
}
</script>

<template>
    <Head title="Calon Presidente" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-5 p-4 sm:p-6">
            <!-- Flash -->
            <div
                v-if="flash.success"
                class="flex items-center gap-3 rounded-xl border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-600 shadow-sm dark:text-green-400"
            >
                <span>✅</span>
                <span>{{ flash.success }}</span>
            </div>
            <div
                v-if="flash.error"
                class="flex items-center gap-3 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-600 shadow-sm dark:text-red-400"
            >
                <span>⚠️</span>
                <span>{{ flash.error }}</span>
            </div>

            <!-- Header -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="flex items-center gap-2 text-xl font-bold">
                        <Crown class="h-5 w-5 animate-pulse text-amber-500" />
                        Pencalonan El Presidente BBMC
                    </h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        Total <span class="font-semibold text-amber-600">{{ candidates.total }}</span> pengajuan calon terdaftar
                    </p>
                </div>
            </div>

            <!-- Card List/Table -->
            <div class="overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-sm">
                <!-- Toolbar -->
                <div class="flex flex-col gap-3 border-b bg-muted/40 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:w-72">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Cari calon, pengusul, chapter..."
                            class="pl-9 pr-8 text-sm focus-visible:ring-amber-500"
                        />
                        <button
                            v-if="search"
                            @click="clearSearch"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <p class="whitespace-nowrap text-xs text-muted-foreground">
                        Menampilkan {{ candidates.from ?? 0 }}–{{ candidates.to ?? 0 }} dari {{ candidates.total }} data
                    </p>
                </div>

                <!-- Table -->
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-10 text-xs font-semibold uppercase tracking-wider">#</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Foto</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Nama Calon</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">No. Kartu</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Chapter</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Pengusul</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Status</TableHead>
                            <TableHead class="text-center text-xs font-semibold uppercase tracking-wider">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <!-- Empty -->
                        <TableEmpty v-if="candidates.data.length === 0" :colspan="8">
                            <div class="flex flex-col items-center gap-2 py-12 text-muted-foreground">
                                <Crown class="h-10 w-10 text-amber-500 opacity-30" />
                                <p class="text-sm font-medium">Tidak ada data calon</p>
                                <p class="text-xs">
                                    {{ search ? 'Coba ubah kata kunci pencarian.' : 'Belum ada calon yang diajukan.' }}
                                </p>
                            </div>
                        </TableEmpty>

                        <!-- Rows -->
                        <TableRow v-for="(candidate, index) in candidates.data" :key="candidate.id" class="transition-colors">
                            <!-- Index -->
                            <TableCell class="w-10 text-xs text-muted-foreground">
                                {{ (candidates.current_page - 1) * candidates.per_page + index + 1 }}
                            </TableCell>

                            <!-- Photo -->
                            <TableCell>
                                <div class="h-10 w-10 overflow-hidden rounded-full border bg-muted">
                                    <img v-if="getCandidatePhoto(candidate)" :src="getCandidatePhoto(candidate)" class="h-full w-full object-cover" />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center bg-amber-500/10 text-xs font-bold text-muted-foreground"
                                    >
                                        {{ candidate.member?.nama_lengkap?.charAt(0) || 'C' }}
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Nama -->
                            <TableCell class="whitespace-nowrap font-semibold">
                                <div>
                                    <span class="text-sm font-semibold">{{ candidate.member?.nama_lengkap }}</span>
                                    <div class="text-xs font-normal text-muted-foreground">"{{ candidate.member?.nama_panggilan || '—' }}"</div>
                                </div>
                            </TableCell>

                            <!-- No Kartu -->
                            <TableCell>
                                <span class="rounded-md bg-muted px-2 py-0.5 font-mono text-xs font-semibold text-muted-foreground">
                                    BBMC 38 2026 {{ candidate.no_kartu || '—' }}
                                </span>
                            </TableCell>

                            <!-- Chapter -->
                            <TableCell class="whitespace-nowrap text-sm">
                                {{ candidate.chapter }}
                            </TableCell>

                            <!-- Pengusul -->
                            <TableCell class="text-xs">
                                <div class="flex flex-col gap-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <Badge
                                            v-if="candidate.self_nominations > 0"
                                            variant="secondary"
                                            class="bg-blue-50 text-[11px] font-semibold text-blue-700 dark:bg-blue-950/20 dark:text-blue-400"
                                            >Pencalonan Diri</Badge
                                        >
                                        <Badge
                                            v-if="candidate.member_nominations > 0"
                                            variant="secondary"
                                            class="bg-purple-50 text-[11px] font-semibold text-purple-700 dark:bg-purple-950/20 dark:text-purple-400"
                                            >{{ candidate.member_nominations }} Rekomendasi Anggota</Badge
                                        >
                                    </div>
                                    <span class="text-[11px] font-medium text-muted-foreground"
                                        >Total {{ candidate.total_nominations ?? 1 }} Pengajuan</span
                                    >
                                </div>
                            </TableCell>

                            <!-- Status Badge -->
                            <TableCell>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <Badge
                                        variant="outline"
                                        :class="['whitespace-nowrap px-2 py-0.5 text-xs font-semibold', statusBadgeClass(candidate.status)]"
                                    >
                                        {{ getStatusLabel(candidate.status) }}
                                    </Badge>
                                    <Badge
                                        v-if="candidate.status === 'ditetapkan' && candidate.no_urut"
                                        class="bg-amber-500 px-2 py-0.5 font-mono text-xs font-bold text-white shadow-sm"
                                    >
                                        No. {{ candidate.no_urut }}
                                    </Badge>
                                    <Badge
                                        v-else-if="candidate.status === 'ditetapkan' && !candidate.no_urut"
                                        class="border border-red-300 bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700"
                                    >
                                        No. Urut Kosong
                                    </Badge>
                                </div>
                            </TableCell>

                            <!-- Actions -->
                            <TableCell class="text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Detail -->
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 hover:bg-amber-500/10 hover:text-amber-600"
                                        title="Detail Visi & Misi"
                                        @click="openView(candidate)"
                                    >
                                        <Eye class="h-4 w-4 text-blue-500" />
                                    </Button>

                                    <!-- Tetapkan -->
                                    <Button
                                        v-if="candidate.status !== 'ditetapkan'"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-green-600 hover:bg-green-500/10 hover:text-green-700"
                                        title="Tetapkan Calon & Masukkan Nomor Urut"
                                        @click="confirmStatusChange(candidate, 'ditetapkan')"
                                    >
                                        <Check class="h-4 w-4" />
                                    </Button>

                                    <!-- Ubah No Urut jika sudah ditetapkan -->
                                    <Button
                                        v-if="candidate.status === 'ditetapkan'"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 font-mono text-xs font-bold text-amber-600 hover:bg-amber-500/10 hover:text-amber-700"
                                        title="Ubah Nomor Urut Calon"
                                        @click="openEditNoUrut(candidate)"
                                    >
                                        #
                                    </Button>

                                    <!-- Tolak -->
                                    <Button
                                        v-if="candidate.status !== 'ditolak'"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-red-600 hover:bg-red-500/10 hover:text-red-700"
                                        title="Tolak Calon"
                                        @click="confirmStatusChange(candidate, 'ditolak')"
                                    >
                                        <XSquare class="h-4 w-4" />
                                    </Button>

                                    <!-- Delete -->
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-destructive hover:bg-destructive/10"
                                        title="Hapus"
                                        @click="confirmDelete(candidate)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div
                    v-if="candidates.last_page > 1"
                    class="flex flex-col items-center justify-between gap-3 border-t bg-muted/40 px-4 py-3 sm:flex-row"
                >
                    <p class="text-xs text-muted-foreground">
                        Halaman <span class="font-semibold text-foreground">{{ candidates.current_page }}</span> dari
                        <span class="font-semibold text-foreground">{{ candidates.last_page }}</span>
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-1">
                        <Link
                            :href="candidates.links[0]?.url ?? '#'"
                            :class="['inline-flex', !candidates.links[0]?.url ? 'pointer-events-none opacity-40' : '']"
                        >
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronsLeft class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <Link
                            :href="candidates.links[1]?.url ?? '#'"
                            :class="['inline-flex', !candidates.links[1]?.url ? 'pointer-events-none opacity-40' : '']"
                        >
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronLeft class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <template v-for="link in candidates.links.slice(1, -1)" :key="link.label">
                            <Link v-if="link.url" :href="link.url">
                                <Button
                                    variant="outline"
                                    size="icon"
                                    class="h-7 w-7 text-xs font-medium"
                                    :class="link.active ? 'border-amber-600 bg-amber-600 text-white hover:bg-amber-700' : ''"
                                >
                                    {{ link.label }}
                                </Button>
                            </Link>
                            <span v-else class="px-1 text-xs text-muted-foreground">…</span>
                        </template>
                        <Link
                            :href="candidates.links[candidates.links.length - 2]?.url ?? '#'"
                            :class="['inline-flex', !candidates.links[candidates.links.length - 2]?.url ? 'pointer-events-none opacity-40' : '']"
                        >
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronRight class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <Link
                            :href="candidates.links[candidates.links.length - 1]?.url ?? '#'"
                            :class="['inline-flex', !candidates.links[candidates.links.length - 1]?.url ? 'pointer-events-none opacity-40' : '']"
                        >
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronsRight class="h-3.5 w-3.5" /></Button>
                        </Link>
                    </div>
                </div>
            </div>
            <!-- /Card -->
        </div>

        <!-- ═══ DETAIL VIEW MODAL ═══ -->
        <Dialog
            :open="!!viewTarget"
            @update:open="
                (v) => {
                    if (!v) closeView();
                }
            "
        >
            <DialogContent class="max-w-xl overflow-hidden rounded-2xl p-0">
                <!-- Header Card Banner -->
                <div class="relative bg-gradient-to-br from-amber-600 via-amber-500 to-yellow-500 px-6 pb-14 pt-8 text-white">
                    <div
                        class="absolute inset-0 opacity-10"
                        style="
                            background-image: repeating-linear-gradient(45deg, #fff 0, #fff 1px, transparent 0, transparent 50%);
                            background-size: 10px 10px;
                        "
                    ></div>
                    <div class="relative flex items-center gap-4">
                        <!-- Photo -->
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-full border-4 border-white/40 bg-white/20 shadow-lg">
                            <img
                                v-if="getCandidatePhoto(viewTarget)"
                                :src="getCandidatePhoto(viewTarget)"
                                class="h-full w-full object-cover"
                                alt="Foto Calon"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center bg-amber-700/30 text-2xl font-black text-white/80">
                                {{ viewTarget?.member?.nama_lengkap?.charAt(0) || 'C' }}
                            </div>
                        </div>
                        <div>
                            <p class="flex items-center gap-1 text-xs font-semibold uppercase tracking-widest text-amber-100">
                                <Crown class="h-3.5 w-3.5 fill-yellow-200 text-yellow-200" />
                                Calon Presidente
                            </p>
                            <h2 class="text-lg font-black leading-tight">{{ viewTarget?.member?.nama_lengkap }}</h2>
                            <p class="text-sm font-medium text-amber-500/10 dark:text-amber-100">"{{ viewTarget?.member?.nama_panggilan }}"</p>
                        </div>
                        <!-- KTA -->
                        <div class="ml-auto text-right">
                            <p class="text-[10px] uppercase tracking-widest text-amber-100">No. Kartu</p>
                            <p class="font-mono text-lg font-black">{{ viewTarget?.no_kartu ? `BBMC 38 2026 ${viewTarget.no_kartu}` : '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Details Body -->
                <div class="relative mx-4 -mt-8 mb-4 space-y-4 rounded-xl border bg-card px-5 py-4 shadow-md">
                    <!-- Status Badge -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Badge variant="outline" :class="['px-3 py-1 text-xs font-bold', statusBadgeClass(viewTarget?.status)]">
                                {{ getStatusLabel(viewTarget?.status) }}
                            </Badge>
                            <Badge v-if="viewTarget?.no_urut" class="bg-amber-500 px-2.5 py-1 font-mono text-xs font-bold text-white">
                                No. Urut: {{ viewTarget.no_urut }}
                            </Badge>
                        </div>
                        <span class="text-xs text-muted-foreground"
                            >Diajukan:
                            {{
                                viewTarget?.created_at
                                    ? new Date(viewTarget.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
                                    : '—'
                            }}</span
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Chapter / Checkpoint</p>
                            <p class="mt-0.5 font-semibold">{{ viewTarget?.chapter || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Total Pengusul</p>
                            <p class="mt-0.5 font-semibold">
                                {{ viewTarget?.total_nominations ?? 1 }} Pengajuan
                                <span class="block text-xs font-normal text-muted-foreground"
                                    >({{ viewTarget?.self_nominations > 0 ? 'Pencalonan Mandiri & ' : ''
                                    }}{{ viewTarget?.member_nominations || 0 }} Rekomendasi Anggota)</span
                                >
                            </p>
                        </div>
                    </div>

                    <!-- Visi & Misi / Daftar Pengusul Section -->
                    <div class="max-h-72 space-y-3 overflow-y-auto border-t pr-1 pt-3">
                        <div v-if="viewTarget?.nominations_list && viewTarget.nominations_list.length > 0" class="space-y-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-amber-600">
                                Daftar Pengusul & Rekomendasi ({{ viewTarget.nominations_list.length }})
                            </h3>
                            <div
                                v-for="nom in viewTarget.nominations_list"
                                :key="nom.id"
                                class="space-y-1.5 rounded-lg border bg-muted/30 p-3 text-xs"
                            >
                                <div class="flex items-center justify-between border-b pb-1 font-semibold">
                                    <span :class="nom.diajukan_oleh === 'self' ? 'text-blue-600' : 'text-purple-600'">
                                        {{ nom.diajukan_oleh === 'self' ? 'Pencalonan Mandiri (Self)' : nom.diajukan_oleh }}
                                    </span>
                                    <span v-if="nom.no_kartu_diajukan_oleh" class="font-mono text-[10px] text-muted-foreground"
                                        >KTA: {{ nom.no_kartu_diajukan_oleh }}</span
                                    >
                                    <span v-else class="text-[10px] text-muted-foreground">{{
                                        new Date(nom.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
                                    }}</span>
                                </div>
                                <div v-if="nom.visi" class="mt-1">
                                    <span class="text-[10px] font-bold uppercase text-muted-foreground">Visi / Rekomendasi:</span>
                                    <p class="mt-0.5 italic text-foreground/90">{{ nom.visi }}</p>
                                </div>
                                <div v-if="nom.misi" class="mt-1">
                                    <span class="text-[10px] font-bold uppercase text-muted-foreground">Misi:</span>
                                    <p class="mt-0.5 whitespace-pre-line text-foreground/90">{{ nom.misi }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="space-y-3">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-amber-600">Visi</h3>
                                <p
                                    class="mt-1 whitespace-pre-line rounded-lg border-l-2 border-amber-500 bg-muted/40 p-2.5 text-sm italic text-foreground/90"
                                >
                                    {{ viewTarget?.visi || 'Tidak mencantumkan visi.' }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-amber-600">Misi / Alasan</h3>
                                <p class="mt-1 whitespace-pre-line rounded-lg bg-muted/40 p-2.5 text-sm text-foreground/90">
                                    {{ viewTarget?.misi || 'Tidak mencantumkan misi.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions inside Detail Modal -->
                <div class="flex flex-col gap-2 px-4 pb-4">
                    <div class="flex gap-2">
                        <Button
                            v-if="viewTarget?.status !== 'ditetapkan'"
                            class="flex-1 gap-1.5 bg-green-600 font-bold text-white hover:bg-green-700"
                            @click="
                                () => {
                                    const t = viewTarget;
                                    closeView();
                                    confirmStatusChange(t, 'ditetapkan');
                                }
                            "
                        >
                            <Check class="h-4 w-4" /> Tetapkan Calon
                        </Button>
                        <Button
                            v-if="viewTarget?.status === 'ditetapkan'"
                            class="flex-1 gap-1.5 bg-amber-600 font-bold text-white hover:bg-amber-700"
                            @click="openEditNoUrutFromView(viewTarget)"
                        >
                            <Hash class="h-4 w-4" /> Ubah No. Urut {{ viewTarget?.no_urut ? `(#${viewTarget.no_urut})` : '' }}
                        </Button>
                        <Button
                            v-if="viewTarget?.status !== 'ditolak'"
                            class="flex-1 gap-1.5 bg-red-600 text-white hover:bg-red-700"
                            @click="
                                () => {
                                    const t = viewTarget;
                                    closeView();
                                    confirmStatusChange(t, 'ditolak');
                                }
                            "
                        >
                            <XSquare class="h-4 w-4" /> Tolak
                        </Button>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="destructive" class="flex-1 gap-1.5" @click="confirmDelete(viewTarget)">
                            <Trash2 class="h-4 w-4" /> Hapus
                        </Button>
                        <Button variant="outline" class="flex-1" @click="closeView">Tutup</Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- ═══ STATUS CHANGE / NOMOR URUT MODAL ═══ -->
        <Dialog
            :open="!!statusTarget"
            @update:open="
                (v) => {
                    if (!v) closeStatusConfirm();
                }
            "
        >
            <DialogContent class="max-w-md rounded-2xl p-6">
                <!-- Case 1: Tetapkan or Ubah No. Urut -->
                <div
                    v-if="pendingStatus === 'ditetapkan' || (statusTarget?.status === 'ditetapkan' && pendingStatus !== 'ditolak')"
                    class="space-y-4"
                >
                    <DialogHeader class="space-y-1 text-left">
                        <DialogTitle class="flex items-center gap-2 text-lg font-bold text-green-600">
                            <UserCheck class="h-5 w-5 shrink-0" />
                            <span>{{ statusTarget?.status === 'ditetapkan' ? 'Ubah Nomor Urut Calon' : 'Tetapkan Calon Presidente' }}</span>
                        </DialogTitle>
                        <DialogDescription class="text-xs text-muted-foreground">
                            Calon resmi yang ditetapkan <strong class="text-foreground">wajib memiliki nomor urut</strong> untuk pemungutan suara &
                            polling pemilihan.
                        </DialogDescription>
                    </DialogHeader>

                    <!-- Candidate Card Summary -->
                    <div class="flex items-center gap-3 rounded-xl border bg-muted/40 p-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border bg-muted font-bold text-muted-foreground"
                        >
                            <img v-if="getCandidatePhoto(statusTarget)" :src="getCandidatePhoto(statusTarget)" class="h-full w-full object-cover" />
                            <span v-else>{{ statusTarget?.member?.nama_lengkap?.charAt(0) || 'C' }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-foreground">{{ statusTarget?.member?.nama_lengkap }}</p>
                            <p class="flex items-center gap-1.5 font-mono text-xs text-muted-foreground">
                                <span>KTA: BBMC 38 2026 {{ statusTarget?.no_kartu || '—' }}</span>
                                <span>•</span>
                                <span>{{ statusTarget?.chapter || '—' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Input Nomor Urut -->
                    <div class="space-y-2 pt-1 text-left">
                        <div class="flex items-center justify-between">
                            <label
                                for="pending-no-urut-input"
                                class="flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400"
                            >
                                Nomor Urut Calon <span class="font-bold text-red-500">*</span>
                            </label>
                            <span class="text-[11px] text-muted-foreground">Wajib diisi (minimal 1)</span>
                        </div>

                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 font-mono text-base font-bold text-muted-foreground"
                            >
                                #
                            </div>
                            <Input
                                id="pending-no-urut-input"
                                v-model="pendingNoUrut"
                                type="number"
                                min="1"
                                step="1"
                                placeholder="Contoh: 1"
                                class="h-12 pl-8 font-mono text-lg font-bold focus-visible:ring-green-500"
                                :class="{ 'border-red-500 ring-1 ring-red-500': !!statusError }"
                                @keydown.enter.prevent="submitStatusChange"
                            />
                        </div>

                        <p class="text-[11px] leading-relaxed text-muted-foreground">
                            Nomor urut resmi saat ditetapkan agar tampil berurutan di Dashboard Pemilihan & Live Polling.
                        </p>

                        <!-- Error Message -->
                        <div
                            v-if="statusError"
                            class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-2.5 text-xs font-medium text-red-600 dark:border-red-800 dark:bg-red-950/30"
                        >
                            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{{ statusError }}</span>
                        </div>
                    </div>

                    <DialogFooter class="flex gap-2 pt-2 sm:gap-2">
                        <Button variant="outline" class="flex-1" @click="closeStatusConfirm" :disabled="isUpdatingStatus"> Batal </Button>
                        <Button
                            class="flex-1 bg-green-600 font-bold text-white hover:bg-green-700"
                            :disabled="isUpdatingStatus || !pendingNoUrut"
                            @click="submitStatusChange"
                        >
                            <svg v-if="isUpdatingStatus" class="mr-1.5 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            <Check v-else class="mr-1.5 h-4 w-4" />
                            {{ isUpdatingStatus ? 'Menyimpan...' : statusTarget?.status === 'ditetapkan' ? 'Simpan Nomor Urut' : 'Tetapkan Calon' }}
                        </Button>
                    </DialogFooter>
                </div>

                <!-- Case 2: Tolak Calon -->
                <div v-else class="space-y-4">
                    <DialogHeader class="space-y-1 text-left">
                        <DialogTitle class="flex items-center gap-2 text-lg font-bold text-red-600">
                            <AlertTriangle class="h-5 w-5 shrink-0" />
                            <span>Konfirmasi Tolak Calon</span>
                        </DialogTitle>
                        <DialogDescription class="text-sm">
                            Apakah Anda yakin ingin menolak pengajuan calon
                            <span class="font-semibold text-foreground">{{ statusTarget?.member?.nama_lengkap }}</span
                            >? Calon ini tidak akan masuk ke bursa pemilihan resmi.
                        </DialogDescription>
                    </DialogHeader>

                    <!-- Candidate Card Summary -->
                    <div class="flex items-center gap-3 rounded-xl border bg-muted/40 p-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border bg-muted font-bold text-muted-foreground"
                        >
                            <img v-if="getCandidatePhoto(statusTarget)" :src="getCandidatePhoto(statusTarget)" class="h-full w-full object-cover" />
                            <span v-else>{{ statusTarget?.member?.nama_lengkap?.charAt(0) || 'C' }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-foreground">{{ statusTarget?.member?.nama_lengkap }}</p>
                            <p class="flex items-center gap-1.5 font-mono text-xs text-muted-foreground">
                                <span>KTA: BBMC 38 2026 {{ statusTarget?.no_kartu || '—' }}</span>
                                <span>•</span>
                                <span>{{ statusTarget?.chapter || '—' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div
                        v-if="statusError"
                        class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-2.5 text-xs font-medium text-red-600 dark:border-red-800 dark:bg-red-950/30"
                    >
                        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>{{ statusError }}</span>
                    </div>

                    <DialogFooter class="flex gap-2 pt-2 sm:gap-2">
                        <Button variant="outline" class="flex-1" @click="closeStatusConfirm" :disabled="isUpdatingStatus"> Batal </Button>
                        <Button
                            class="flex-1 bg-red-600 font-bold text-white hover:bg-red-700"
                            :disabled="isUpdatingStatus"
                            @click="submitStatusChange"
                        >
                            <svg v-if="isUpdatingStatus" class="mr-1.5 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            <XSquare v-else class="mr-1.5 h-4 w-4" />
                            {{ isUpdatingStatus ? 'Memproses...' : 'Ya, Tolak' }}
                        </Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>

        <!-- ═══ DELETE CONFIRMATION DIALOG ═══ -->
        <Dialog
            :open="!!deleteTarget"
            @update:open="
                (v) => {
                    if (!v) cancelDelete();
                }
            "
        >
            <DialogContent class="max-w-sm rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-red-600"> <Trash2 class="h-5 w-5" /> Hapus Calon </DialogTitle>
                    <DialogDescription class="mt-2 text-sm">
                        Anda akan menghapus data calon
                        <span class="font-semibold text-foreground">{{ deleteTarget?.member?.nama_lengkap }}</span> dari daftar pengusulan. Tindakan
                        ini <span class="font-semibold text-destructive">tidak dapat dibatalkan</span>.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="mt-4 flex gap-2">
                    <Button variant="outline" class="flex-1" @click="cancelDelete" :disabled="isDeleting">Batal</Button>
                    <Button variant="destructive" class="flex-1" :disabled="isDeleting" @click="doDelete">
                        <svg v-if="isDeleting" class="mr-1.5 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
