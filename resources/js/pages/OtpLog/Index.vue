<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Table, TableBody, TableCell, TableEmpty,
    TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
    ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight,
    Search, RefreshCw, X, ShieldAlert, ShieldCheck, Copy, Eye, EyeOff, FileText, Download
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{ otps: any, filters: any }>();
const page = usePage();
const flash = computed(() => (page.props.flash as Record<string, any>) ?? {});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'OTP Logs', href: '/dashboard/otp-logs' },
];

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? 'all');
let searchTimer: any;

watch([search, status], ([newSearch, newStatus]) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/dashboard/otp-logs', { search: newSearch, status: newStatus }, { preserveState: true, replace: true });
    }, 400);
});
function clearSearch() { search.value = ''; }

const isResending = ref<Record<number, boolean>>({});

function resendOtp(otp: any) {
    if (!confirm('Apakah Anda yakin ingin mengirim ulang OTP ini ke nomor ' + otp.phone + '?')) return;
    
    isResending.value[otp.id] = true;
    router.post(`/dashboard/otp-logs/${otp.id}/resend`, {}, {
        onFinish: () => {
            isResending.value[otp.id] = false;
        }
    });
}

const showOtp = ref<Record<number, boolean>>({});
const showAllOtp = ref(false);

function toggleAllOtp() {
    showAllOtp.value = !showAllOtp.value;
    props.otps.data.forEach((otp: any) => {
        showOtp.value[otp.id] = showAllOtp.value;
    });
}

function toggleOtp(id: number) {
    showOtp.value[id] = !showOtp.value[id];
}
function copyOtp(code: string) {
    navigator.clipboard.writeText(code);
    alert('Kode OTP berhasil disalin: ' + code);
}
</script>

