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
    Eye, Pencil, Search, Trash2, Users, X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({ members: Object, filters: Object });
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Data Anggota', href: '/dashboard/member' },
];

// ── Search ──────────────────────────────────────────────────────────────────
const search = ref(props.filters?.search ?? '');
let searchTimer;
watch(search, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/dashboard/member', { search: val }, { preserveState: true, replace: true });
    }, 400);
});
function clearSearch() { search.value = ''; }

// ── View Modal ───────────────────────────────────────────────────────────────
const viewTarget = ref(null);
function openView(member) { viewTarget.value = member; }
function closeView() { viewTarget.value = null; }

// ── Edit Modal ───────────────────────────────────────────────────────────────
const editTarget = ref(null);
const editForm = ref({});
const editErrors = ref({});
const isEditing = ref(false);
const editFotoPreview = ref(null);
const editFotoFile = ref(null);

const chapterList = [
    { name: 'Mother Chapter', checkpoints: ['Bandung','Subang','Bogor','Sukabumi','Garut','Sumedang','Cirebon'] },
    { name: 'Jakarta Chapter', checkpoints: [] },
    { name: 'Sumatera Chapter', checkpoints: ['Bangka Belitung','Palembang','Medan','Lampung','Batam'] },
    { name: 'Central Java Chapter', checkpoints: ['Pekalongan','Kudus','Jepara','Solo','Sleman','Jogja'] },
    { name: 'East Java Chapter', checkpoints: ['Mojokerto','Malang'] },
    { name: 'Bali Chapter', checkpoints: [] },
    { name: 'Lombok Chapter', checkpoints: [] },
    { name: 'Borneo Chapter', checkpoints: [] },
    { name: 'USA Chapter', checkpoints: [] },
];
const editCheckpoints = computed(() => chapterList.find(c => c.name === editForm.value.chapter)?.checkpoints ?? []);
const showEditRegion = computed(() => editForm.value.chapter === 'Mother Chapter' && editForm.value.checkpoint === 'Bandung');

watch(() => editForm.value.chapter, () => { editForm.value.checkpoint = ''; editForm.value.region = ''; });
watch(() => editForm.value.checkpoint, (v) => { if (v !== 'Bandung') editForm.value.region = ''; });

function openEdit(member) {
    editTarget.value = member;
    editForm.value = { ...member };
    editErrors.value = {};
    editFotoPreview.value = member.foto ? `/storage/${member.foto}` : null;
    editFotoFile.value = null;
}
function closeEdit() { editTarget.value = null; editFotoPreview.value = null; editFotoFile.value = null; }

function handleEditFoto(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    editFotoFile.value = file;
    const reader = new FileReader();
    reader.onload = (ev) => { editFotoPreview.value = ev.target.result; };
    reader.readAsDataURL(file);
}
function handleEditNoWa(e) {
    let d = e.target.value.replace(/\D/g, '');
    if (d.startsWith('0')) d = '62' + d.slice(1);
    if (d.length > 0 && !d.startsWith('62')) d = '62' + d;
    editForm.value.no_wa = d; e.target.value = d;
}
function handleEditTanggal(e) {
    let raw = e.target.value.replace(/\D/g, '').slice(0, 8), m = '';
    if (raw.length > 0) m += raw.substring(0, 2);
    if (raw.length >= 3) m += '/' + raw.substring(2, 4);
    if (raw.length >= 5) m += '/' + raw.substring(4, 8);
    editForm.value.tanggal_lahir = m; e.target.value = m;
}
function submitEdit() {
    if (!editTarget.value) return;
    isEditing.value = true; editErrors.value = {};
    const data = new FormData();
    data.append('_method', 'PUT');
    Object.entries(editForm.value).forEach(([k, v]) => { if (v !== null && v !== undefined) data.append(k, String(v)); });
    if (editFotoFile.value) data.append('foto', editFotoFile.value);
    router.post(`/dashboard/member/${editTarget.value.id}`, data, {
        forceFormData: true,
        onSuccess: () => { isEditing.value = false; closeEdit(); },
        onError: (errs) => { isEditing.value = false; editErrors.value = errs; },
    });
}

