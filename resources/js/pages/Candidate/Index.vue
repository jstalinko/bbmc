<script setup>
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
    ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight,
    Eye, Search, Trash2, X, Crown, Check, XSquare, AlertTriangle, UserCheck
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({ candidates: Object, filters: Object });
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
function clearSearch() { search.value = ''; }

// ── View Modal ───────────────────────────────────────────────────────────────
const viewTarget = ref(null);
function openView(candidate) { viewTarget.value = candidate; }
function closeView() { viewTarget.value = null; }

// ── Status Change Modal ───────────────────────────────────────────────────────
const statusTarget = ref(null);
const pendingStatus = ref('');
const pendingNoUrut = ref('');
const isUpdatingStatus = ref(false);

function confirmStatusChange(candidate, status) {
    viewTarget.value = null;
    statusTarget.value = candidate;
    pendingStatus.value = status;
    pendingNoUrut.value = candidate.no_urut !== null && candidate.no_urut !== undefined ? candidate.no_urut : '';
}

function openEditNoUrut(candidate) {
    statusTarget.value = candidate;
    pendingStatus.value = candidate.status;
    pendingNoUrut.value = candidate.no_urut !== null && candidate.no_urut !== undefined ? candidate.no_urut : '';
}

function closeStatusConfirm() {
    statusTarget.value = null;
    pendingStatus.value = '';
    pendingNoUrut.value = '';
}

function submitStatusChange() {
    if (!statusTarget.value) return;
    isUpdatingStatus.value = true;
    router.put(`/dashboard/candidate/${statusTarget.value.id}`, {
        status: pendingStatus.value || statusTarget.value.status,
        no_urut: pendingNoUrut.value !== '' ? pendingNoUrut.value : null,
    }, {
        onSuccess: () => {
            isUpdatingStatus.value = false;
            // Update the viewTarget details if it's currently open
            if (viewTarget.value && viewTarget.value.id === statusTarget.value.id) {
                viewTarget.value.status = pendingStatus.value || statusTarget.value.status;
                viewTarget.value.no_urut = pendingNoUrut.value !== '' ? pendingNoUrut.value : null;
            }
            closeStatusConfirm();
        },
        onError: () => {
            isUpdatingStatus.value = false;
        }
    });
}

// ── Delete Dialog ────────────────────────────────────────────────────────────
const deleteTarget = ref(null);
const isDeleting = ref(false);
function confirmDelete(candidate) {
    viewTarget.value = null;
    deleteTarget.value = candidate;
}
function cancelDelete() { deleteTarget.value = null; }
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
    'mengajukan': {
        label: 'Pencalonan Diri',
        class: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800/50'
    },
    'diajukan': {
        label: 'Diajukan Anggota',
        class: 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800/50'
    },
    'ditetapkan': {
        label: 'Ditetapkan (Calon)',
        class: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800/50'
    },
    'ditolak': {
        label: 'Ditolak',
        class: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800/50'
    }
};