<template>
    <Head title="OTP Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-5 p-4 sm:p-6">
            
            <!-- Flash Messages -->
            <div v-if="flash.success" class="flex items-center gap-3 rounded-xl border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-600 dark:text-green-400 shadow-sm">
                <span>✅</span>
                <span>{{ flash.success }}</span>
            </div>
            <div v-if="page.props.errors && Object.keys(page.props.errors).length > 0" class="flex items-center gap-3 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-600 dark:text-red-400 shadow-sm">
                <span>❌</span>
                <span>{{ Object.values(page.props.errors)[0] }}</span>
            </div>

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="flex items-center gap-2 text-xl font-bold">
                        <ShieldAlert class="h-5 w-5 text-amber-500" />
                        Log OTP
                    </h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        Memantau riwayat pengiriman OTP kepada anggota untuk login dan pencalonan.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="`/dashboard/otp-logs/export/csv?search=${search}&status=${status}`" target="_blank">
                        <Button variant="outline" size="sm" class="h-9 gap-2">
                            <Download class="h-4 w-4" />
                            CSV
                        </Button>
                    </a>
                    <a :href="`/dashboard/otp-logs/export/pdf?search=${search}&status=${status}`" target="_blank">
                        <Button variant="outline" size="sm" class="h-9 gap-2">
                            <FileText class="h-4 w-4" />
                            PDF
                        </Button>
                    </a>
                </div>
            </div>

            <!-- Card Table -->
            <div class="rounded-2xl border bg-card text-card-foreground shadow-sm overflow-hidden">
                
                <!-- Toolbar -->
                <div class="flex flex-col gap-3 border-b bg-muted/40 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                            <Input
                                v-model="search"
                                placeholder="Cari no kartu, nama, hp..."
                                class="pl-9 pr-8 text-sm focus-visible:ring-amber-500"
                            />
                            <button v-if="search" @click="clearSearch"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors">
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>
                        <select
                            v-model="status"
                            class="flex h-9 w-full sm:w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber-500"
                        >
                            <option value="all">Semua Status</option>
                            <option value="verified">Terverifikasi</option>
                            <option value="unverified">Menunggu</option>
                        </select>
                    </div>
                    <p class="text-xs text-muted-foreground whitespace-nowrap">
                        Menampilkan {{ otps.from ?? 0 }}–{{ otps.to ?? 0 }} dari {{ otps.total }} data
                    </p>
                </div>

                <!-- Table -->
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-10 text-xs font-semibold uppercase tracking-wider">#</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Tgl / Waktu</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Anggota</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">No HP</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">
                                <div class="flex items-center gap-1.5 cursor-pointer hover:text-amber-600 transition-colors select-none w-fit" @click="toggleAllOtp" :title="showAllOtp ? 'Sembunyikan Semua OTP' : 'Tampilkan Semua OTP'">
                                    Kode OTP
                                    <Eye v-if="!showAllOtp" class="h-3.5 w-3.5 text-muted-foreground hover:text-amber-600" />
                                    <EyeOff v-else class="h-3.5 w-3.5 text-amber-600" />
                                </div>
                            </TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider">Status</TableHead>
                            <TableHead class="text-xs font-semibold uppercase tracking-wider text-center">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableEmpty v-if="otps.data.length === 0" :colspan="7">
                            <div class="flex flex-col items-center gap-2 py-12 text-muted-foreground">
                                <ShieldAlert class="h-10 w-10 opacity-30 text-amber-500" />
                                <p class="font-medium text-sm">Tidak ada data log OTP</p>
                            </div>
                        </TableEmpty>

                        <TableRow v-for="(otp, index) in otps.data" :key="otp.id" class="transition-colors">
                            <TableCell class="text-muted-foreground text-xs w-10">
                                {{ (otps.current_page - 1) * otps.per_page + index + 1 }}
                            </TableCell>

                            <TableCell class="text-xs whitespace-nowrap">
                                <span class="font-medium">{{ new Date(otp.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span><br/>
                                <span class="text-muted-foreground">{{ new Date(otp.created_at).toLocaleTimeString('id-ID') }}</span>
                            </TableCell>

                            <TableCell>
                                <div v-if="otp.member">
                                    <span class="text-sm font-semibold">{{ otp.member.nama_lengkap }}</span>
                                    <div class="text-[10px] text-muted-foreground font-mono bg-muted inline-block px-1.5 rounded mt-0.5">
                                        {{ otp.member.no_kartu }}
                                    </div>
                                </div>
                                <span v-else class="text-xs text-muted-foreground italic">Member Deleted</span>
                            </TableCell>

                            <TableCell class="font-mono text-xs">
                                {{ otp.phone }}
                            </TableCell>

                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-sm tracking-widest cursor-pointer select-none" @click="toggleOtp(otp.id)" title="Klik untuk lihat">
                                        {{ showOtp[otp.id] ? otp.otp : '••••••' }}
                                    </span>
                                    <button @click="copyOtp(otp.otp)" class="text-muted-foreground hover:text-amber-600 transition-colors" title="Salin OTP">
                                        <Copy class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </TableCell>

                            <TableCell>
                                <div class="flex flex-col gap-1">
                                    <Badge v-if="otp.is_verified" variant="secondary" class="bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400 text-[10px] font-semibold flex w-fit items-center gap-1">
                                        <ShieldCheck class="h-3 w-3" /> Terverifikasi
                                    </Badge>
                                    <Badge v-else variant="secondary" class="bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 text-[10px] font-semibold flex w-fit items-center gap-1">
                                        <ShieldAlert class="h-3 w-3" /> Menunggu
                                    </Badge>
                                    
                                    <span class="text-[10px] text-muted-foreground whitespace-nowrap">
                                        Exp: {{ new Date(otp.expires_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}
                                        <span v-if="!otp.is_verified && new Date(otp.expires_at) < new Date()" class="text-red-500 font-bold ml-1">(Expired)</span>
                                    </span>
                                </div>
                            </TableCell>

                            <TableCell class="text-center">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-8 text-xs font-semibold flex items-center gap-1 hover:bg-amber-500 hover:text-white border-amber-200 text-amber-700 mx-auto"
                                    @click="resendOtp(otp)"
                                    :disabled="isResending[otp.id]"
                                >
                                    <RefreshCw class="h-3.5 w-3.5" :class="isResending[otp.id] ? 'animate-spin' : ''" />
                                    {{ isResending[otp.id] ? 'Kirim...' : 'Resend' }}
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="otps.last_page > 1" class="flex flex-col items-center justify-between gap-3 border-t bg-muted/40 px-4 py-3 sm:flex-row">
                    <p class="text-xs text-muted-foreground">
                        Halaman <span class="font-semibold text-foreground">{{ otps.current_page }}</span>
                        dari <span class="font-semibold text-foreground">{{ otps.last_page }}</span>
                    </p>
                    <div class="flex items-center gap-1 flex-wrap justify-center">
                        <Link :href="otps.links[0]?.url ?? '#'" :class="['inline-flex', !otps.links[0]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronsLeft class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <Link :href="otps.links[1]?.url ?? '#'" :class="['inline-flex', !otps.links[1]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronLeft class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <template v-for="link in otps.links.slice(1, -1)" :key="link.label">
                            <Link v-if="link.url" :href="link.url">
                                <Button variant="outline" size="icon" class="h-7 w-7 text-xs font-medium" :class="link.active ? 'bg-amber-600 text-white border-amber-600 hover:bg-amber-700' : ''">
                                    {{ link.label }}
                                </Button>
                            </Link>
                            <span v-else class="px-1 text-muted-foreground text-xs">…</span>
                        </template>
                        <Link :href="otps.links[otps.links.length - 2]?.url ?? '#'" :class="['inline-flex', !otps.links[otps.links.length - 2]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronRight class="h-3.5 w-3.5" /></Button>
                        </Link>
                        <Link :href="otps.links[otps.links.length - 1]?.url ?? '#'" :class="['inline-flex', !otps.links[otps.links.length - 1]?.url ? 'pointer-events-none opacity-40' : '']">
                            <Button variant="outline" size="icon" class="h-7 w-7"><ChevronsRight class="h-3.5 w-3.5" /></Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