// ── Delete Dialog ────────────────────────────────────────────────────────────
const deleteTarget = ref(null);
const isDeleting = ref(false);
function confirmDelete(member) { deleteTarget.value = member; }
function cancelDelete() { deleteTarget.value = null; }
function doDelete() {
    if (!deleteTarget.value) return;
    isDeleting.value = true;
    router.delete(`/dashboard/member/${deleteTarget.value.id}`, {
        onFinish: () => { isDeleting.value = false; deleteTarget.value = null; },
    });
}

// ── Helpers ──────────────────────────────────────────────────────────────────
const statusClass = {
    'SS DIPONEGORO': 'bg-purple-100 text-purple-700 border-purple-200',
    'LIFE MEMBER':   'bg-blue-100 text-blue-700 border-blue-200',
    'HONORARY':      'bg-amber-100 text-amber-700 border-amber-200',
    'VIRGIN':        'bg-green-100 text-green-700 border-green-200',
    'PROSPECT':      'bg-red-100 text-red-700 border-red-200',
};
function statusBadgeClass(s) { return statusClass[s] ?? 'bg-gray-100 text-gray-600 border-gray-200'; }
function waLink(no) { return `https://wa.me/${no.replace(/\D/g,'').replace(/^0/,'62')}`; }
</script>

