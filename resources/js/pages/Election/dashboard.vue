<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { 
    ShieldCheck, 
    UserCheck, 
    Users, 
    Calendar, 
    Info, 
    Award, 
    LogOut,
    Heart,
    CheckCircle2,
    BookOpen
} from 'lucide-vue-next';

const props = defineProps<{
    candidates: any[];
    errors?: Record<string, string>;
    flash?: {
        success?: boolean;
        message?: string;
    };
}>();

const page = usePage();
const electionMember = ref<any>(page.props.auth.election_member);

const handleLogout = () => {
    const logoutForm = useForm({});
    logoutForm.post(route('election.logout'));
};

// Simple voting simulation/logic (placeholder or ready for integration)
const votingSuccess = ref(false);
const votedForName = ref('');
const isVoting = ref(false);

const handleVote = (candidate: any) => {
    if (confirm(`Apakah Anda yakin ingin memberikan suara Anda kepada ${candidate.member?.nama_lengkap || 'Calon'}? Tindakan ini tidak dapat dibatalkan.`)) {
        isVoting.value = true;
        // In the future, this can submit to a real POST route: route('election.vote')
        setTimeout(() => {
            votedForName.value = candidate.member?.nama_lengkap;
            votingSuccess.value = true;
            isVoting.value = false;
        }, 1200);
    }
};

</script>

