<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    ShieldCheck, 
    UserCheck, 
    Users, 
    Calendar, 
    Info, 
    CheckCircle2, 
    ChevronRight, 
    Award, 
    ArrowLeft, 
    Sparkles, 
    Send, 
    Search,
    BookOpen,
    HelpCircle
} from 'lucide-vue-next';

// State management for interactive forms
const showNominationOptions = ref(false);
const activeForm = ref<'self' | 'member' | null>(null);
const submissionSuccess = ref(false);
const submittedName = ref('');

// Form data reactive models
const selfForm = ref({
    nama: '',
    no_kartu: '',
    chapter: '',
    visi_misi: '',
    program_kerja: ''
});

const memberForm = ref({
    nominator_no_kartu: '',
    candidate_no_kartu: '',
    candidate_nama: '',
    candidate_chapter: '',
    alasan: ''
});

// Mock Candidate List
const registeredCandidates = ref([
    {
        nama: 'Brother Gunawan "Guns" Wibisono',
        no_kartu: '0023',
        chapter: 'Mother Chapter',
        status: 'Terverifikasi',
        status_color: 'bg-green-100 text-green-700 border-green-200',
        visi: 'Membangun persaudaraan tanpa batas dengan memperkuat struktur keanggotaan dan chapter di seluruh Indonesia.'
    },
    {
        nama: 'Brother Deddy "Black" Rahardjo',
        no_kartu: '0108',
        chapter: 'Jakarta Chapter',
        status: 'Verifikasi Berkas',
        status_color: 'bg-amber-100 text-amber-700 border-amber-200',
        visi: 'Menjadikan BBMC Indonesia sebagai pelopor keselamatan berkendara dan teladan dalam kegiatan sosial kemasyarakatan.'
    }
]);

// Handle Forms Submission
const handleSelfSubmit = () => {
    if (!selfForm.value.nama || !selfForm.value.no_kartu || !selfForm.value.visi_misi) {
        alert('Harap isi nama, no kartu, dan visi misi Anda.');
        return;
    }
    submittedName.value = selfForm.value.nama;
    submissionSuccess.value = true;
    
    // Append to list dynamically for interactive simulation
    registeredCandidates.value.push({
        nama: selfForm.value.nama,
        no_kartu: selfForm.value.no_kartu.padStart(4, '0'),
        chapter: selfForm.value.chapter || 'Mother Chapter',
        status: 'Pending Verifikasi',
        status_color: 'bg-blue-100 text-blue-700 border-blue-200',
        visi: selfForm.value.visi_misi
    });

    // Reset Form
    selfForm.value = { nama: '', no_kartu: '', chapter: '', visi_misi: '', program_kerja: '' };
};

const handleMemberSubmit = () => {
    if (!memberForm.value.candidate_nama || !memberForm.value.candidate_no_kartu || !memberForm.value.alasan) {
        alert('Harap isi nama anggota yang dicalonkan, nomor kartu, dan alasan pencalonan.');
        return;
    }
    submittedName.value = memberForm.value.candidate_nama;
    submissionSuccess.value = true;

    // Append to list dynamically for interactive simulation
    registeredCandidates.value.push({
        nama: memberForm.value.candidate_nama,
        no_kartu: memberForm.value.candidate_no_kartu.padStart(4, '0'),
        chapter: memberForm.value.candidate_chapter || 'Mother Chapter',
        status: 'Rekomendasi Anggota',
        status_color: 'bg-purple-100 text-purple-700 border-purple-200',
        visi: `Direkomendasikan oleh Anggota: "${memberForm.value.alasan}"`
    });

    // Reset Form
    memberForm.value = { nominator_no_kartu: '', candidate_no_kartu: '', candidate_nama: '', candidate_chapter: '', alasan: '' };
};

const closeAlert = () => {
    submissionSuccess.value = false;
    activeForm.value = null;
};
</script>