<template>
    <Head title="Data Anggota" />

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
                        <Users class="h-5 w-5 text-red-500" />
                        Data Anggota BBMC
                    </h1>
                    <p class="mt-0.5 text-sm ">
                        Total <span class="font-semibold text-red-600">{{ members.total }}</span> anggota terdaftar
                    </p>
                </div>
                <Link href="/member/register">
                    <Button size="sm" class="bg-red-600 hover:bg-red-700 text-white shadow-sm w-full sm:w-auto">
                        + Tambah Anggota
                    </Button>
                </Link>
            </div>

            <!-- Card -->
            <div class="rounded-2xl border bg-card text-card-foreground shadow-sm overflow-hidden">

                <!-- Toolbar -->
                <div class="flex flex-col gap-3 border-b bg-muted/40 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:w-72">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                        <Input
                            v-model="search"
                            placeholder="Cari nama, no.kartu, chapter..."
                            class="pl-9 pr-8 text-sm"
                        />
                        <button v-if="search" @click="clearSearch"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors">
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <p class="text-xs text-muted-foreground whitespace-nowrap">
                        Menampilkan {{ members.from ?? 0 }}–{{ members.to ?? 0 }} dari {{ members.total }} data
                    </p>
                </div>

                <!-- Shadcn Table — wrapper handles overflow-x-auto -->
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-10 text-xs font-semibold uppercase tracking-wider">#</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">No. Kartu</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Nama Lengkap</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">No. WA</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Status</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Chapter</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Checkpoint</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Terdaftar</TableHead>
                            <TableHead class="text-center text-xs font-semibold uppercase tracking-wider">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <!-- Empty -->
                        <TableEmpty v-if="members.data.length === 0" :colspan="9">
                            <div class="flex flex-col items-center gap-2 py-12 text-muted-foreground">
                                <Users class="h-10 w-10 opacity-30" />
                                <p class="font-medium text-sm">Tidak ada data anggota</p>
                                <p class="text-xs">
                                    {{ search ? 'Coba ubah kata kunci pencarian.' : 'Belum ada anggota yang terdaftar.' }}
                                </p>
                            </div>
                        </TableEmpty>

                        <!-- Rows -->
                        <TableRow
                            v-for="(member, index) in members.data"
                            :key="member.id"
                            class="transition-colors"
                        >
                            <!-- # -->
                            <TableCell class="text-muted-foreground text-xs w-10">
                                {{ (members.current_page - 1) * members.per_page + index + 1 }}
                            </TableCell>

                            <!-- No Kartu -->
                            <TableCell>
                                <span class="rounded-md bg-muted px-2 py-0.5 font-mono text-xs font-semibold text-muted-foreground">
                                    {{ member.no_kartu || '—' }}
                                </span>
                            </TableCell>

                            <!-- Nama -->
                            <TableCell class="font-semibold whitespace-nowrap">
                                {{ member.nama_lengkap }}
                            </TableCell>

                            <!-- WA -->
                            <TableCell>
                                <a :href="waLink(member.no_wa)" target="_blank"
                                    class="inline-flex items-center gap-1 text-green-600 hover:underline text-xs font-medium whitespace-nowrap">
                                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.105.547 4.084 1.508 5.799L0 24l6.336-1.485A11.937 11.937 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.007-1.366l-.36-.213-3.728.874.937-3.614-.235-.372A9.818 9.818 0 012.182 12 9.818 9.818 0 0112 2.182 9.818 9.818 0 0121.818 12 9.818 9.818 0 0112 21.818z"/>
                                    </svg>
                                    {{ member.no_wa }}
                                </a>
                            </TableCell>

                            <!-- Status Badge -->
                            <TableCell>
                                <Badge
                                    variant="outline"
                                    :class="['whitespace-nowrap text-xs font-semibold', statusBadgeClass(member.status_keanggotaan)]"
                                >
                                    {{ member.status_keanggotaan }}
                                </Badge>
                            </TableCell>

                            <!-- Chapter -->
                            <TableCell class="whitespace-nowrap text-sm">
                                {{ member.chapter }}
                            </TableCell>

                            <!-- Checkpoint -->
                            <TableCell class="text-xs whitespace-nowrap">
                                <span :class="member.checkpoint ? 'text-foreground' : 'text-muted-foreground'">
                                    {{ member.checkpoint || '—' }}
                                </span>
                                <span
                                    v-if="member.checkpoint === 'Bandung' && member.region"
                                    class="mt-0.5 flex items-center gap-1 text-[10px] font-semibold text-blue-600 dark:text-blue-400"
                                >
                                    <svg class="h-2.5 w-2.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                                        <circle cx="12" cy="9" r="2.5"/>
                                    </svg>
                                    {{ member.region }}
                                </span>
                            </TableCell>

                            <!-- Terdaftar Sejak -->
                            <TableCell class="text-muted-foreground text-xs whitespace-nowrap">
                                {{ member.terdaftar_sejak }}
                            </TableCell>

                            <!-- Actions -->
                            <TableCell class="text-center">
                                <div class="inline-flex items-center gap-1">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" title="Detail" @click="openView(member)">
                                        <Eye class="h-4 w-4 text-blue-500" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8" title="Edit" @click="openEdit(member)">
                                        <Pencil class="h-4 w-4 text-amber-500" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8" title="Hapus" @click="confirmDelete(member)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="members.last_page > 1"
                    class="flex flex-col items-center justify-between gap-3 border-t bg-muted/40 px-4 py-3 sm:flex-row">
                    <p class="text-xs text-muted-foreground">
                        Halaman <span class="font-semibold text-foreground">{{ members.current_page }}</span>
                        dari <span class="font-semibold text-foreground">{{ members.last_page }}</span>
                    </p>
                    <div class="flex items-center gap-1 flex-wrap justify-center">
                        <Link :href="members.links[0]?.url ?? '#'"
                            :class="['inline-flex', !members.links[0]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronsLeft class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <Link :href="members.links[1]?.url ?? '#'"
                            :class="['inline-flex', !members.links[1]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronLeft class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <template v-for="link in members.links.slice(1, -1)" :key="link.label">
                            <Link v-if="link.url" :href="link.url">
                                <Button variant="outline" size="icon" class="h-7 w-7 text-xs font-medium"
                                    :class="link.active ? 'bg-primary text-primary-foreground border-primary hover:bg-primary/90' : ''">
                                    {{ link.label }}
                                </Button>
                            </Link>
                            <span v-else class="px-1 text-muted-foreground text-xs">…</span>
                        </template>
                        <Link :href="members.links[members.links.length - 2]?.url ?? '#'"
                            :class="['inline-flex', !members.links[members.links.length - 2]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronRight class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <Link :href="members.links[members.links.length - 1]?.url ?? '#'"
                            :class="['inline-flex', !members.links[members.links.length - 1]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronsRight class="h-3.5 w-3.5" /></Button>
                        </Link>
                    </div>
                </div>

            </div><!-- /Card -->
        </div>

        <!-- ═══ VIEW MODAL (Member Card) ═══ -->
        <Dialog :open="!!viewTarget" @update:open="(v) => { if (!v) closeView() }">
            <DialogContent class="max-w-md p-0 overflow-hidden rounded-2xl">
                <!-- Card Header — gradien merah -->
                <div class="relative bg-gradient-to-br from-red-700 via-red-600 to-red-500 px-6 pt-8 pb-14 text-white">
                    <div class="absolute inset-0 opacity-10"
                        style="background-image:repeating-linear-gradient(45deg,#fff 0,#fff 1px,transparent 0,transparent 50%);background-size:10px 10px;"></div>
                    <div class="relative flex items-center gap-4">
                        <!-- Foto / Avatar -->
                        <div class="h-16 w-16 shrink-0 rounded-full border-4 border-white/40 shadow-lg overflow-hidden bg-white/20">
                            <img v-if="viewTarget?.foto" :src="`/storage/${viewTarget.foto}`"
                                class="h-full w-full object-cover" alt="Foto" />
                            <div v-else class="h-full w-full flex items-center justify-center text-2xl font-black text-white/80">
                                {{ viewTarget?.nama_lengkap?.charAt(0) }}
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-red-200">Member Card</p>
                            <h2 class="text-lg font-black leading-tight">{{ viewTarget?.nama_lengkap }}</h2>
                            <p class="text-sm text-red-100 font-medium">"{{ viewTarget?.nama_panggilan }}"</p>
                        </div>
                        <!-- No Kartu pojok kanan -->
                        <div class="ml-auto text-right">
                            <p class="text-[10px] text-red-200 uppercase tracking-widest">No. Kartu</p>
                            <p class="font-mono text-xl font-black">{{ viewTarget?.no_kartu ? `BBMC 36 2026 ${viewTarget.no_kartu}` : '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="relative -mt-8 mx-4 mb-4 rounded-xl bg-card border shadow-md px-5 py-4 space-y-3">
                    <!-- Status badge -->
                    <div class="flex items-center justify-between">
                        <Badge variant="outline" :class="['text-xs font-bold px-3 py-1', statusBadgeClass(viewTarget?.status_keanggotaan)]">
                            {{ viewTarget?.status_keanggotaan }}
                        </Badge>
                        <span class="text-xs text-muted-foreground">Sejak {{ viewTarget?.terdaftar_sejak || '—' }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Chapter</p>
                            <p class="font-semibold mt-0.5">{{ viewTarget?.chapter || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Checkpoint</p>
                            <p class="font-semibold mt-0.5">{{ viewTarget?.checkpoint || '—' }}</p>
                        </div>
                        <div v-if="viewTarget?.region">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Region</p>
                            <p class="font-semibold mt-0.5 text-blue-600">{{ viewTarget.region }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">No. WA</p>
                            <a :href="waLink(viewTarget?.no_wa ?? '')" target="_blank"
                                class="font-mono font-semibold mt-0.5 text-green-600 hover:underline block">
                                {{ viewTarget?.no_wa || '—' }}
                            </a>
                        </div>
                    </div>
                    <div class="pt-1 border-t text-xs text-muted-foreground text-center">
                        {{ viewTarget?.tempat_lahir }}, {{ viewTarget?.tanggal_lahir }}
                    </div>
                </div>

                <DialogFooter class="px-4 pb-4">
                    <Button variant="outline" class="w-full" @click="closeView">Tutup</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ═══ EDIT MODAL ═══ -->
        <Dialog :open="!!editTarget" @update:open="(v) => { if (!v) closeEdit() }">
            <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-amber-600">
                        <Pencil class="h-4 w-4" /> Edit Data Anggota
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Edit data untuk <span class="font-semibold text-foreground">{{ editTarget?.nama_lengkap }}</span>
                    </DialogDescription>
                </DialogHeader>

                <!-- Error banner -->
                <div v-if="Object.keys(editErrors).length" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-xs text-red-700 space-y-1">
                    <p class="font-semibold">Terdapat kesalahan:</p>
                    <p v-for="(err, k) in editErrors" :key="k">• {{ err }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 py-1">
                    <!-- Nama Lengkap -->
                    <div>
                        <label class="el">Nama Lengkap *</label>
                        <input v-model="editForm.nama_lengkap" class="ei" :class="editErrors.nama_lengkap?'border-red-400':''"/>
                        <p v-if="editErrors.nama_lengkap" class="ee">{{ editErrors.nama_lengkap }}</p>
                    </div>
                    <!-- Nama Panggilan -->
                    <div>
                        <label class="el">Nama Panggilan *</label>
                        <input v-model="editForm.nama_panggilan" class="ei" :class="editErrors.nama_panggilan?'border-red-400':''"/>
                        <p v-if="editErrors.nama_panggilan" class="ee">{{ editErrors.nama_panggilan }}</p>
                    </div>
                    <!-- Tempat Lahir -->
                    <div>
                        <label class="el">Tempat Lahir *</label>
                        <input v-model="editForm.tempat_lahir" class="ei" :class="editErrors.tempat_lahir?'border-red-400':''"/>
                    </div>
                    <!-- Tanggal Lahir -->
                    <div>
                        <label class="el">Tanggal Lahir *</label>
                        <input :value="editForm.tanggal_lahir" @input="handleEditTanggal" placeholder="DD/MM/YYYY" maxlength="10" inputmode="numeric" class="ei font-mono" :class="editErrors.tanggal_lahir?'border-red-400':''"/>
                        <p v-if="editErrors.tanggal_lahir" class="ee">{{ editErrors.tanggal_lahir }}</p>
                    </div>
                    <!-- Jenis Kelamin -->
                    <div>
                        <label class="el">Jenis Kelamin *</label>
                        <select v-model="editForm.jenis_kelamin" class="ei">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <!-- Gol Darah -->
                    <div>
                        <label class="el">Gol. Darah *</label>
                        <select v-model="editForm.gol_darah" class="ei">
                            <option value="-">Tidak Tau</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="AB">AB</option>
                            <option value="O">O</option>
                        </select>
                    </div>
                    <!-- NIK -->
                    <div class="sm:col-span-2">
                        <label class="el">NIK *</label>
                        <input v-model="editForm.nik" maxlength="16" inputmode="numeric" class="ei font-mono" :class="editErrors.nik?'border-red-400':''"/>
                        <p v-if="editErrors.nik" class="ee">{{ editErrors.nik }}</p>
                    </div>
                    <!-- Alamat -->
                    <div class="sm:col-span-2">
                        <label class="el">Alamat *</label>
                        <textarea v-model="editForm.alamat" rows="2" class="ei resize-none"></textarea>
                    </div>
                    <!-- No WA -->
                    <div>
                        <label class="el">No. WhatsApp *</label>
                        <input :value="editForm.no_wa" @input="handleEditNoWa" inputmode="numeric" class="ei font-mono" :class="editErrors.no_wa?'border-red-400':''"/>
                    </div>
                    <!-- Email -->
                    <div>
                        <label class="el">Email</label>
                        <input v-model="editForm.email" type="email" class="ei"/>
                    </div>
                    <!-- Profesi -->
                    <div>
                        <label class="el">Profesi</label>
                        <input v-model="editForm.profesi" class="ei"/>
                    </div>
                    <!-- No Kartu -->
                    <div>
                        <label class="el">No. Kartu</label>
                        <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:border-amber-400 focus-within:ring-2 focus-within:ring-amber-100 transition-all">
                            <span class="bg-muted px-3 py-2 text-xs font-mono font-bold text-muted-foreground border-r whitespace-nowrap">BBMC 36 2026</span>
                            <input v-model="editForm.no_kartu" maxlength="4" inputmode="numeric"
                                @input="editForm.no_kartu = editForm.no_kartu.replace(/\D/g,'').slice(0,4)"
                                placeholder="0000" class="flex-1 min-w-0 bg-background px-3 py-2 text-sm font-mono font-bold outline-none tracking-widest"/>
                        </div>
                    </div>
                    <!-- Status Keanggotaan -->
                    <div>
                        <label class="el">Status Keanggotaan *</label>
                        <select v-model="editForm.status_keanggotaan" class="ei">
                            <option>SS DIPONEGORO</option>
                            <option>LIFE MEMBER</option>
                            <option>HONORARY</option>
                            <option>VIRGIN</option>
                            <option>PROSPECT</option>
                        </select>
                    </div>
                    <!-- Chapter -->
                    <div>
                        <label class="el">Chapter *</label>
                        <select v-model="editForm.chapter" class="ei">
                            <option v-for="ch in chapterList" :key="ch.name" :value="ch.name">{{ ch.name }}</option>
                        </select>
                    </div>
                    <!-- Checkpoint -->
                    <div v-if="editCheckpoints.length">
                        <label class="el">Checkpoint *</label>
                        <select v-model="editForm.checkpoint" class="ei">
                            <option value="" disabled>Pilih checkpoint</option>
                            <option v-for="cp in editCheckpoints" :key="cp" :value="cp">{{ cp }}</option>
                        </select>
                    </div>
                    <!-- Region -->
                    <div v-if="showEditRegion">
                        <label class="el">Region *</label>
                        <select v-model="editForm.region" class="ei">
                            <option value="" disabled>Pilih region</option>
                            <option>West Region</option>
                            <option>East Region</option>
                            <option>South Region</option>
                            <option>North Region</option>
                        </select>
                    </div>
                    <!-- Terdaftar Sejak -->
                    <div>
                        <label class="el">Terdaftar Sejak (Tahun)</label>
                        <input v-model="editForm.terdaftar_sejak" type="number" min="1970" :max="new Date().getFullYear()" class="ei"/>
                    </div>
                    <!-- Foto -->
                    <div class="sm:col-span-2">
                        <label class="el">Foto</label>
                        <div class="flex items-center gap-4">
                            <div class="h-14 w-14 rounded-full border-2 border-dashed border-muted-foreground/30 overflow-hidden bg-muted flex items-center justify-center shrink-0">
                                <img v-if="editFotoPreview" :src="editFotoPreview" class="h-full w-full object-cover"/>
                                <span v-else class="text-xs text-muted-foreground">Foto</span>
                            </div>
                            <label class="cursor-pointer">
                                <span class="inline-block rounded-lg border border-dashed border-muted-foreground/40 px-4 py-2 text-xs text-muted-foreground hover:border-amber-400 hover:text-amber-600 transition-colors">
                                    Pilih foto baru...
                                </span>
                                <input type="file" accept="image/*" class="hidden" @change="handleEditFoto"/>
                            </label>
                        </div>
                    </div>
                </div>

                <DialogFooter class="mt-2 flex gap-2">
                    <Button variant="outline" class="flex-1" @click="closeEdit" :disabled="isEditing">Batal</Button>
                    <Button class="flex-1 bg-amber-500 hover:bg-amber-600 text-white" :disabled="isEditing" @click="submitEdit">
                        <svg v-if="isEditing" class="mr-1.5 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ isEditing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ═══ DELETE DIALOG ═══ -->
        <Dialog :open="!!deleteTarget" @update:open="(v) => { if (!v) cancelDelete() }">
            <DialogContent class="max-w-sm rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-red-600">
                        <Trash2 class="h-5 w-5" /> Hapus Anggota
                    </DialogTitle>
                    <DialogDescription class="mt-2 text-sm">
                        Anda akan menghapus data anggota
                        <span class="font-semibold text-foreground">{{ deleteTarget?.nama_lengkap }}</span>.
                        Tindakan ini <span class="font-semibold text-destructive">tidak dapat dibatalkan</span>.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="mt-4 flex gap-2">
                    <Button variant="outline" class="flex-1" @click="cancelDelete">Batal</Button>
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

<style scoped>
.el { @apply block text-[10px] font-semibold uppercase tracking-wider text-muted-foreground mb-1; }
.ei { @apply w-full border border-input bg-background rounded-md px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100 transition-all; }
.ee { @apply text-red-500 text-xs mt-0.5; }
</style>