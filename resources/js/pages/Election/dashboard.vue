<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
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
    hasVoted: boolean;
    votedCalonId: number | null;
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

const isVoting = ref(false);

const handleVote = (candidate: any) => {
    if (confirm(`Apakah Anda yakin ingin memberikan suara Anda kepada ${candidate.member?.nama_lengkap || 'Calon'}? Tindakan ini tidak dapat dibatalkan.`)) {
        isVoting.value = true;
        router.post(route('election.vote'), {
            calon_id: candidate.id
        }, {
            onFinish: () => {
                isVoting.value = false;
            }
        });
    }
};

const votedCandidate = computed(() => {
    if (!props.hasVoted || !props.votedCalonId) return null;
    return props.candidates.find(c => c.id === props.votedCalonId);
});

const getCandidatePhoto = (c: any) => {
    if (!c) return null;
    if (c.foto_calon) return `/storage/${c.foto_calon}`;
    if (c.member?.foto) return `/storage/${c.member.foto}`;
    return null;
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
        <header class="relative z-10 w-full border-b border-red-100 bg-white/60 backdrop-blur-md py-4 sm:py-6">
            <div class="mx-auto flex w-full max-w-full px-4 sm:px-8 lg:px-12 items-center justify-between">
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
        <main class="relative z-10 mx-auto flex w-full max-w-full flex-col px-4 py-10 sm:px-8 lg:px-12">
            
            <!-- Hero Title Segment -->
            <div class="mb-10 text-center">
                <div class="mb-3 inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-white px-3 py-1 text-xs uppercase tracking-[0.2em] text-red-600 shadow-sm">
                    <Award class="h-3.5 w-3.5 animate-bounce" />
                    <span>Election Dashboard</span>
                </div>
                <h1 class="font-bebas text-4xl sm:text-6xl tracking-wide text-transparent bg-clip-text bg-gradient-to-r from-zinc-950 via-red-600 to-red-800">
                    CALON EL PRESIDENTE
                </h1>
                <div class="mt-4 h-[2px] w-24 mx-auto bg-gradient-to-r from-transparent via-red-500 to-transparent"></div>
            </div>

            <!-- SUCCESS DIALOG ALERT MODAL -->
            <div v-if="hasVoted" class="mb-8 rounded-2xl border border-green-200 bg-green-50 p-6 shadow-lg shadow-green-100/50 flex gap-4 items-start transition-all duration-300">
                <div class="rounded-xl bg-green-500/10 border border-green-500/20 p-2.5 text-green-600">
                    <CheckCircle2 class="h-6 w-6" />
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-green-800 uppercase tracking-wide">Pemberian Suara Berhasil!</h3>
                    <p class="text-sm text-green-700 mt-1">
                        Terima kasih atas partisipasi Anda. Anda telah memberikan suara kepada <strong>{{ votedCandidate?.member?.nama_lengkap || 'Calon Pilihan Anda' }}</strong>. Pilihan Anda sangat menentukan masa depan Bikers Brotherhood MC Indonesia.
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

                <!-- Inline Horizontal of Candidates (Fit all on mobile without scroll) -->
                <div v-else class="flex flex-row flex-nowrap items-stretch justify-center gap-2 sm:gap-8 w-full pb-6 px-1">
                    <div 
                        v-for="candidate in candidates" 
                        :key="candidate.id" 
                        class="group flex flex-col items-center justify-between rounded-xl sm:rounded-2xl border border-red-100/60 bg-white p-2.5 sm:p-6 shadow-xl shadow-red-100/20 hover:border-red-500 hover:shadow-red-200/30 transition-all duration-300 relative overflow-hidden min-w-0 flex-1 sm:min-w-[340px] max-w-2xl"
                        :class="{ 'border-green-500 bg-green-50/20 shadow-green-100/30': hasVoted && votedCalonId === candidate.id }"
                    >
                        <!-- Candidate Card Info -->
                        <div class="flex flex-col items-center text-center w-full py-1 sm:py-4">
                            <!-- Nomor Urut Badge -->
                            <div v-if="candidate.no_urut" class="mb-2 sm:mb-4 inline-flex items-center gap-1 rounded-full bg-amber-500 text-white px-2 sm:px-3.5 py-0.5 sm:py-1 font-mono text-[8px] sm:text-xs font-bold shadow-md uppercase tracking-wider">
                                <span>No. Urut #{{ candidate.no_urut }}</span>
                            </div>

                            <!-- Large Circle Photo -->
                            <div class="h-16 w-16 sm:h-32 sm:w-32 shrink-0 rounded-full border-2 sm:border-4 border-white shadow-md sm:shadow-xl overflow-hidden bg-zinc-100 flex items-center justify-center relative transition-transform duration-300 group-hover:scale-105"
                                :class="hasVoted && votedCalonId === candidate.id ? 'ring-2 sm:ring-4 ring-green-500/30' : 'ring-2 sm:ring-4 ring-red-500/10 group-hover:ring-red-500/30'">
                                <img 
                                    v-if="getCandidatePhoto(candidate)" 
                                    :src="getCandidatePhoto(candidate)" 
                                    class="h-full w-full object-cover" 
                                    alt="Foto Calon" 
                                />
                                <div v-else class="h-full w-full flex items-center justify-center text-xl sm:text-4xl font-black text-red-600 bg-red-50">
                                    {{ candidate.member?.nama_lengkap?.charAt(0) || 'C' }}
                                </div>
                                <!-- Active Selection Overlay -->
                                <div v-if="hasVoted && votedCalonId === candidate.id" class="absolute inset-0 bg-green-950/40 flex items-center justify-center text-white backdrop-blur-[2px]">
                                    <CheckCircle2 class="h-6 w-6 sm:h-10 sm:w-10 text-green-400 stroke-[2.5]" />
                                </div>
                            </div>

                            <!-- Names & Info -->
                            <div class="mt-2 sm:mt-5">
                                <h4 class="font-oswald text-xs sm:text-xl font-bold text-zinc-950 uppercase leading-tight tracking-wide line-clamp-2 break-words">
                                    {{ candidate.member?.nama_lengkap }}
                                </h4>
                                <p class="text-[9px] sm:text-xs text-red-600 font-semibold mt-0.5 sm:mt-1 uppercase tracking-wider line-clamp-1 break-words">
                                    "{{ candidate.member?.nama_panggilan || '—' }}"
                                </p>
                                <div class="mt-1.5 sm:mt-3 flex flex-wrap items-center justify-center gap-1 sm:gap-2 text-[8px] sm:text-[10px] font-mono text-zinc-500">
                                    <span class="bg-zinc-100 px-1 sm:px-2 py-0.5 rounded border">KTA: {{ candidate.no_kartu }}</span>
                                    <span class="bg-zinc-100 px-1 sm:px-2 py-0.5 rounded border">{{ candidate.chapter }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Vote Action Button -->
                        <div class="w-full mt-2 sm:mt-4 border-t border-zinc-100/80 pt-2 sm:pt-4">
                            <button 
                                @click="handleVote(candidate)" 
                                :disabled="hasVoted || isVoting"
                                class="w-full flex items-center justify-center gap-1 sm:gap-2 rounded-lg sm:rounded-xl py-2 sm:py-3 text-[9px] sm:text-xs font-bold uppercase tracking-wider shadow-md transition-all duration-200"
                                :class="[
                                    hasVoted 
                                        ? (votedCalonId === candidate.id 
                                            ? 'bg-green-600 text-white shadow-green-100 hover:bg-green-600 cursor-default' 
                                            : 'bg-zinc-100 text-zinc-400 border shadow-none cursor-not-allowed')
                                        : 'bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white shadow-red-200'
                                ]"
                            >
                                <Heart class="h-3 w-3 sm:h-4 sm:w-4 shrink-0" :class="{ 'animate-pulse text-red-300': isVoting && !hasVoted, 'fill-white': hasVoted && votedCalonId === candidate.id }" />
                                <span class="line-clamp-1 break-words">
                                    {{ isVoting 
                                        ? 'Memproses...' 
                                        : (hasVoted 
                                            ? (votedCalonId === candidate.id ? 'PILIHAN ANDA' : 'PILIHAN LAIN') 
                                            : 'PILIH EL PRESIDENTE') 
                                    }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer Segment -->
        <footer class="relative z-10 w-full border-t border-red-100 bg-white/40 py-8 backdrop-blur-md">
            <div class="mx-auto w-full max-w-full px-4 sm:px-8 lg:px-12">
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
