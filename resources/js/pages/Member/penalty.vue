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
    ArrowDown, ArrowUp, ArrowUpDown,
    ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight,
    ExternalLink, Eye, Pencil, Search, ShieldAlert, UserRoundX, X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({ members: Object, filters: Object });
const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Data Anggota', href: '/dashboard/member' },
    { title: 'Anggota Penalty', href: '/dashboard/member/penalty' },
];

// ── Search & Sort ────────────────────────────────────────────────────────────
const search = ref(props.filters?.search ?? '');
const sortBy = ref(props.filters?.sort_by ?? '');
const sortDir = ref(props.filters?.sort_dir ?? '');

watch(() => props.filters?.sort_by, (val) => { sortBy.value = val ?? ''; });
watch(() => props.filters?.sort_dir, (val) => { sortDir.value = val ?? ''; });

let searchTimer;
watch(search, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters({ search: val, page: 1 }), 350);
});

function toggleSort(col) {
    let nextDir = 'asc';
    if (sortBy.value === col) {
        nextDir = sortDir.value === 'asc' ? 'desc' : '';
    }
    applyFilters({
        sort_by: nextDir ? col : '',
        sort_dir: nextDir,
        page: 1,
    });
}

function applyFilters(overrides = {}) {
    router.get('/dashboard/member/penalty', {
        search: overrides.search !== undefined ? overrides.search : search.value,
        sort_by: overrides.sort_by !== undefined ? overrides.sort_by : sortBy.value,
        sort_dir: overrides.sort_dir !== undefined ? overrides.sort_dir : sortDir.value,
        page: overrides.page ?? 1,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearFilters() {
    search.value = '';
    sortBy.value = '';
    sortDir.value = '';
    applyFilters({ search: '', sort_by: '', sort_dir: '', page: 1 });
}

// ── Edit Penalty Modal ───────────────────────────────────────────────────────
const editPenaltyTarget = ref(null);
const editPenaltyForm = ref({
    penalty: 'clean',
    penalty_reason: '',
});
const editPenaltyErrors = ref({});
const isEditingPenalty = ref(false);

function openEditPenalty(member) {
    editPenaltyTarget.value = member;
    editPenaltyForm.value = {
        penalty: member.penalty || 'clean',
        penalty_reason: member.penalty_reason || '',
    };
    editPenaltyErrors.value = {};
}
function closeEditPenalty() { editPenaltyTarget.value = null; }
function submitEditPenalty() {
    if (!editPenaltyTarget.value) return;
    isEditingPenalty.value = true;
    editPenaltyErrors.value = {};
    const data = new FormData();
    data.append('_method', 'PUT');
    data.append('penalty', editPenaltyForm.value.penalty);
    data.append('penalty_reason', editPenaltyForm.value.penalty_reason || '');
    router.post(`/dashboard/member/${editPenaltyTarget.value.id}/penalty`, data, {
        forceFormData: true,
        onSuccess: () => { isEditingPenalty.value = false; closeEditPenalty(); },
        onError: (errs) => { isEditingPenalty.value = false; editPenaltyErrors.value = errs; },
    });
}

// ── Badges & Helpers ─────────────────────────────────────────────────────────
const statusClass = {
    'SS DIPONEGORO': 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800',
    'LIFE MEMBER':   'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800',
    'HONORARY':      'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-800',
    'VIRGIN':        'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800',
    'PROSPECT':      'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800',
};
function statusBadgeClass(s) { return statusClass[s] ?? 'bg-gray-100 text-gray-600 border-gray-200'; }

const penaltyClass = {
    'clean':     'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800',
    'warning':   'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800',
    'blacklist': 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800',
    'banned':    'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-800',
};
function penaltyBadgeClass(p) { return penaltyClass[p] ?? 'bg-gray-100 text-gray-600 border-gray-200'; }
function formatPenaltyLabel(p) {
    if (!p) return 'Clean';
    return p.charAt(0).toUpperCase() + p.slice(1);
}
function waLink(no) { return `https://wa.me/${(no||'').replace(/\D/g,'').replace(/^0/,'62')}`; }
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Anggota Penalty" />

        <div class="flex h-full flex-1 flex-col gap-5 p-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400">
                            <UserRoundX class="h-5 w-5" />
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight">Anggota Penalty</h1>
                    </div>
                    <p class="text-sm text-muted-foreground mt-0.5">
                        Daftar anggota yang memiliki status penalty (Warning, Blacklist, atau Banned).
                    </p>
                </div>
            </div>

            <!-- Flash Success -->
            <div
                v-if="flash.success"
                class="flex items-center justify-between rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-950/50 dark:border-emerald-800 dark:text-emerald-300"
            >
                <span>{{ flash.success }}</span>
            </div>

            <!-- Filter Card -->
            <div class="rounded-xl border bg-card p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:w-80">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Cari nama, no kartu, whatsapp, status..."
                        class="pl-9 h-9 text-sm rounded-lg"
                    />
                    <button
                        v-if="search"
                        @click="search = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div class="text-xs text-muted-foreground">
                    Total Anggota Penalty: <span class="font-bold text-foreground">{{ members.total }}</span>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden flex-1 flex flex-col">
                <div class="overflow-x-auto flex-1">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead
                                    class="w-44 text-xs font-semibold uppercase tracking-wider cursor-pointer select-none hover:text-foreground transition-colors"
                                    @click="toggleSort('no_kartu')"
                                >
                                    <div class="flex items-center gap-1">
                                        <span>No Kartu</span>
                                        <ArrowUp v-if="sortBy === 'no_kartu' && sortDir === 'asc'" class="h-3.5 w-3.5 text-red-600" />
                                        <ArrowDown v-else-if="sortBy === 'no_kartu' && sortDir === 'desc'" class="h-3.5 w-3.5 text-red-600" />
                                        <ArrowUpDown v-else class="h-3.5 w-3.5 text-muted-foreground/50" />
                                    </div>
                                </TableHead>

                                <TableHead
                                    class="text-xs font-semibold uppercase tracking-wider cursor-pointer select-none hover:text-foreground transition-colors"
                                    @click="toggleSort('nama_lengkap')"
                                >
                                    <div class="flex items-center gap-1">
                                        <span>Nama Lengkap ( Panggilan )</span>
                                        <ArrowUp v-if="sortBy === 'nama_lengkap' && sortDir === 'asc'" class="h-3.5 w-3.5 text-red-600" />
                                        <ArrowDown v-else-if="sortBy === 'nama_lengkap' && sortDir === 'desc'" class="h-3.5 w-3.5 text-red-600" />
                                        <ArrowUpDown v-else class="h-3.5 w-3.5 text-muted-foreground/50" />
                                    </div>
                                </TableHead>

                                <TableHead class="text-xs font-semibold uppercase tracking-wider">No Whatsapp</TableHead>

                                <TableHead
                                    class="text-xs font-semibold uppercase tracking-wider cursor-pointer select-none hover:text-foreground transition-colors"
                                    @click="toggleSort('status_keanggotaan')"
                                >
                                    <div class="flex items-center gap-1">
                                        <span>Status</span>
                                        <ArrowUp v-if="sortBy === 'status_keanggotaan' && sortDir === 'asc'" class="h-3.5 w-3.5 text-red-600" />
                                        <ArrowDown v-else-if="sortBy === 'status_keanggotaan' && sortDir === 'desc'" class="h-3.5 w-3.5 text-red-600" />
                                        <ArrowUpDown v-else class="h-3.5 w-3.5 text-muted-foreground/50" />
                                    </div>
                                </TableHead>

                                <TableHead
                                    class="text-xs font-semibold uppercase tracking-wider cursor-pointer select-none hover:text-foreground transition-colors"
                                    @click="toggleSort('penalty')"
                                >
                                    <div class="flex items-center gap-1">
                                        <span>Penalty Status</span>
                                        <ArrowUp v-if="sortBy === 'penalty' && sortDir === 'asc'" class="h-3.5 w-3.5 text-red-600" />
                                        <ArrowDown v-else-if="sortBy === 'penalty' && sortDir === 'desc'" class="h-3.5 w-3.5 text-red-600" />
                                        <ArrowUpDown v-else class="h-3.5 w-3.5 text-muted-foreground/50" />
                                    </div>
                                </TableHead>

                                <TableHead class="text-xs font-semibold uppercase tracking-wider">Penalty Reason</TableHead>

                                <TableHead class="w-36 text-xs font-semibold uppercase tracking-wider text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableEmpty v-if="members.data.length === 0" :colspan="7">
                                <div class="flex flex-col items-center gap-2 py-12 text-muted-foreground">
                                    <ShieldAlert class="h-10 w-10 opacity-30" />
                                    <p class="font-medium text-sm">Tidak ada anggota yang berstatus penalty</p>
                                    <p class="text-xs">Semua anggota berstatus clean saat ini.</p>
                                </div>
                            </TableEmpty>

                            <TableRow
                                v-for="member in members.data"
                                :key="member.id"
                                class="hover:bg-muted/40 transition-colors"
                            >
                                <!-- No Kartu -->
                                <TableCell>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-muted text-xs font-mono font-bold text-foreground">
                                        BBMC 38 2026 {{ member.no_kartu }}
                                    </span>
                                </TableCell>

                                <!-- Nama Lengkap ( nama panggilan ) -->
                                <TableCell>
                                    <div class="font-bold text-sm text-foreground">{{ member.nama_lengkap }}</div>
                                    <div class="text-xs text-muted-foreground mt-0.5">
                                        ( {{ member.nama_panggilan }} )
                                    </div>
                                </TableCell>

                                <!-- No Whatsapp -->
                                <TableCell>
                                    <a
                                        :href="waLink(member.no_wa)"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 font-mono text-xs font-semibold text-green-600 hover:underline"
                                    >
                                        <span>{{ member.no_wa }}</span>
                                    </a>
                                </TableCell>

                                <!-- Status Keanggotaan -->
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        :class="['whitespace-nowrap text-xs font-semibold', statusBadgeClass(member.status_keanggotaan)]"
                                    >
                                        {{ member.status_keanggotaan }}
                                    </Badge>
                                </TableCell>

                                <!-- Penalty Status -->
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        :class="['whitespace-nowrap text-xs font-bold uppercase', penaltyBadgeClass(member.penalty)]"
                                    >
                                        {{ formatPenaltyLabel(member.penalty) }}
                                    </Badge>
                                </TableCell>

                                <!-- Penalty Reason -->
                                <TableCell class="max-w-[220px]">
                                    <p class="text-xs text-foreground font-medium line-clamp-2">
                                        {{ member.penalty_reason || '—' }}
                                    </p>
                                </TableCell>

                                <!-- Action -->
                                <TableCell class="text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openEditPenalty(member)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-800 dark:text-amber-400 dark:hover:bg-amber-950/40 text-xs font-semibold transition-colors"
                                            title="Edit Status Penalty"
                                        >
                                            <Pencil class="h-3 w-3" />
                                            <span>Edit</span>
                                        </button>

                                        <a
                                            :href="`/member/${member.no_kartu}`"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition-all shadow-sm active:scale-95"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                            <span>View Detail</span>
                                            <ExternalLink class="h-3 w-3 opacity-80" />
                                        </a>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="members.last_page > 1"
                    class="flex items-center justify-between border-t px-4 py-3 bg-muted/20"
                >
                    <div class="text-xs text-muted-foreground">
                        Menampilkan
                        <span class="font-medium text-foreground">{{ members.from ?? 0 }}</span>
                        -
                        <span class="font-medium text-foreground">{{ members.to ?? 0 }}</span>
                        dari
                        <span class="font-medium text-foreground">{{ members.total }}</span>
                        data
                    </div>

                    <div class="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="members.current_page === 1"
                            @click="applyFilters({ page: 1 })"
                        >
                            <ChevronsLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="!members.prev_page_url"
                            @click="applyFilters({ page: members.current_page - 1 })"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <span class="px-3 text-xs font-semibold text-foreground">
                            Halaman {{ members.current_page }} / {{ members.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="!members.next_page_url"
                            @click="applyFilters({ page: members.current_page + 1 })"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="members.current_page === members.last_page"
                            @click="applyFilters({ page: members.last_page })"
                        >
                            <ChevronsRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ EDIT PENALTY DIALOG ═══ -->
        <Dialog :open="!!editPenaltyTarget" @update:open="(v) => { if (!v) closeEditPenalty() }">
            <DialogContent class="max-w-sm rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-amber-600">
                        <Pencil class="h-4 w-4" /> Edit Status Penalty
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Ubah status penalty untuk <span class="font-semibold text-foreground">{{ editPenaltyTarget?.nama_lengkap }}</span>
                    </DialogDescription>
                </DialogHeader>

                <!-- Error banner -->
                <div v-if="Object.keys(editPenaltyErrors).length" class="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700 space-y-1">
                    <p class="font-semibold">Terdapat kesalahan:</p>
                    <p v-for="(err, k) in editPenaltyErrors" :key="k">• {{ err }}</p>
                </div>

                <div class="py-3 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Status Penalty *</label>
                        <select v-model="editPenaltyForm.penalty" class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:border-red-500 capitalize">
                            <option value="clean">Clean (Hapus Penalty)</option>
                            <option value="warning">Warning</option>
                            <option value="blacklist">Blacklist</option>
                            <option value="banned">Banned</option>
                        </select>
                        <p v-if="editPenaltyErrors.penalty" class="text-red-500 text-xs mt-1">{{ editPenaltyErrors.penalty }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">Alasan Penalty (Penalty Reason)</label>
                        <textarea
                            v-model="editPenaltyForm.penalty_reason"
                            rows="3"
                            placeholder="Tuliskan keterangan / alasan penalty jika ada..."
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:border-red-500 resize-none"
                        ></textarea>
                        <p v-if="editPenaltyErrors.penalty_reason" class="text-red-500 text-xs mt-1">{{ editPenaltyErrors.penalty_reason }}</p>
                    </div>
                </div>

                <DialogFooter class="flex gap-2">
                    <Button variant="outline" class="flex-1" @click="closeEditPenalty" :disabled="isEditingPenalty">Batal</Button>
                    <Button class="flex-1 bg-amber-500 hover:bg-amber-600 text-white" :disabled="isEditingPenalty" @click="submitEditPenalty">
                        <svg v-if="isEditingPenalty" class="mr-1.5 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ isEditingPenalty ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
