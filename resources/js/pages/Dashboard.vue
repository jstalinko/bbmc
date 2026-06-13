<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Users, UserCheck, Shield, Calendar, ArrowRight, MapPin } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface Stats {
    total_members: number;
    total_candidates: number;
    total_candidates_ditetapkan: number;
    total_life_members: number;
}

interface Member {
    id: number;
    no_kartu: string;
    nama_lengkap: string;
    nama_panggilan: string;
    chapter: string;
    checkpoint: string;
    status_keanggotaan: string;
    created_at: string;
}

defineProps<{
    stats: Stats;
    latest_members: Member[];
}>();

const statusClass = {
    'SS DIPONEGORO': 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-800/40',
    'LIFE MEMBER':   'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/40',
    'HONORARY':      'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/40',
    'VIRGIN':        'bg-green-100 text-green-700 border-green-200 dark:bg-green-950/40 dark:text-green-400 dark:border-green-800/40',
    'PROSPECT':      'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800/40',
};

function statusBadgeClass(s: string) {
    return statusClass[s as keyof typeof statusClass] ?? 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800/40 dark:text-gray-400';
}

function formatDate(dateStr: string) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
}
</script>

<template>
    <Head title="Dashboard Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6 max-w-7xl mx-auto w-full">
            
            <!-- Welcome Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-bold tracking-tight">Dashboard Overview</h1>
                <p class="text-sm text-muted-foreground">
                    Selamat datang kembali di panel administrasi BBMC Member & Election.
                </p>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Card 1: Total Members -->
                <div class="relative overflow-hidden rounded-2xl border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-red-500/20 group">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Total Anggota</p>
                            <p class="text-3xl font-bold tracking-tight">{{ stats.total_members }}</p>
                        </div>
                        <div class="rounded-xl bg-red-500/10 p-3 text-red-500 transition-colors group-hover:bg-red-500/20">
                            <Users class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs text-muted-foreground">
                        <span>{{ stats.total_life_members }} Life Members</span>
                        <Link href="/dashboard/member" class="text-red-500 hover:underline inline-flex items-center gap-1">
                            Lihat semua <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>

                <!-- Card 2: Candidates -->
                <div class="relative overflow-hidden rounded-2xl border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-amber-500/20 group">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Calon Presidente</p>
                            <p class="text-3xl font-bold tracking-tight">{{ stats.total_candidates }}</p>
                        </div>
                        <div class="rounded-xl bg-amber-500/10 p-3 text-amber-500 transition-colors group-hover:bg-amber-500/20">
                            <UserCheck class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs text-muted-foreground">
                        <span>{{ stats.total_candidates_ditetapkan }} Calon Ditetapkan</span>
                        <Link href="/dashboard/candidate" class="text-amber-500 hover:underline inline-flex items-center gap-1">
                            Kelola calon <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>

                <!-- Card 3: Quick Info (Life Member Percent / Quick Link) -->
                <div class="relative overflow-hidden rounded-2xl border bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-purple-500/20 group sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Life Member Ratio</p>
                            <p class="text-3xl font-bold tracking-tight">
                                {{ stats.total_members > 0 ? Math.round((stats.total_life_members / stats.total_members) * 100) : 0 }}%
                            </p>
                        </div>
                        <div class="rounded-xl bg-purple-500/10 p-3 text-purple-500 transition-colors group-hover:bg-purple-500/20">
                            <Shield class="h-6 w-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs text-muted-foreground">
                        <span>Proporsi dari total anggota</span>
                        <Link href="/dashboard/setting-pemilihan" class="text-purple-500 hover:underline inline-flex items-center gap-1">
                            Pengaturan Pemilihan <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Latest Members Table Section -->
            <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b bg-muted/40 px-6 py-4">
                    <div>
                        <h2 class="text-md font-bold flex items-center gap-2">
                            <Users class="h-4 w-4 text-red-500" />
                            Anggota Terbaru Terdaftar
                        </h2>
                        <p class="text-xs text-muted-foreground mt-0.5">5 anggota yang baru saja mendaftar ke sistem</p>
                    </div>
                    <Link href="/dashboard/member">
                        <Button size="sm" variant="outline" class="text-xs gap-1">
                            Semua Anggota <ArrowRight class="h-3.5 w-3.5" />
                        </Button>
                    </Link>
                </div>

                <!-- Table wrapper -->
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="text-xs font-semibold uppercase tracking-wider">No. Kartu</TableHead>
                                <TableHead class="text-xs font-semibold uppercase tracking-wider">Nama Lengkap</TableHead>
                                <TableHead class="text-xs font-semibold uppercase tracking-wider">Chapter</TableHead>
                                <TableHead class="text-xs font-semibold uppercase tracking-wider">Status</TableHead>
                                <TableHead class="text-xs font-semibold uppercase tracking-wider">Tanggal Daftar</TableHead>
                                <TableHead class="text-right text-xs font-semibold uppercase tracking-wider">Detail</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="latest_members.length === 0">
                                <TableCell colspan="6" class="text-center py-8 text-muted-foreground text-sm">
                                    Belum ada data anggota terdaftar.
                                </TableCell>
                            </TableRow>
                            <TableRow 
                                v-for="member in latest_members" 
                                :key="member.id"
                                class="transition-colors hover:bg-muted/30"
                            >
                                <TableCell>
                                    <span class="rounded-md bg-muted px-2 py-0.5 font-mono text-xs font-semibold text-muted-foreground">
                                       BBMC 38 2026 {{ member.no_kartu || '—' }}
                                    </span>
                                </TableCell>
                                <TableCell class="font-medium whitespace-nowrap">
                                    {{ member.nama_lengkap }}
                                    <span v-if="member.nama_panggilan" class="text-xs text-muted-foreground ml-1">
                                        ("{{ member.nama_panggilan }}")
                                    </span>
                                </TableCell>
                                <TableCell class="whitespace-nowrap text-sm">
                                    <div class="flex items-center gap-1">
                                        <MapPin class="h-3.5 w-3.5 text-muted-foreground" />
                                        <span>{{ member.chapter }}</span>
                                        <span v-if="member.checkpoint" class="text-xs text-muted-foreground">
                                            ({{ member.checkpoint }})
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline" :class="['whitespace-nowrap text-xs font-semibold', statusBadgeClass(member.status_keanggotaan)]">
                                        {{ member.status_keanggotaan }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground text-xs whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-3.5 w-3.5" />
                                        <span>{{ formatDate(member.created_at) }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Link :href="`/dashboard/member`">
                                        <Button variant="ghost" size="sm" class="h-8 text-xs text-blue-500 hover:text-blue-600">
                                            Lihat di List
                                        </Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
            
        </div>
    </AppLayout>
</template>