function getStatusLabel(s) { return statusConfig[s]?.label ?? s; }
function statusBadgeClass(s) { return statusConfig[s]?.class ?? 'bg-gray-100 text-gray-600 border-gray-200'; }

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
            <div v-if="flash.success"
                class="flex items-center gap-3 rounded-xl border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-600 dark:text-green-400 shadow-sm">
                <span>✅</span>
                <span>{{ flash.success }}</span>
            </div>

            <!-- Header -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="flex items-center gap-2 text-xl font-bold">
                        <Crown class="h-5 w-5 text-amber-500 animate-pulse" />
                        Pencalonan El Presidente BBMC
                    </h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        Total <span class="font-semibold text-amber-600">{{ candidates.total }}</span> pengajuan calon terdaftar
                    </p>
                </div>
            </div>

            <!-- Card List/Table -->
            <div class="rounded-2xl border bg-card text-card-foreground shadow-sm overflow-hidden">

                <!-- Toolbar -->
                <div class="flex flex-col gap-3 border-b bg-muted/40 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:w-72">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                        <Input
                            v-model="search"
                            placeholder="Cari calon, pengusul, chapter..."
                            class="pl-9 pr-8 text-sm focus-visible:ring-amber-500"
                        />
                        <button v-if="search" @click="clearSearch"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors">
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <p class="text-xs text-muted-foreground whitespace-nowrap">
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
                                <Crown class="h-10 w-10 opacity-30 text-amber-500" />
                                <p class="font-medium text-sm">Tidak ada data calon</p>
                                <p class="text-xs">
                                    {{ search ? 'Coba ubah kata kunci pencarian.' : 'Belum ada calon yang diajukan.' }}
                                </p>
                            </div>
                        </TableEmpty>

                        <!-- Rows -->
                        <TableRow
                            v-for="(candidate, index) in candidates.data"
                            :key="candidate.id"
                            class="transition-colors"
                        >
                            <!-- Index -->
                            <TableCell class="text-muted-foreground text-xs w-10">
                                {{ (candidates.current_page - 1) * candidates.per_page + index + 1 }}
                            </TableCell>

                            <!-- Photo -->
                            <TableCell>
                                <div class="h-10 w-10 rounded-full border overflow-hidden bg-muted">
                                    <img v-if="getCandidatePhoto(candidate)" :src="getCandidatePhoto(candidate)" class="h-full w-full object-cover" />
                                    <div v-else class="h-full w-full flex items-center justify-center text-xs font-bold text-muted-foreground bg-amber-500/10">
                                        {{ candidate.member?.nama_lengkap?.charAt(0) || 'C' }}
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Nama -->
                            <TableCell class="font-semibold whitespace-nowrap">
                                <div>
                                    <span class="text-sm font-semibold">{{ candidate.member?.nama_lengkap }}</span>
                                    <div class="text-xs text-muted-foreground font-normal">"{{ candidate.member?.nama_panggilan || '—' }}"</div>
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
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <Badge v-if="candidate.self_nominations > 0" variant="secondary" class="bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 text-[11px] font-semibold">Pencalonan Diri</Badge>
                                        <Badge v-if="candidate.member_nominations > 0" variant="secondary" class="bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400 text-[11px] font-semibold">{{ candidate.member_nominations }} Rekomendasi Anggota</Badge>
                                    </div>
                                    <span class="text-[11px] text-muted-foreground font-medium">Total {{ candidate.total_nominations ?? 1 }} Pengajuan</span>
                                </div>
                            </TableCell>

                            <!-- Status Badge -->
                            <TableCell>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <Badge
                                        variant="outline"
                                        :class="['whitespace-nowrap text-xs font-semibold px-2 py-0.5', statusBadgeClass(candidate.status)]"
                                    >
                                        {{ getStatusLabel(candidate.status) }}
                                    </Badge>
                                    <Badge v-if="candidate.no_urut" class="bg-amber-500 text-white font-mono font-bold text-xs px-2 py-0.5 shadow-sm">
                                        No. {{ candidate.no_urut }}
                                    </Badge>
                                </div>
                            </TableCell>

                            <!-- Actions -->
                            <TableCell class="text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Detail -->
                                    <Button variant="ghost" size="icon" class="h-8 w-8 hover:bg-amber-500/10 hover:text-amber-600" title="Detail Visi & Misi" @click="openView(candidate)">
                                        <Eye class="h-4 w-4 text-blue-500" />
                                    </Button>

                                    <!-- Tetapkan -->
                                    <Button
                                        v-if="candidate.status !== 'ditetapkan'"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 hover:bg-green-500/10 text-green-600 hover:text-green-700"
                                        title="Tetapkan Calon"
                                        @click="confirmStatusChange(candidate, 'ditetapkan')"
                                    >
                                        <Check class="h-4 w-4" />
                                    </Button>

                                    <!-- Ubah No Urut jika sudah ditetapkan -->
                                    <Button
                                        v-if="candidate.status === 'ditetapkan'"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 hover:bg-amber-500/10 text-amber-600 hover:text-amber-700 font-mono font-bold text-xs"
                                        title="Ubah Nomor Urut"
                                        @click="openEditNoUrut(candidate)"
                                    >
                                        #
                                    </Button>

                                    <!-- Tolak -->
                                    <Button
                                        v-if="candidate.status !== 'ditolak'"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 hover:bg-red-500/10 text-red-600 hover:text-red-700"
                                        title="Tolak Calon"
                                        @click="confirmStatusChange(candidate, 'ditolak')"
                                    >
                                        <XSquare class="h-4 w-4" />
                                    </Button>

                                    <!-- Delete -->
                                    <Button variant="ghost" size="icon" class="h-8 w-8 hover:bg-destructive/10 text-destructive" title="Hapus" @click="confirmDelete(candidate)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="candidates.last_page > 1"
                    class="flex flex-col items-center justify-between gap-3 border-t bg-muted/40 px-4 py-3 sm:flex-row">
                    <p class="text-xs text-muted-foreground">
                        Halaman <span class="font-semibold text-foreground">{{ candidates.current_page }}</span>
                        dari <span class="font-semibold text-foreground">{{ candidates.last_page }}</span>
                    </p>
                    <div class="flex items-center gap-1 flex-wrap justify-center">
                        <Link :href="candidates.links[0]?.url ?? '#'"
                            :class="['inline-flex', !candidates.links[0]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronsLeft class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <Link :href="candidates.links[1]?.url ?? '#'"
                            :class="['inline-flex', !candidates.links[1]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronLeft class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <template v-for="link in candidates.links.slice(1, -1)" :key="link.label">
                            <Link v-if="link.url" :href="link.url">
                                <Button variant="outline" size="icon" class="h-7 w-7 text-xs font-medium"
                                    :class="link.active ? 'bg-amber-600 text-white border-amber-600 hover:bg-amber-700' : ''">
                                    {{ link.label }}
                                </Button>
                            </Link>
                            <span v-else class="px-1 text-muted-foreground text-xs">…</span>
                        </template>
                        <Link :href="candidates.links[candidates.links.length - 2]?.url ?? '#'"
                            :class="['inline-flex', !candidates.links[candidates.links.length - 2]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronRight class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <Link :href="candidates.links[candidates.links.length - 1]?.url ?? '#'"
                            :class="['inline-flex', !candidates.links[candidates.links.length - 1]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronsRight class="h-3.5 w-3.5" /></Button>
                        </Link>
                    </div>
                </div>

            </div><!-- /Card -->
        </div>

        <!-- ═══ DETAIL VIEW MODAL ═══ -->
        <Dialog :open="!!viewTarget" @update:open="(v) => { if (!v) closeView() }">
            <DialogContent class="max-w-xl p-0 overflow-hidden rounded-2xl">
                <!-- Header Card Banner -->
                <div class="relative bg-gradient-to-br from-amber-600 via-amber-500 to-yellow-500 px-6 pt-8 pb-14 text-white">
                    <div class="absolute inset-0 opacity-10"
                        style="background-image:repeating-linear-gradient(45deg,#fff 0,#fff 1px,transparent 0,transparent 50%);background-size:10px 10px;"></div>
                    <div class="relative flex items-center gap-4">
                        <!-- Photo -->
                        <div class="h-16 w-16 shrink-0 rounded-full border-4 border-white/40 shadow-lg overflow-hidden bg-white/20">
                            <img v-if="getCandidatePhoto(viewTarget)" :src="getCandidatePhoto(viewTarget)" class="h-full w-full object-cover" alt="Foto Calon" />
                            <div v-else class="h-full w-full flex items-center justify-center text-2xl font-black text-white/80 bg-amber-700/30">
                                {{ viewTarget?.member?.nama_lengkap?.charAt(0) || 'C' }}
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-amber-100 flex items-center gap-1">
                                <Crown class="h-3.5 w-3.5 text-yellow-200 fill-yellow-200" />
                                Calon Presidente
                            </p>
                            <h2 class="text-lg font-black leading-tight">{{ viewTarget?.member?.nama_lengkap }}</h2>
                            <p class="text-sm text-amber-500/10 dark:text-amber-100 font-medium">"{{ viewTarget?.member?.nama_panggilan }}"</p>
                        </div>
                        <!-- KTA -->
                        <div class="ml-auto text-right">
                            <p class="text-[10px] text-amber-100 uppercase tracking-widest">No. Kartu</p>
                            <p class="font-mono text-lg font-black">{{ viewTarget?.no_kartu ? `BBMC 38 2026 ${viewTarget.no_kartu}` : '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Details Body -->
                <div class="relative -mt-8 mx-4 mb-4 rounded-xl bg-card border shadow-md px-5 py-4 space-y-4">
                    <!-- Status Badge -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Badge variant="outline" :class="['text-xs font-bold px-3 py-1', statusBadgeClass(viewTarget?.status)]">
                                {{ getStatusLabel(viewTarget?.status) }}
                            </Badge>
                            <Badge v-if="viewTarget?.no_urut" class="bg-amber-500 text-white font-mono font-bold text-xs px-2.5 py-1">
                                No. Urut: {{ viewTarget.no_urut }}
                            </Badge>
                        </div>
                        <span class="text-xs text-muted-foreground">Diajukan: {{ viewTarget?.created_at ? new Date(viewTarget.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) : '—' }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Chapter / Checkpoint</p>
                            <p class="font-semibold mt-0.5">{{ viewTarget?.chapter || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Total Pengusul</p>
                            <p class="font-semibold mt-0.5">
                                {{ viewTarget?.total_nominations ?? 1 }} Pengajuan
                                <span class="text-xs text-muted-foreground font-normal block">({{ viewTarget?.self_nominations > 0 ? 'Pencalonan Mandiri & ' : '' }}{{ viewTarget?.member_nominations || 0 }} Rekomendasi Anggota)</span>
                            </p>
                        </div>
                    </div>

                    <!-- Visi & Misi / Daftar Pengusul Section -->
                    <div class="border-t pt-3 space-y-3 max-h-72 overflow-y-auto pr-1">
                        <div v-if="viewTarget?.nominations_list && viewTarget.nominations_list.length > 0" class="space-y-3">
                            <h3 class="text-xs font-bold text-amber-600 uppercase tracking-wider">Daftar Pengusul & Rekomendasi ({{ viewTarget.nominations_list.length }})</h3>
                            <div v-for="nom in viewTarget.nominations_list" :key="nom.id" class="rounded-lg border bg-muted/30 p-3 text-xs space-y-1.5">
                                <div class="flex items-center justify-between font-semibold border-b pb-1">
                                    <span :class="nom.diajukan_oleh === 'self' ? 'text-blue-600' : 'text-purple-600'">
                                        {{ nom.diajukan_oleh === 'self' ? 'Pencalonan Mandiri (Self)' : nom.diajukan_oleh }}
                                    </span>
                                    <span v-if="nom.no_kartu_diajukan_oleh" class="font-mono text-[10px] text-muted-foreground">KTA: {{ nom.no_kartu_diajukan_oleh }}</span>
                                    <span v-else class="text-[10px] text-muted-foreground">{{ new Date(nom.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short'}) }}</span>
                                </div>
                                <div v-if="nom.visi" class="mt-1">
                                    <span class="font-bold text-[10px] text-muted-foreground uppercase">Visi / Rekomendasi:</span>
                                    <p class="text-foreground/90 italic mt-0.5">{{ nom.visi }}</p>
                                </div>
                                <div v-if="nom.misi" class="mt-1">
                                    <span class="font-bold text-[10px] text-muted-foreground uppercase">Misi:</span>
                                    <p class="text-foreground/90 mt-0.5 whitespace-pre-line">{{ nom.misi }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="space-y-3">
                            <div>
                                <h3 class="text-xs font-bold text-amber-600 uppercase tracking-wider">Visi</h3>
                                <p class="text-sm mt-1 bg-muted/40 rounded-lg p-2.5 italic border-l-2 border-amber-500 whitespace-pre-line text-foreground/90">
                                    {{ viewTarget?.visi || 'Tidak mencantumkan visi.' }}
                                </p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-amber-600 uppercase tracking-wider">Misi / Alasan</h3>
                                <p class="text-sm mt-1 bg-muted/40 rounded-lg p-2.5 whitespace-pre-line text-foreground/90">
                                    {{ viewTarget?.misi || 'Tidak mencantumkan misi.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions inside Detail Modal -->
                <div class="px-4 pb-4 flex flex-col gap-2">
                    <div class="flex gap-2">
                        <Button
                            v-if="viewTarget?.status !== 'ditetapkan'"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white gap-1.5"
                            @click="confirmStatusChange(viewTarget, 'ditetapkan')"
                        >
                            <Check class="h-4 w-4" /> Tetapkan
                        </Button>
                        <Button
                            v-if="viewTarget?.status !== 'ditolak'"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white gap-1.5"
                            @click="confirmStatusChange(viewTarget, 'ditolak')"
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

        <!-- ═══ STATUS CHANGE CONFIRMATION DIALOG ═══ -->
        <Dialog :open="!!statusTarget" @update:open="(v) => { if (!v) closeStatusConfirm() }">
            <DialogContent class="max-w-sm rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2" :class="pendingStatus === 'ditetapkan' || statusTarget?.status === 'ditetapkan' ? 'text-green-600' : 'text-red-600'">
                        <UserCheck v-if="pendingStatus === 'ditetapkan' || statusTarget?.status === 'ditetapkan'" class="h-5 w-5" />
                        <AlertTriangle v-else class="h-5 w-5" />
                        Konfirmasi Perubahan Status & No. Urut
                    </DialogTitle>
                    <DialogDescription class="mt-2 text-sm">
                        Apakah Anda yakin ingin mengatur status pengajuan calon
                        <span class="font-semibold text-foreground">{{ statusTarget?.member?.nama_lengkap }}</span> menjadi
                        <span class="font-bold" :class="pendingStatus === 'ditetapkan' || statusTarget?.status === 'ditetapkan' ? 'text-green-600' : 'text-red-600'">
                            {{ getStatusLabel(pendingStatus) }}
                        </span>?
                    </DialogDescription>
                    <div v-if="pendingStatus === 'ditetapkan' || statusTarget?.status === 'ditetapkan'" class="mt-4 pt-4 border-t space-y-2 text-left">
                        <label class="block text-xs font-bold uppercase tracking-wider text-amber-600">Nomor Urut Calon (No. Urut)</label>
                        <Input 
                            v-model="pendingNoUrut" 
                            type="number" 
                            placeholder="Contoh: 1" 
                            class="font-mono font-bold text-base"
                        />
                        <p class="text-[11px] text-muted-foreground">Masukkan nomor urut calon resmi saat ditetapkan agar tampil berurutan di Dashboard & Polling.</p>
                    </div>
                </DialogHeader>
                <DialogFooter class="mt-4 flex gap-2">
                    <Button variant="outline" class="flex-1" @click="closeStatusConfirm" :disabled="isUpdatingStatus">Batal</Button>
                    <Button
                        :class="['flex-1 text-white', pendingStatus === 'ditetapkan' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700']"
                        :disabled="isUpdatingStatus"
                        @click="submitStatusChange"
                    >
                        <svg v-if="isUpdatingStatus" class="mr-1.5 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ isUpdatingStatus ? 'Memproses...' : 'Ya, Ubah' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ═══ DELETE CONFIRMATION DIALOG ═══ -->
        <Dialog :open="!!deleteTarget" @update:open="(v) => { if (!v) cancelDelete() }">
            <DialogContent class="max-w-sm rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-red-600">
                        <Trash2 class="h-5 w-5" /> Hapus Calon
                    </DialogTitle>
                    <DialogDescription class="mt-2 text-sm">
                        Anda akan menghapus data calon
                        <span class="font-semibold text-foreground">{{ deleteTarget?.member?.nama_lengkap }}</span> dari daftar pengusulan.
                        Tindakan ini <span class="font-semibold text-destructive">tidak dapat dibatalkan</span>.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="mt-4 flex gap-2">
                    <Button variant="outline" class="flex-1" @click="cancelDelete" :disabled="isDeleting">Batal</Button>
                    <Button variant="destructive" class="flex-1" :disabled="isDeleting" @click="doDelete">
                        <svg v-if="isDeleting" class="mr-1.5 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>