<template>
    <Head title="Election Dashboard - El Presidente BBMC" />

    <!-- Main Container: Light Red/White Theme matching election portal -->
    <div 
        class="relative min-h-screen w-full flex flex-col justify-between overflow-x-hidden text-zinc-800 font-sans selection:bg-red-600 selection:text-white"
        style="background: linear-gradient(135deg, #fff5f5 0%, #fff9f7 40%, #fef2f2 100%);"
    >
        
        <!-- Ambient red gradients and mesh grids -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-[20%] -top-[30%] h-[80%] w-[80%] rounded-full bg-red-100/40 blur-[130px]"></div>
            <div class="absolute -right-[20%] top-[20%] h-[70%] w-[70%] rounded-full bg-red-50/40 blur-[130px]"></div>
            <div class="absolute inset-0 bg-[linear-gradient(rgba(220,38,38,0.012)_1px,transparent_1px),linear-gradient(90deg,rgba(220,38,38,0.012)_1px,transparent_1px)] bg-[size:32px_32px]"></div>
        </div>

        <!-- Navigation Bar -->
        <header class="relative z-10 w-full border-b border-red-100 bg-white/60 backdrop-blur-md px-4 py-3 sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <Link :href="route('election.portal')" class="flex items-center gap-2 group text-zinc-600 hover:text-red-600 transition-colors">
                    <span class="text-xs font-bold uppercase tracking-wider">Pencalonan Portal</span>
                </Link>
                
                <div class="flex items-center gap-3">
                    <img src="/bbmc-logo.png" class="h-8 w-auto filter drop-shadow" alt="Logo" />
                    <span class="font-bebas text-lg tracking-wider text-red-600">BBMC ELECTION 2026</span>
                </div>

                <div class="flex items-center gap-4">
                    <div v-if="electionMember" class="flex items-center gap-3">
                        <div class="hidden md:flex flex-col items-end text-right">
                            <span class="text-xs font-bold text-zinc-800 uppercase leading-none">{{ electionMember.nama_lengkap }}</span>
                            <span class="text-[10px] text-zinc-500 font-semibold mt-1">KTA: {{ electionMember.no_kartu }} | {{ electionMember.chapter }}</span>
                        </div>
                        <div class="h-8 w-8 rounded-full border border-red-200 overflow-hidden bg-zinc-100 flex items-center justify-center shrink-0">
                            <img v-if="electionMember.foto" :src="'/storage/' + electionMember.foto" class="h-full w-full object-cover" />
                            <UserCheck v-else class="h-4 w-4 text-red-500" />
                        </div>
                        <button 
                            @click="handleLogout" 
                            title="Log Keluar"
                            class="p-2 rounded-xl hover:bg-red-50 text-zinc-500 hover:text-red-600 transition-all"
                        >
                            <LogOut class="h-4.5 w-4.5" />
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="relative z-10 mx-auto flex w-full max-w-5xl flex-col px-4 py-10 sm:px-6 lg:px-8">
            
            <!-- Hero Title Segment -->
            <div class="mb-10 text-center">
                <div class="mb-3 inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-white px-3 py-1 text-xs uppercase tracking-[0.2em] text-red-600 shadow-sm">
                    <Award class="h-3.5 w-3.5 animate-bounce" />
                    <span>Election Dashboard</span>
                </div>
                <h1 class="font-bebas text-4xl sm:text-6xl tracking-wide text-transparent bg-clip-text bg-gradient-to-r from-zinc-950 via-red-600 to-red-800">
                    CALON EL PRESIDENTE TERPILIH
                </h1>
                <p class="mx-auto mt-2 max-w-lg text-xs tracking-[0.15em] text-zinc-600 font-semibold uppercase">
                    Salurkan Suara Anda Untuk Masa Bakti 2026 — 2030
                </p>
                <div class="mt-3 h-[2px] w-24 mx-auto bg-gradient-to-r from-transparent via-red-500 to-transparent"></div>
            </div>

            <!-- SUCCESS DIALOG ALERT MODAL -->
            <div v-if="votingSuccess" class="mb-8 rounded-2xl border border-green-200 bg-green-50 p-6 shadow-lg shadow-green-100/50 flex gap-4 items-start transition-all duration-300">
                <div class="rounded-xl bg-green-500/10 border border-green-500/20 p-2.5 text-green-600">
                    <CheckCircle2 class="h-6 w-6" />
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-green-800 uppercase tracking-wide">Pemberian Suara Berhasil!</h3>
                    <p class="text-sm text-green-700 mt-1">
                        Terima kasih atas partisipasi Anda. Anda telah memberikan suara kepada <strong>{{ votedForName }}</strong>. Pilihan Anda sangat menentukan masa depan Bikers Brotherhood MC Indonesia.
                    </p>
                </div>
            </div>

            <!-- Candidate Lists Section -->
            <div class="space-y-6">
                <div class="flex items-center justify-between border-b border-red-100 pb-3">
                    <h3 class="font-oswald text-lg font-bold text-zinc-950 uppercase tracking-wide flex items-center gap-2">
                        <Users class="h-5 w-5 text-red-600" />
                        <span>Daftar Calon El Presidente Resmi</span>
                    </h3>
                    <span class="rounded bg-red-600/10 px-2.5 py-0.5 font-mono text-[10px] font-bold text-red-600 border border-red-500/20 uppercase">
                        {{ candidates.length }} Calon Ditetapkan
                    </span>
                </div>

                <!-- Empty State -->
                <div v-if="candidates.length === 0" class="text-center py-16 bg-white rounded-2xl border border-zinc-100 shadow-xl shadow-red-100/20">
                    <div class="mx-auto h-12 w-12 text-zinc-400 mb-3 flex items-center justify-center">
                        <Info class="h-8 w-8" />
                    </div>
                    <h4 class="font-oswald text-base font-bold text-zinc-800 uppercase">Belum ada Calon Ditetapkan</h4>
                    <p class="text-xs text-zinc-500 mt-1 max-w-sm mx-auto leading-relaxed">
                        Saat ini Dewan Adat belum mempublikasikan calon resmi El Presidente yang ditetapkan. Silakan kembali lagi nanti setelah verifikasi berkas selesai.
                    </p>
                </div>

                <!-- Grid of Candidates -->
                <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div 
                        v-for="candidate in candidates" 
                        :key="candidate.id" 
                        class="group flex flex-col justify-between rounded-2xl border border-red-100/60 bg-white p-6 shadow-xl shadow-red-100/20 hover:border-red-400 transition-all duration-300 relative overflow-hidden"
                    >
                        <!-- Candidate Header Detail -->
                        <div>
                            <div class="flex items-start gap-4">
                                <div class="h-20 w-20 shrink-0 rounded-full border-2 border-red-200 overflow-hidden bg-zinc-100 shadow-inner flex items-center justify-center">
                                    <img 
                                        v-if="candidate.foto_calon" 
                                        :src="'/storage/' + candidate.foto_calon" 
                                        class="h-full w-full object-cover" 
                                        alt="Foto Calon" 
                                    />
                                    <img 
                                        v-else-if="candidate.member?.foto" 
                                        :src="'/storage/' + candidate.member.foto" 
                                        class="h-full w-full object-cover" 
                                        alt="Foto Calon" 
                                    />
                                    <UserCheck v-else class="h-10 w-10 text-red-400" />
                                </div>
                                <div>
                                    <span class="text-[9px] font-bold uppercase tracking-widest text-red-600 bg-red-50 border border-red-200/50 px-2 py-0.5 rounded">
                                        Calon Resmi
                                    </span>
                                    <h4 class="font-oswald text-lg font-bold text-zinc-950 uppercase mt-1 leading-tight">
                                        {{ candidate.member?.nama_lengkap }}
                                    </h4>
                                    <p class="text-xs text-zinc-500 font-semibold mt-0.5">
                                        KTA: {{ candidate.no_kartu }} | Chapter: {{ candidate.chapter }}
                                    </p>
                                </div>
                            </div>

                            <!-- Visi Misi Section -->
                            <div class="mt-6 space-y-4">
                                <div class="bg-zinc-50 rounded-xl p-3 border border-zinc-100">
                                    <span class="text-[10px] font-bold uppercase text-zinc-400 tracking-wider">Visi</span>
                                    <p class="text-xs text-zinc-700 font-medium leading-relaxed mt-1">
                                        {{ candidate.visi || '-' }}
                                    </p>
                                </div>
                                <div class="bg-zinc-50 rounded-xl p-3 border border-zinc-100">
                                    <span class="text-[10px] font-bold uppercase text-zinc-400 tracking-wider">Misi</span>
                                    <p class="text-xs text-zinc-700 font-medium leading-relaxed mt-1 whitespace-pre-line">
                                        {{ candidate.misi || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Vote Action Button -->
                        <div class="mt-6 border-t border-zinc-50 pt-4">
                            <button 
                                @click="handleVote(candidate)" 
                                :disabled="votingSuccess || isVoting"
                                class="w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white py-3 text-xs font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all disabled:opacity-40"
                            >
                                <Heart class="h-4 w-4" :class="{ 'animate-pulse text-red-300': isVoting }" />
                                <span>{{ isVoting ? 'Memproses...' : (votingSuccess ? 'Pilihan Disimpan' : 'PILIH EL PRESIDENTE') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Notice -->
            <div class="rounded-2xl border border-red-100 bg-white p-6 shadow-xl shadow-red-100/40 mt-8">
                <div class="flex items-start gap-2.5 text-xs text-zinc-500">
                    <Info class="h-4.5 w-4.5 text-red-500 mt-0.5 shrink-0" />
                    <p class="leading-relaxed">
                        Proses pemungutan suara ini diawasi ketat dan dijalankan secara mandiri, langsung, umum, bebas, rahasia, jujur, dan adil oleh **Panitia Pemilihan Dewan Adat BBMC Indonesia**. Suara Anda sah hanya jika diverifikasi menggunakan nomor kartu terdaftar.
                    </p>
                </div>
            </div>

        </main>

        <!-- Footer Segment -->
        <footer class="relative z-10 w-full border-t border-red-100 bg-white/40 py-8 backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center text-center">
                    <span class="font-bebas text-xs sm:text-sm tracking-[0.25em] text-zinc-400 select-none">Bikers Brotherhood MC Indonesia</span>
                    <span class="font-bebas text-xl sm:text-2xl tracking-[0.15em] text-red-600 font-bold mt-2">
                        BROTHERHOOD, LOYAL, RESPECT, HONOR, PRIDE
                    </span>
                </div>
                <div class="my-6 h-[1px] w-full bg-red-100"></div>
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row text-center sm:text-left">
                    <p class="text-xs text-zinc-500">
                        &copy; {{ new Date().getFullYear() }} <span class="text-zinc-600 font-semibold">Bikers Brotherhood MC Indonesia</span>. All Rights Reserved.
                    </p>
                    <div class="flex items-center gap-2 text-[10px] sm:text-xs text-zinc-500">
                        <span>Managed and Developed by</span>
                        <span class="rounded bg-red-50 px-2 py-0.5 font-mono text-red-600 border border-red-200 shadow-sm">BBMC IT Division</span>
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>

<style scoped>
.font-bebas {
    font-family: 'Bebas Neue', sans-serif;
}
.font-oswald {
    font-family: 'Oswald', sans-serif;
}
.font-sans {
    font-family: 'Outfit', 'Roboto', sans-serif;
}
</style>
