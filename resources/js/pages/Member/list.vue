<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Eye,
    Pencil,
    Search,
    Trash2,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    members: Object,
    filters: Object,
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Data Anggota', href: '/dashboard/member' },
];

// ── Search ─────────────────────────────────────────────────────────────────
const search = ref(props.filters?.search ?? '');
let searchTimer;

watch(search, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/dashboard/member', { search: val }, { preserveState: true, replace: true });
    }, 400);
});

function clearSearch() {
    search.value = '';
}

// ── Delete Dialog ──────────────────────────────────────────────────────────
const deleteTarget = ref(null);
const isDeleting = ref(false);

function confirmDelete(member) {
    deleteTarget.value = member;
}
function cancelDelete() {
    deleteTarget.value = null;
}
function doDelete() {
    if (!deleteTarget.value) return;
    isDeleting.value = true;
    router.delete(`/dashboard/member/${deleteTarget.value.id}`, {
        onFinish: () => {
            isDeleting.value = false;
            deleteTarget.value = null;
        },
    });
}

// ── Status Badge ──────────────────────────────────────────────────────────
const statusClass = {
    'SS DIPONEGORO': 'bg-purple-100 text-purple-700 border-purple-200',
    'LIFE MEMBER':   'bg-blue-100 text-blue-700 border-blue-200',
    'HONORARY':      'bg-amber-100 text-amber-700 border-amber-200',
    'VIRGIN':        'bg-green-100 text-green-700 border-green-200',
    'PROSPECT':      'bg-red-100 text-red-700 border-red-200',
};

function statusBadgeClass(s) {
    return statusClass[s] ?? 'bg-gray-100 text-gray-600 border-gray-200';
}

// ── Helpers ────────────────────────────────────────────────────────────────
function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function waLink(no) {
    const clean = no.replace(/\D/g, '').replace(/^0/, '62');
    return `https://wa.me/${clean}`;
}
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
                                    <Link :href="`/dashboard/member/${member.id}`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8" title="Detail">
                                            <Eye class="h-4 w-4 text-blue-500" />
                                        </Button>
                                    </Link>
                                    <Link :href="`/dashboard/member/${member.id}/edit`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8" title="Edit">
                                            <Pencil class="h-4 w-4 text-amber-500" />
                                        </Button>
                                    </Link>
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
                        <!-- First -->
                        <Link :href="members.links[0]?.url ?? '#'"
                            :class="['inline-flex', !members.links[0]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7">
                                <ChevronsLeft class="h-3.5 w-3.5" />
                            </Button>
                        </Link>
                        <!-- Prev -->
                        <Link :href="members.links[1]?.url ?? '#'"
                            :class="['inline-flex', !members.links[1]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7">
                                <ChevronLeft class="h-3.5 w-3.5" />
                            </Button>
                        </Link>

                        <!-- Page Numbers -->
                        <template v-for="link in members.links.slice(1, -1)" :key="link.label">
                            <Link v-if="link.url" :href="link.url">
                                <Button variant="outline" size="icon"
                                    class="h-7 w-7 text-xs font-medium"
                                    :class="link.active ? 'bg-primary text-primary-foreground border-primary hover:bg-primary/90' : ''"
                                >
                                    {{ link.label }}
                                </Button>
                            </Link>
                            <span v-else class="px-1 text-muted-foreground text-xs">…</span>
                        </template>

                        <!-- Next -->
                        <Link :href="members.links[members.links.length - 2]?.url ?? '#'"
                            :class="['inline-flex', !members.links[members.links.length - 2]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7">
                                <ChevronRight class="h-3.5 w-3.5" />
                            </Button>
                        </Link>
                        <!-- Last -->
                        <Link :href="members.links[members.links.length - 1]?.url ?? '#'"
                            :class="['inline-flex', !members.links[members.links.length - 1]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7">
                                <ChevronsRight class="h-3.5 w-3.5" />
                            </Button>
                        </Link>
                    </div>
                </div>

            </div><!-- /Card -->
        </div>

        <!-- Delete Dialog -->
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