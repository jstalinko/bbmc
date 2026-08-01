<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Input } from '@/components/ui/input';
import { Settings2, Save, Calendar, Check, Info } from 'lucide-vue-next';
import { TransitionRoot } from '@headlessui/vue';
import { decryptPayload } from '@/lib/crypto';

const props = defineProps<{
    settings: {
        ajukan_diri: boolean;
        ajukan_anggota: boolean;
        tanggal_mulai: string | null;
        tanggal_selesai: string | null;
        max_active_users: number;
        piwapi: string | Array<{secret_key: string, account_id: string}>;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Setting Pemilihan',
        href: '/dashboard/setting-pemilihan',
    },
];

// Initialize form using passed settings
const decryptedPiwapi = typeof props.settings.piwapi === 'string' 
    ? decryptPayload(props.settings.piwapi) 
    : props.settings.piwapi;

const form = useForm({
    ajukan_diri: !!props.settings.ajukan_diri,
    ajukan_anggota: !!props.settings.ajukan_anggota,
    tanggal_mulai: props.settings.tanggal_mulai ? props.settings.tanggal_mulai.substring(0, 16) : '',
    tanggal_selesai: props.settings.tanggal_selesai ? props.settings.tanggal_selesai.substring(0, 16) : '',
    max_active_users: props.settings.max_active_users ?? 0,
    piwapi: decryptedPiwapi && Array.isArray(decryptedPiwapi) && decryptedPiwapi.length > 0 
        ? decryptedPiwapi 
        : [{secret_key: '', account_id: ''}],
});