<template>
    <Head title="El Presidente Pra-Election Portal" />

    <!-- Main Container: Light Red/White Theme matching welcome page -->
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
                <Link :href="route('home')" class="flex items-center gap-2 group text-zinc-600 hover:text-red-600 transition-colors">
                    <ArrowLeft class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                    <span class="text-xs font-bold uppercase tracking-wider">Kembali ke Portal</span>
                </Link>
                
                <div class="flex items-center gap-3">
                    <img src="/bbmc-logo.png" class="h-8 w-auto filter drop-shadow" alt="Logo" />
                    <span class="font-bebas text-lg tracking-wider text-red-600">BBMC ELECTION 2026</span>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="relative z-10 mx-auto flex w-full max-w-5xl flex-col px-4 py-10 sm:px-6 lg:px-8">
            
            <!-- Hero Title Segment -->
            <div class="mb-10 text-center">
                <div class="mb-3 inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-white px-3 py-1 text-xs uppercase tracking-[0.2em] text-red-600 shadow-sm">
                    <Award class="h-3.5 w-3.5 animate-bounce" />
                    <span>Pra-Election Portal</span>
                </div>
                <h1 class="font-bebas text-4xl sm:text-6xl tracking-wide text-transparent bg-clip-text bg-gradient-to-r from-zinc-950 via-red-600 to-red-800">
                    EL PRESIDENTE BBMC INDONESIA
                </h1>
                <p class="mx-auto mt-2 max-w-lg text-xs tracking-[0.15em] text-zinc-600 font-semibold uppercase">
                    Masa Bakti Bhakti 2026 — 2030
                </p>
                <div class="mt-3 h-[2px] w-24 mx-auto bg-gradient-to-r from-transparent via-red-500 to-transparent"></div>
            </div>

            <!-- SUCCESS DIALOG ALERT MODAL (Interactive validation confirmation) -->
            <div v-if="submissionSuccess" class="mb-8 rounded-2xl border border-green-200 bg-green-50 p-6 shadow-lg shadow-green-100/50 flex gap-4 items-start transition-all duration-300">
                <div class="rounded-xl bg-green-500/10 border border-green-500/20 p-2.5 text-green-600">
                    <CheckCircle2 class="h-6 w-6" />
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-green-800 uppercase tracking-wide">Pencalonan Berhasil Dikirim!</h3>
                    <p class="text-sm text-green-700 mt-1">
                        Formulir pencalonan untuk <strong class="underline font-semibold">{{ submittedName }}</strong> telah terkirim ke Dewan Adat / Panitia Pemilihan. Terima kasih atas partisipasi aktif Anda menjaga pilar demokrasi klub.
                    </p>
                    <button 
                        @click="closeAlert" 
                        class="mt-3 inline-flex items-center gap-1 px-4 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase tracking-wider transition-all"
                    >
                        Tutup & Lihat Calon
                    </button>
                </div>
            </div>

            <!-- Timeline Tracker Section -->
            <div class="bg-white rounded-2xl border border-red-100 p-6 shadow-xl shadow-red-100/40 mb-8">
                <h2 class="font-oswald text-lg font-bold text-zinc-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <Calendar class="h-5 w-5 text-red-600" />
                    <span>Garis Waktu Pemilihan Presiden</span>
                </h2>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 relative">
                    <!-- Step 1 (Active) -->
                    <div class="relative rounded-xl border border-red-200 bg-red-50/50 p-4 shadow-sm">
                        <span class="absolute top-3 right-3 rounded-full bg-red-600 px-2 py-0.5 text-[9px] font-bold text-white uppercase animate-pulse">Aktif</span>
                        <span class="text-xs font-bold text-red-600">TAHAP 1</span>
                        <h4 class="font-oswald text-sm font-bold text-zinc-950 uppercase mt-0.5">Pencalonan</h4>
                        <p class="text-[11px] text-zinc-500 mt-1">18 Mei — 31 Mei 2026. Pengajuan bakal calon presiden.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="rounded-xl border border-zinc-100 bg-white p-4 shadow-sm">
                        <span class="text-xs font-bold text-zinc-400">TAHAP 2</span>
                        <h4 class="font-oswald text-sm font-semibold text-zinc-800 uppercase mt-0.5">Verifikasi</h4>
                        <p class="text-[11px] text-zinc-400 mt-1">1 Juni — 7 Juni 2026. Validasi berkas kelayakan Dewan Adat.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="rounded-xl border border-zinc-100 bg-white p-4 shadow-sm">
                        <span class="text-xs font-bold text-zinc-400">TAHAP 3</span>
                        <h4 class="font-oswald text-sm font-semibold text-zinc-800 uppercase mt-0.5">Kampanye & Debat</h4>
                        <p class="text-[11px] text-zinc-400 mt-1">8 Juni — 20 Juni 2026. Penyampaian visi misi & perdebatan terbuka.</p>
                    </div>

                    <!-- Step 4 -->
                    <div class="rounded-xl border border-zinc-100 bg-white p-4 shadow-sm">
                        <span class="text-xs font-bold text-zinc-400">TAHAP 4</span>
                        <h4 class="font-oswald text-sm font-semibold text-zinc-800 uppercase mt-0.5">Pemilihan</h4>
                        <p class="text-[11px] text-zinc-400 mt-1">21 Juni 2026. Pemungutan suara secara elektronik / e-voting.</p>
                    </div>
                </div>
            </div>

            <!-- Grand Action Button Container (Proses Bakal Calon dan Pencalonan) -->
            <div class="text-center mb-8">
                <button 
                    @click="showNominationOptions = !showNominationOptions"
                    class="group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl bg-gradient-to-r from-red-600 via-red-500 to-red-700 px-8 py-4 text-sm sm:text-base font-bold uppercase tracking-wider text-white shadow-lg shadow-red-200/80 transition-all duration-300 hover:scale-102 hover:shadow-xl hover:shadow-red-300/80 active:scale-98"
                >
                    <!-- Background light effect -->
                    <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <Sparkles class="h-5 w-5" />
                    <span>Proses bakal calon dan pencalonan</span>
                    <ChevronRight class="h-5 w-5 transition-transform duration-300" :class="{ 'rotate-90': showNominationOptions }" />
                </button>
            </div>

            <!-- Expandable Nomination Panel containing the 2 requested cards -->
            <div v-if="showNominationOptions" class="grid grid-cols-1 gap-6 sm:grid-cols-2 mb-8 transition-all duration-300 animate-in fade-in slide-in-from-bottom-5">
                
                <!-- Action 1: Ajukan Diri Sebagai El Presidente -->
                <div 
                    class="group flex flex-col justify-between rounded-2xl border p-6 transition-all duration-300 bg-white shadow-xl shadow-red-100/40"
                    :class="activeForm === 'self' ? 'border-red-600 ring-2 ring-red-100' : 'border-red-100 hover:border-red-400'"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="rounded-xl bg-red-50 border border-red-100 p-3 text-red-600">
                                <UserCheck class="h-6 w-6" />
                            </div>
                            <span class="rounded bg-red-600/10 px-2.5 py-0.5 font-mono text-[10px] font-bold text-red-500 border border-red-500/20">Self Nomination</span>
                        </div>
                        
                        <h3 class="font-oswald text-xl font-bold tracking-wide text-zinc-950 mt-4 uppercase">
                            Ajukan Diri Sebagai El Presidente
                        </h3>
                        <p class="text-xs leading-relaxed text-zinc-500 mt-2">
                            Daftarkan diri Anda secara resmi sebagai bakal calon presiden. Pastikan Anda telah memenuhi syarat dasar keanggotaan (minimal berstatus Life Member) dan bersedia menyertakan visi misi tertulis.
                        </p>
                    </div>

                    <button 
                        @click="activeForm = 'self'; window.scrollTo({ top: 500, behavior: 'smooth' })"
                        class="mt-6 w-full flex items-center justify-center gap-1.5 rounded-xl border border-red-500/20 bg-red-50 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-red-600 transition-all hover:bg-red-600 hover:text-white"
                    >
                        <span>Mulai Pendaftaran Mandiri</span>
                        <ChevronRight class="h-3.5 w-3.5" />
                    </button>
                </div>

                <!-- Action 2: Ajukan Anggota Sebagai El Presidente -->
                <div 
                    class="group flex flex-col justify-between rounded-2xl border p-6 transition-all duration-300 bg-white shadow-xl shadow-red-100/40"
                    :class="activeForm === 'member' ? 'border-red-600 ring-2 ring-red-100' : 'border-red-100 hover:border-red-400'"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="rounded-xl bg-red-50 border border-red-100 p-3 text-red-600">
                                <Users class="h-6 w-6" />
                            </div>
                            <span class="rounded bg-red-600/10 px-2.5 py-0.5 font-mono text-[10px] font-bold text-red-500 border border-red-500/20">Endorsement</span>
                        </div>
                        
                        <h3 class="font-oswald text-xl font-bold tracking-wide text-zinc-950 mt-4 uppercase">
                            Ajukan Anggota Sebagai El Presidente
                        </h3>
                        <p class="text-xs leading-relaxed text-zinc-500 mt-2">
                            Calonkan saudara satu aspal yang Anda nilai cakap, memiliki kepemimpinan mumpuni, serta berdedikasi tinggi demi masa depan persaudaraan Bikers Brotherhood MC Indonesia.
                        </p>
                    </div>

                    <button 
                        @click="activeForm = 'member'; window.scrollTo({ top: 500, behavior: 'smooth' })"
                        class="mt-6 w-full flex items-center justify-center gap-1.5 rounded-xl border border-red-500/20 bg-red-50 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-red-600 transition-all hover:bg-red-600 hover:text-white"
                    >
                        <span>Rekomendasikan Saudara</span>
                        <ChevronRight class="h-3.5 w-3.5" />
                    </button>
                </div>

            </div>

            <!-- Dynamic Input Forms based on selection -->
            <div v-if="showNominationOptions && activeForm" class="bg-white rounded-2xl border border-red-200 p-6 shadow-xl shadow-red-100/50 mb-8 animate-in fade-in zoom-in-95 duration-200">
                
                <!-- Form Header -->
                <div class="border-b border-red-100 pb-4 mb-5 flex items-center justify-between">
                    <h3 class="font-oswald text-lg font-bold text-zinc-950 uppercase tracking-wide flex items-center gap-2">
                        <BookOpen class="h-5 w-5 text-red-600" />
                        <span>{{ activeForm === 'self' ? 'Formulir Pengajuan Diri Calon Presiden' : 'Formulir Rekomendasi Calon Presiden' }}</span>
                    </h3>
                    <button @click="activeForm = null" class="text-xs font-bold uppercase text-zinc-400 hover:text-red-600 px-2 py-1 transition-colors">Batal</button>
                </div>

                <!-- FORM A: Self Nomination Form -->
                <form v-if="activeForm === 'self'" @submit.prevent="handleSelfSubmit" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-1">Nama Lengkap Anda <span class="text-red-500">*</span></label>
                            <input v-model="selfForm.nama" type="text" placeholder="Contoh: Budi 'Biker' Rahardjo" class="f-input" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-1">No. Kartu BBMC <span class="text-red-500">*</span></label>
                            <input v-model="selfForm.no_kartu" type="text" maxlength="4" placeholder="0000" class="f-input font-mono" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-1">Chapter <span class="text-red-500">*</span></label>
                            <select v-model="selfForm.chapter" class="f-input" required>
                                <option value="" disabled>Pilih chapter Anda</option>
                                <option value="Mother Chapter">Mother Chapter</option>
                                <option value="Jakarta Chapter">Jakarta Chapter</option>
                                <option value="Sumatera Chapter">Sumatera Chapter</option>
                                <option value="Central Java Chapter">Central Java Chapter</option>
                                <option value="East Java Chapter">East Java Chapter</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-1">Visi & Misi Utama <span class="text-red-500">*</span></label>
                        <textarea v-model="selfForm.visi_misi" rows="4" placeholder="Tulis visi misi Anda secara ringkas namun jelas untuk membawa kemajuan persaudaraan..." class="f-input resize-none" required></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-1">Program Kerja Unggulan (Opsional)</label>
                        <textarea v-model="selfForm.program_kerja" rows="3" placeholder="Tulis beberapa program kerja prioritas..." class="f-input resize-none"></textarea>
                    </div>

                    <div class="flex justify-end pt-3">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all">
                            <Send class="h-4 w-4" />
                            <span>Kirim Formulir Pencalonan</span>
                        </button>
                    </div>
                </form>

                <!-- FORM B: Member Nomination Form -->
                <form v-if="activeForm === 'member'" @submit.prevent="handleMemberSubmit" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-1">Nomor Kartu Anda <span class="text-red-500">*</span></label>
                            <input v-model="memberForm.nominator_no_kartu" type="text" maxlength="4" placeholder="Nomor KTA Pengusul" class="f-input font-mono" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-1">Nomor Kartu Calon yang Diusulkan <span class="text-red-500">*</span></label>
                            <input v-model="memberForm.candidate_no_kartu" type="text" maxlength="4" placeholder="Nomor KTA Calon" class="f-input font-mono" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-1">Nama Calon Presiden yang Diusulkan <span class="text-red-500">*</span></label>
                            <input v-model="memberForm.candidate_nama" type="text" placeholder="Nama saudara yang dicalonkan" class="f-input" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-1">Chapter Calon <span class="text-red-500">*</span></label>
                            <select v-model="memberForm.candidate_chapter" class="f-input" required>
                                <option value="" disabled>Pilih chapter calon</option>
                                <option value="Mother Chapter">Mother Chapter</option>
                                <option value="Jakarta Chapter">Jakarta Chapter</option>
                                <option value="Sumatera Chapter">Sumatera Chapter</option>
                                <option value="Central Java Chapter">Central Java Chapter</option>
                                <option value="East Java Chapter">East Java Chapter</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-1">Alasan Pengajuan & Rekomendasi <span class="text-red-500">*</span></label>
                        <textarea v-model="memberForm.alasan" rows="4" placeholder="Berikan ulasan singkat mengapa saudara ini layak diusulkan menjadi El Presidente berikutnya..." class="f-input resize-none" required></textarea>
                    </div>

                    <div class="flex justify-end pt-3">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all">
                            <Send class="h-4 w-4" />
                            <span>Kirim Rekomendasi Saudara</span>
                        </button>
                    </div>
                </form>

            </div>

            <!-- Bakal Calon Terdaftar (Registered Candidates Panel) -->
            <div class="bg-white rounded-2xl border border-red-100 p-6 shadow-xl shadow-red-100/40">
                <h2 class="font-oswald text-lg font-bold text-zinc-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <ShieldCheck class="h-5 w-5 text-red-600" />
                    <span>Daftar Bakal Calon Terdaftar</span>
                </h2>
                
                <div class="space-y-4">
                    <div 
                        v-for="c in registeredCandidates" 
                        :key="c.no_kartu"
                        class="group relative flex flex-col md:flex-row gap-4 justify-between border border-red-50 bg-red-50/20 p-5 rounded-2xl transition-all duration-300 hover:border-red-200 hover:bg-white hover:shadow-md"
                    >
                        <div class="flex-1 text-left">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="rounded bg-red-600/10 px-2 py-0.5 font-mono text-xs font-bold text-red-600 border border-red-500/20">KTA: {{ c.no_kartu }}</span>
                                <span class="rounded bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 border border-zinc-200">{{ c.chapter }}</span>
                                <span :class="['rounded px-2.5 py-0.5 text-xs font-semibold border', c.status_color]">{{ c.status }}</span>
                            </div>
                            
                            <h4 class="font-oswald text-lg font-bold text-zinc-950 group-hover:text-red-700 transition-colors uppercase">
                                {{ c.nama }}
                            </h4>
                            
                            <p class="text-xs leading-relaxed text-zinc-500 mt-2 italic group-hover:text-zinc-600">
                                "{{ c.visi }}"
                            </p>
                        </div>

                        <!-- Right badge representative -->
                        <div class="flex items-center justify-end border-t md:border-t-0 md:border-l border-red-100/50 pt-3 md:pt-0 md:pl-5 shrink-0">
                            <div class="text-right">
                                <span class="block text-[10px] text-zinc-400 uppercase tracking-widest font-semibold">Dewan Adat Status</span>
                                <span class="block text-xs font-bold text-zinc-700 uppercase mt-0.5">Menunggu Verifikasi</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notice Badge -->
                <div class="mt-6 flex items-start gap-2.5 rounded-xl bg-zinc-50 border border-zinc-200 p-4 text-xs text-zinc-500">
                    <Info class="h-4.5 w-4.5 text-red-500 mt-0.5 shrink-0" />
                    <p class="leading-relaxed">
                        Seluruh berkas pencalonan mandiri maupun rekomendasi dari anggota akan diverifikasi secara teliti oleh **Dewan Adat BBMC Indonesia** untuk memastikan keabsahan status kepesertaan, loyalitas, kelayakan hukum adat, dan kepemimpinan moral sebelum dipublikasikan sebagai Calon Presiden resmi.
                    </p>
                </div>
            </div>

        </main>

        <!-- Footer Segment consistent with Welcome.vue -->
        <footer class="relative z-10 w-full border-t border-red-100 bg-white/40 py-8 backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <!-- Motto Segment -->
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center gap-4 w-full max-w-lg mb-6">
                        <div class="h-[1px] flex-grow bg-red-100"></div>
                        <span class="font-bebas text-lg tracking-[0.25em] text-zinc-500 select-none">HONOR RESPECT BROTHERHOOD</span>
                        <div class="h-[1px] flex-grow bg-red-100"></div>
                    </div>
                    
                    <span class="font-bebas text-2xl tracking-[0.4em] text-red-600 animate-pulse">NO SURRENDER</span>
                </div>

                <!-- Horizontal separator -->
                <div class="my-6 h-[1px] w-full bg-red-100"></div>

                <!-- Bottom Footer Details -->
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row text-center sm:text-left">
                    <p class="text-xs text-zinc-500">
                        &copy; {{ new Date().getFullYear() }} <span class="text-zinc-600 font-semibold">Bikers Brotherhood Motorcycle Club Indonesia</span>. All Rights Reserved.
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
.f-input {
    @apply w-full bg-white border border-red-100 focus:border-red-500 text-zinc-800 placeholder-zinc-400 rounded-xl px-4 py-2.5 text-sm outline-none transition-all duration-200 focus:ring-2 focus:ring-red-100;
}
</style>