const submitSettings = () => {
    form.post(route('election.setting_post'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Setting Pemilihan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8 space-y-6">
            <!-- Header Section -->
            <div class="flex items-center gap-3 pb-5 border-b">
                <div class="p-3 bg-amber-500/10 text-amber-600 rounded-xl">
                    <Settings2 class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground uppercase">Setting Pemilihan El Presidente</h1>
                    <p class="text-sm text-muted-foreground mt-0.5">Konfigurasi alur pengajuan calon, pendaftaran, dan rentang waktu pemilihan.</p>
                </div>
            </div>

            <!-- Main Form Card -->
            <div class="bg-card text-card-foreground border rounded-xl shadow-sm p-6 space-y-6">
                <form @submit.prevent="submitSettings" class="space-y-8">
                    
                    <!-- Section A: Fitur Toggles -->
                    <div class="space-y-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-muted-foreground border-b pb-2">Status Pengajuan Bakal Calon</h2>
                        
                        <div class="grid gap-6 sm:grid-cols-2 mt-4">
                            <!-- Toggle 1: Ajukan Diri -->
                            <div class="flex items-start justify-between p-4 rounded-lg border bg-muted/30">
                                <div class="space-y-0.5 pr-4">
                                    <Label class="text-base font-semibold cursor-pointer" for="ajukan_diri">
                                        Ajukan Diri Sebagai El Presidente
                                    </Label>
                                    <p class="text-xs text-muted-foreground leading-relaxed">
                                        Aktifkan agar anggota dapat mendaftarkan diri secara mandiri sebagai bakal calon di portal Pra-Election.
                                    </p>
                                </div>
                                <Switch
                                    id="ajukan_diri"
                                    v-model="form.ajukan_diri"
                                />
                            </div>

                            <!-- Toggle 2: Ajukan Anggota -->
                            <div class="flex items-start justify-between p-4 rounded-lg border bg-muted/30">
                                <div class="space-y-0.5 pr-4">
                                    <Label class="text-base font-semibold cursor-pointer" for="ajukan_anggota">
                                        Ajukan Anggota Sebagai El Presidente
                                    </Label>
                                    <p class="text-xs text-muted-foreground leading-relaxed">
                                        Aktifkan agar anggota dapat merekomendasikan saudara satu aspal lainnya sebagai bakal calon el presidente.
                                    </p>
                                </div>
                                <Switch
                                    id="ajukan_anggota"
                                    v-model="form.ajukan_anggota"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Section B: Rentang Waktu Pemilihan -->
                    <div class="space-y-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-muted-foreground border-b pb-2 flex items-center gap-2">
                            <Calendar class="h-4.5 w-4.5" />
                            <span>Rentang Waktu Jalannya Pemilihan</span>
                        </h2>

                        <div class="grid gap-6 sm:grid-cols-2 mt-4">
                            <!-- Tanggal Mulai -->
                            <div class="grid gap-2">
                                <Label for="tanggal_mulai" class="font-medium text-sm">Tanggal Mulai Pemilihan</Label>
                                <Input
                                    id="tanggal_mulai"
                                    type="datetime-local"
                                    v-model="form.tanggal_mulai"
                                    class="w-full"
                                />
                                <p class="text-[11px] text-muted-foreground">Portal login dan halaman live polling akan diblokir sebelum waktu ini.</p>
                            </div>

                            <!-- Tanggal Selesai -->
                            <div class="grid gap-2">
                                <Label for="tanggal_selesai" class="font-medium text-sm">Tanggal Selesai Pemilihan</Label>
                                <Input
                                    id="tanggal_selesai"
                                    type="datetime-local"
                                    v-model="form.tanggal_selesai"
                                    class="w-full"
                                />
                                <p class="text-[11px] text-muted-foreground">Portal login akan otomatis ditutup setelah waktu ini berlalu.</p>
                            </div>
                        </div>

                        <!-- Max Active Users (Queue System) -->
                        <div class="grid gap-6 sm:grid-cols-2 mt-4">
                            <div class="grid gap-2">
                                <Label for="max_active_users" class="font-medium text-sm">Maksimal User Aktif (Antrean Login)</Label>
                                <Input
                                    id="max_active_users"
                                    type="number"
                                    min="0"
                                    v-model="form.max_active_users"
                                    class="w-full"
                                />
                                <p class="text-[11px] text-muted-foreground">Isi dengan `0` untuk menonaktifkan fitur antrean login.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section C: Kredensial WhatsApp (PIWAPI) -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b pb-2">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-muted-foreground">
                                Kredensial WhatsApp (PIWAPI)
                            </h2>
                            <Button type="button" variant="outline" size="sm" @click="form.piwapi.push({secret_key: '', account_id: ''})" class="h-8 text-xs font-semibold gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                Tambah Akun
                            </Button>
                        </div>

                        <div v-for="(item, index) in form.piwapi" :key="index" class="grid gap-6 sm:grid-cols-12 mt-4 items-start p-4 pt-8 rounded-lg border bg-muted/30 relative">
                            <!-- Delete Button -->
                            <button type="button" v-if="form.piwapi.length > 1" @click="form.piwapi.splice(index, 1)" class="absolute top-2 right-2 text-destructive hover:bg-destructive/10 p-1.5 rounded-md transition-colors" title="Hapus Kredensial">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                            </button>

                            <!-- API Secret Key -->
                            <div class="grid gap-2 sm:col-span-6">
                                <Label :for="'piwapi_secret_key_' + index" class="font-medium text-sm">PIWAPI API Secret Key</Label>
                                <Input
                                    :id="'piwapi_secret_key_' + index"
                                    type="text"
                                    v-model="item.secret_key"
                                    placeholder="Masukkan API Secret Key"
                                    class="w-full"
                                />
                                <p class="text-[11px] text-muted-foreground">Kunci rahasia API untuk mengirimkan pesan WhatsApp via PIWAPI.</p>
                            </div>

                            <!-- Account ID -->
                            <div class="grid gap-2 sm:col-span-6">
                                <Label :for="'piwapi_account_id_' + index" class="font-medium text-sm">PIWAPI Account ID</Label>
                                <Input
                                    :id="'piwapi_account_id_' + index"
                                    type="text"
                                    v-model="item.account_id"
                                    placeholder="Masukkan Account ID"
                                    class="w-full"
                                />
                                <p class="text-[11px] text-muted-foreground">ID akun WhatsApp instance di PIWAPI.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Save Action Segment -->
                    <div class="flex items-center gap-4 pt-4 border-t">
                        <Button type="submit" class="font-semibold" :disabled="form.processing">
                            <Save class="h-4 w-4 mr-2" />
                            Simpan Pengaturan
                        </Button>

                        <!-- Success Indication Banner -->
                        <TransitionRoot
                            :show="form.recentlySuccessful"
                            enter="transition ease-in-out duration-300"
                            enter-from="opacity-0 translate-x-2"
                            enter-to="opacity-100 translate-x-0"
                            leave="transition ease-in duration-200"
                            leave-to="opacity-0"
                        >
                            <p class="inline-flex items-center gap-1.5 text-sm font-bold text-green-600 bg-green-50 border border-green-200 px-3 py-1.5 rounded-lg">
                                <Check class="h-4 w-4" />
                                <span>Pengaturan berhasil disimpan!</span>
                            </p>
                        </TransitionRoot>
                    </div>

                </form>
            </div>

            <!-- Information Panel -->
            <div class="bg-amber-50/50 dark:bg-amber-950/10 border border-amber-200/50 dark:border-amber-900/30 rounded-xl p-4 flex gap-3">
                <Info class="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                <div class="text-xs text-amber-800 dark:text-amber-400 space-y-1">
                    <p class="font-bold uppercase tracking-wider">Perhatian Alur Sistem:</p>
                    <p class="leading-relaxed">
                        Jika kedua status pengisian bakal calon dimatikan, halaman pengisian Pra-Election portal akan memblokir semua input form dan menampilkan pesan bahwa pendaftaran ditutup.
                    </p>
                    <p class="leading-relaxed mt-1">
                        Sistem mencatat pengaturan ini secara waktu nyata dan memvalidasinya langsung ke file konfigurasi pemilihan.
                    </p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
