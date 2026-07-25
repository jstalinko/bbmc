<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { decryptPayload, encryptPayload } from '@/lib/crypto';
import { 
    ShieldCheck, 
    UserCheck, 
    Users, 
    Info, 
    CheckCircle2, 
    ChevronRight, 
    Award, 
    ArrowLeft, 
    Send, 
    Search,
    BookOpen,
    HelpCircle,
    UserX,
    Loader2,
    KeyRound,
    X,
    LogOut
} from 'lucide-vue-next';

const page = usePage();
const electionMember = ref<any>(page.props.auth.election_member);

const handleLogout = () => {
    const logoutForm = useForm({});
    logoutForm.post(route('election.logout'));
};

const props = defineProps<{
    errors?: Record<string, string>;
    flash?: {
        success?: boolean;
        message?: string;
        error?: string;
    };
    settings?: {
        ajukan_diri: boolean;
        ajukan_anggota: boolean;
        tanggal_mulai: string | null;
        tanggal_selesai: string | null;
    };
    userNomination?: any;
}>();

const settings = computed(() => props.settings ?? {
    ajukan_diri: true,
    ajukan_anggota: true,
    tanggal_mulai: null,
    tanggal_selesai: null
});

// State management for interactive forms
const activeForm = ref<'self' | 'member' | null>(null);
const submissionSuccess = ref(false);
const hasSubmittedThisSession = ref(false);
const submittedName = ref('');
const flashMessage = ref('');

const hasAlreadyNominated = computed(() => {
    return Boolean(props.userNomination || hasSubmittedThisSession.value);
});

const isElPresidente = computed(() => {
    return electionMember.value && electionMember.value.jabatan && electionMember.value.jabatan.trim().toLowerCase() === 'el presidente';
});

// Autocomplete States for Self-Nomination
const selfCardQuery = ref('');
const selfMemberDetails = ref<any>(null);
const selfSearchError = ref('');
const isSelfSearching = ref(false);

// Autocomplete States for Member Nomination (Candidate Search)
const candidateSearchQuery = ref('');
const candidateSearchResults = ref<any[]>([]);
const candidateMemberDetails = ref<any>(null);
const isCandidateSearching = ref(false);
const showCandidateDropdown = ref(false);

// Autocomplete States for Member Nomination (Nominator Search)
const nominatorCardQuery = ref('');
const nominatorMemberDetails = ref<any>(null);
const nominatorSearchError = ref('');
const isNominatorSearching = ref(false);

// ── OTP Modal State ────────────────────────────────────────────────────────────
const showOtpModal = ref(false);
const otpCode = ref('');
const isSendingOtp = ref(false);
const isSubmittingWithOtp = ref(false);
const otpError = ref('');
const otpTarget = ref<'self' | 'member' | null>(null);
const otpSentMessage = ref('');
const otpCountdown = ref(0);
let countdownTimer: any = null;

const startOtpCountdown = (seconds = 60) => {
    otpCountdown.value = seconds;
    if (countdownTimer) clearInterval(countdownTimer);
    countdownTimer = setInterval(() => {
        if (otpCountdown.value > 0) {
            otpCountdown.value--;
        } else {
            if (countdownTimer) clearInterval(countdownTimer);
        }
    }, 1000);
};
// ──────────────────────────────────────────────────────────────────────────────

// Inertia Forms
const selfForm = useForm({
    no_kartu: '',
    otp: '',
});

const memberForm = useForm({
    candidate_name: '',
    candidate_no_kartu: '',
    candidate_id: null as number | null,
    nominator_no_kartu: '',
    otp: '',
});

// Watch self KTA input to trigger autocomplete search
watch(selfCardQuery, async (newVal) => {
    selfForm.no_kartu = newVal;
    selfMemberDetails.value = null;
    selfSearchError.value = '';
    
    if (newVal.length >= 2) {
        isSelfSearching.value = true;
        try {
            const response = await fetch(`/election/member-info/${encodeURIComponent(encryptPayload(newVal))}?role=candidate`);
            const data = await response.json();
            if (data.success) {
                selfMemberDetails.value = decryptPayload(data.payload);
                selfSearchError.value = '';
            } else {
                selfSearchError.value = data.message;
            }
        } catch (e) {
            selfSearchError.value = 'Gagal memuat data member.';
        } finally {
            isSelfSearching.value = false;
        }
    }
});

// Watch candidate name search to trigger autocomplete list
watch(candidateSearchQuery, async (newVal) => {
    if (candidateMemberDetails.value && newVal === candidateMemberDetails.value.nama_lengkap) {
        return;
    }
    memberForm.candidate_name = newVal;
    memberForm.candidate_no_kartu = '';
    memberForm.candidate_id = null;
    candidateMemberDetails.value = null;
    candidateSearchResults.value = [];
    
    if (newVal.length >= 2) {
        isCandidateSearching.value = true;
        showCandidateDropdown.value = true;
        try {
            const response = await fetch(`/election/search-members?q=${newVal}`);
            const data = await response.json();
            candidateSearchResults.value = data;
        } catch (e) {
            console.error('Gagal mencari member.', e);
        } finally {
            isCandidateSearching.value = false;
        }
    } else {
        showCandidateDropdown.value = false;
    }
});

const selectCandidate = (member: any) => {
    candidateMemberDetails.value = member;
    candidateSearchQuery.value = member.nama_lengkap;
    memberForm.candidate_name = member.nama_lengkap;
    memberForm.candidate_no_kartu = member.no_kartu;
    memberForm.candidate_id = member.id;
    showCandidateDropdown.value = false;
};

// Watch nominator KTA input to trigger autocomplete search
// Watch nominator KTA input to trigger autocomplete search
watch(nominatorCardQuery, async (newVal) => {
    memberForm.nominator_no_kartu = newVal;
    nominatorMemberDetails.value = null;
    nominatorSearchError.value = '';
    
    // Reset candidate selection when nominator card is modified or re-verified
    candidateSearchQuery.value = '';
    candidateMemberDetails.value = null;
    memberForm.candidate_name = '';
    memberForm.candidate_no_kartu = '';
    memberForm.candidate_id = null;
    candidateSearchResults.value = [];
    showCandidateDropdown.value = false;
    
    if (newVal.length >= 2) {
        isNominatorSearching.value = true;
        try {
            const response = await fetch(`/election/member-info/${encodeURIComponent(encryptPayload(newVal))}?role=nominator`);
            const data = await response.json();
            if (data.success) {
                nominatorMemberDetails.value = decryptPayload(data.payload);
                nominatorSearchError.value = '';
            } else {
                nominatorSearchError.value = data.message;
            }
        } catch (e) {
            nominatorSearchError.value = 'Gagal memuat data member.';
        } finally {
            isNominatorSearching.value = false;
        }
    }
});

const requestOtp = async (formType: 'self' | 'member', isResend = false) => {
    if (!isResend) {
        otpCode.value = '';
        otpError.value = '';
        if (formType === 'self') {
            if (selfSearchError.value) return;
            if (!selfMemberDetails.value || selfForm.no_kartu !== selfMemberDetails.value.no_kartu) {
                selfSearchError.value = `Nomor kartu ${selfForm.no_kartu || selfCardQuery.value} tidak valid atau tidak terdaftar.`;
                return;
            }
        } else {
            if (!candidateMemberDetails.value) return;
            if (nominatorSearchError.value) return;
            if (!nominatorMemberDetails.value || memberForm.nominator_no_kartu !== nominatorMemberDetails.value.no_kartu) {
                nominatorSearchError.value = `Nomor kartu pengusul (${memberForm.nominator_no_kartu || nominatorCardQuery.value}) tidak valid atau tidak terdaftar.`;
                return;
            }
        }
    }
    isSendingOtp.value = true;
    try {
        const response = await fetch('/api/send-otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                type: formType,
                no_kartu: formType === 'self' ? selfForm.no_kartu : memberForm.nominator_no_kartu
            })
        });
        const data = await response.json();
        if (data.success) {
            otpTarget.value = formType;
            otpSentMessage.value = data.message;
            showOtpModal.value = true;
            otpError.value = '';
            startOtpCountdown(60);
        } else {
            if (showOtpModal.value || isResend) {
                otpError.value = data.message;
            } else {
                if (formType === 'self') {
                    selfSearchError.value = data.message;
                } else {
                    nominatorSearchError.value = data.message;
                }
            }
        }
    } catch (e) {
        if (showOtpModal.value || isResend) {
            otpError.value = 'Gagal mengirim OTP: ' + e;
        } else {
            if (formType === 'self') {
                selfSearchError.value = 'Gagal mengirim OTP.';
            } else {
                nominatorSearchError.value = 'Gagal mengirim OTP.';
            }
        }
    } finally {
        isSendingOtp.value = false;
    }
};

const confirmOtpAndSubmit = () => {
    if (!otpCode.value) {
        otpError.value = 'Masukkan kode OTP.';
        return;
    }
    isSubmittingWithOtp.value = true;
    otpError.value = '';

    const form = otpTarget.value === 'self' ? selfForm : memberForm;
    form.otp = otpCode.value;
    const url = otpTarget.value === 'self' ? '/election/nominate-self' : '/election/nominate-member';

    form.post(url, {
        onSuccess: (page: any) => {
            submittedName.value = otpTarget.value === 'self' ? selfMemberDetails.value?.nama_lengkap : memberForm.candidate_name;
            flashMessage.value = page.props.flash?.message || 'Pengajuan berhasil!';
            submissionSuccess.value = true;
            hasSubmittedThisSession.value = true;
            closeOtpModal();
            if (otpTarget.value === 'self') {
                selfCardQuery.value = '';
                selfForm.reset();
                activeForm.value = null;
            } else {
                candidateSearchQuery.value = '';
                nominatorCardQuery.value = '';
                memberForm.reset();
                activeForm.value = null;
            }
        },
        onError: (err: any) => {
            const firstError = err.otp || err.candidate_name || err.no_kartu || err.nominator_no_kartu || Object.values(err)[0] || 'Kode OTP tidak valid atau kadaluwarsa.';
            otpError.value = firstError;
        },
        onFinish: () => {
            isSubmittingWithOtp.value = false;
        }
    });
};

const handleMemberSubmit = () => {
    requestOtp('member');
};

const selectForm = (type: 'self' | 'member') => {
    if (hasAlreadyNominated.value) return;
    if (type === 'self' && isElPresidente.value) return;
    activeForm.value = type;
    if (electionMember.value && electionMember.value.no_kartu) {
        if (type === 'self' && !selfCardQuery.value) {
            selfCardQuery.value = electionMember.value.no_kartu;
        } else if (type === 'member' && !nominatorCardQuery.value) {
            nominatorCardQuery.value = electionMember.value.no_kartu;
        }
    }
    setTimeout(() => {
        const formEl = document.getElementById('nomination-form-section');
        if (formEl) {
            formEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            window.scrollTo({ top: 350, behavior: 'smooth' });
        }
    }, 50);
};

const closeOtpModal = () => {
    showOtpModal.value = false;
    otpCode.value = '';
    otpError.value = '';
    otpTarget.value = null;
    isSubmittingWithOtp.value = false;
    if (countdownTimer) clearInterval(countdownTimer);
    otpCountdown.value = 0;
};

const closeAlert = () => {
    submissionSuccess.value = false;
    activeForm.value = null;
    flashMessage.value = '';
};

const selectedPhotoModal = ref<{
    foto: string | null;
    nama_lengkap: string;
    no_kartu: string;
    chapter: string;
    status_keanggotaan: string;
    title: string;
} | null>(null);

const openPhotoModal = (member: any, title = 'Foto Anggota') => {
    if (!member || !member.foto) return;
    selectedPhotoModal.value = {
        foto: member.foto,
        nama_lengkap: member.nama_lengkap,
        no_kartu: member.no_kartu,
        chapter: member.chapter,
        status_keanggotaan: member.status_keanggotaan,
        title
    };
};

const closePhotoModal = () => {
    selectedPhotoModal.value = null;
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
                    <span class="text-xs font-bold uppercase tracking-wider hidden sm:inline">Kembali</span>
                </Link>
                
                <div class="flex items-center gap-2 sm:gap-3">
                    <img src="/bbmc-logo.png" class="h-7 sm:h-8 w-auto filter drop-shadow" alt="Logo" />
                    <span class="font-bebas text-base sm:text-lg tracking-wider text-red-600">BBMC ELECTION 2026</span>
                </div>

                <div class="w-16 hidden sm:block"></div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="relative z-10 mx-auto flex w-full max-w-5xl flex-col px-4 py-6 sm:py-10 sm:px-6 lg:px-8">
            
            <!-- Hero Title Segment -->
            <div class="mb-6 sm:mb-10 text-center">
                <div class="mb-2 sm:mb-3 inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-white px-3 py-1 text-[10px] sm:text-xs uppercase tracking-[0.2em] text-red-600 shadow-sm">
                    <Award class="h-3.5 w-3.5 animate-bounce" />
                    <span>Pra-Election Portal</span>
                </div>
                <h1 class="font-bebas text-3xl sm:text-6xl tracking-wide text-transparent bg-clip-text bg-gradient-to-r from-zinc-950 via-red-600 to-red-800 leading-tight sm:leading-normal">
                    EL PRESIDENTE BBMC INDONESIA
                </h1>
                <p class="mx-auto mt-1 sm:mt-2 max-w-lg text-[11px] sm:text-xs tracking-[0.15em] text-zinc-600 font-semibold uppercase">
                    Masa Bakti Bhakti 2026 — 2030
                </p>
                <div class="mt-3 h-[2px] w-24 mx-auto bg-gradient-to-r from-transparent via-red-500 to-transparent"></div>
            </div>

            <!-- SUCCESS DIALOG ALERT MODAL -->
            <div v-if="submissionSuccess" class="mb-8 rounded-2xl border border-green-200 bg-green-50 p-6 shadow-lg shadow-green-100/50 flex gap-4 items-start transition-all duration-300">
                <div class="rounded-xl bg-green-500/10 border border-green-500/20 p-2.5 text-green-600">
                    <CheckCircle2 class="h-6 w-6" />
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-green-800 uppercase tracking-wide">Pengajuan Berhasil Dikirim!</h3>
                    <p class="text-sm text-green-700 mt-1">
                        {{ flashMessage || 'Formulir pencalonan untuk ' + submittedName + ' telah terkirim secara aman ke Dewan Adat.' }}
                    </p>
                    <p class="text-xs text-green-800 font-semibold mt-2 border-t border-green-200 pt-2">
                        Sesuai ketentuan, hak pengajuan pencalonan Anda telah digunakan. Setiap anggota hanya diperbolehkan melakukan 1 (satu) kali pengajuan/pilihan.
                    </p>
                    <button 
                        @click="closeAlert" 
                        class="mt-3 inline-flex items-center gap-1 px-4 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase tracking-wider transition-all"
                    >
                        Tutup
                    </button>
                </div>
            </div>

            <!-- ERROR FLASH BANNER -->
            <div v-if="$page.props.flash?.error" class="mb-8 rounded-2xl border border-red-200 bg-red-50 p-6 shadow-lg shadow-red-100/50 flex gap-4 items-start transition-all duration-300">
                <div class="rounded-xl bg-red-500/10 border border-red-500/20 p-2.5 text-red-600">
                    <Info class="h-6 w-6" />
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-red-800 uppercase tracking-wide">Pemberitahuan</h3>
                    <p class="text-sm text-red-700 mt-1">
                        {{ $page.props.flash.error }}
                    </p>
                </div>
            </div>

            <!-- Nomination Cards Section (Directly displayed when no form is active) -->
            <div v-if="!activeForm" class="grid grid-cols-1 gap-4 sm:gap-6 sm:grid-cols-2 mb-8 animate-in fade-in duration-300">
                
                <!-- Status Banner if already nominated -->
                <div v-if="hasAlreadyNominated" class="col-span-1 sm:col-span-2 rounded-2xl border border-red-200 bg-red-50 p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row items-start gap-3 sm:gap-4 animate-in fade-in">
                    <div class="rounded-xl bg-red-500/10 border border-red-500/20 p-2.5 text-red-600 shrink-0">
                        <CheckCircle2 class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="font-oswald text-base font-bold text-zinc-900 uppercase">Hak Pengajuan Pencalonan Telah Digunakan</h4>
                        <p class="text-xs sm:text-sm text-zinc-600 mt-1 leading-relaxed">
                            <template v-if="props.userNomination">
                                Anda (KTA: <strong class="font-mono text-red-600">{{ props.userNomination.diajukan_oleh === 'self' ? props.userNomination.no_kartu : (props.userNomination.no_kartu_diajukan_oleh || props.userNomination.no_kartu) }}</strong>) sudah melakukan pengajuan pencalonan dengan pilihan:
                                <strong class="text-zinc-900">{{ props.userNomination.diajukan_oleh === 'self' ? 'Ajukan Diri Sebagai El Presidente (Self Nomination)' : 'Ajukan Anggota Sebagai El Presidente (Endorsement)' }}</strong>.
                            </template>
                            <template v-else>
                                Anda telah berhasil mengirimkan formulir pengajuan pencalonan.
                            </template>
                            Sesuai ketentuan pemilihan, setiap anggota hanya diperbolehkan melakukan <strong>1 (satu) kali pengajuan</strong> dan <strong>hanya memilih satu pilihan saja</strong> (memilih diri sendiri ataupun mengajukan anggota lain).
                        </p>
                    </div>
                </div>

                <!-- Action 1: Ajukan Diri Sebagai El Presidente -->
                <div 
                    class="group flex flex-col justify-between rounded-2xl border p-5 sm:p-6 transition-all duration-300 bg-white shadow-xl shadow-red-100/40"
                    :class="!settings.ajukan_diri || hasAlreadyNominated || isElPresidente ? 'opacity-65 border-zinc-200' : (activeForm === 'self' ? 'border-red-600 ring-2 ring-red-100' : 'border-red-100 hover:border-red-400 hover:shadow-2xl hover:-translate-y-0.5')"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="rounded-xl bg-red-50 border border-red-100 p-3 text-red-600 group-hover:bg-red-600 group-hover:text-white transition-colors">
                                <UserCheck class="h-6 w-6" />
                            </div>
                            <span class="rounded bg-red-600/10 px-2.5 py-0.5 font-mono text-[10px] sm:text-xs font-bold text-red-500 border border-red-500/20">Self Nomination</span>
                        </div>
                        
                        <h3 class="font-oswald text-lg sm:text-xl font-bold tracking-wide text-zinc-950 mt-4 uppercase">
                            Ajukan Diri Sebagai El Presidente
                        </h3>
                        <p class="text-xs sm:text-sm leading-relaxed text-zinc-500 mt-2">
                            Daftarkan diri Anda secara resmi sebagai bakal calon presiden (Khusus Life Member &amp; SS Diponegoro dengan masa keanggotaan minimal 10 tahun dan status Clean/tanpa penalty). Profil anggota Anda akan otomatis dimuat dan diverifikasi.
                        </p>
                    </div>

                    <div v-if="!settings.ajukan_diri" class="mt-6 w-full text-center py-2.5 sm:py-3 bg-zinc-100 text-zinc-500 text-xs font-bold uppercase rounded-xl border border-zinc-200">
                        Pendaftaran Mandiri Ditutup
                    </div>
                    <div v-else-if="hasAlreadyNominated" class="mt-6 w-full text-center py-2.5 sm:py-3 bg-red-100/60 text-red-600 text-xs font-bold uppercase rounded-xl border border-red-200 cursor-not-allowed">
                        Hak Pengajuan Telah Digunakan
                    </div>
                    <div v-else-if="isElPresidente" class="mt-6 w-full text-center py-2.5 sm:py-3 bg-zinc-100 text-zinc-500 text-xs font-bold uppercase rounded-xl border border-zinc-200 cursor-not-allowed">
                        Jabatan El Presidente Tidak Bisa Mengajukan Diri
                    </div>
                    <button 
                        v-else
                        @click="selectForm('self')"
                        class="mt-6 w-full flex items-center justify-center gap-1.5 rounded-xl border border-red-500/20 bg-red-50 px-4 py-3 text-xs sm:text-sm font-bold uppercase tracking-wider text-red-600 transition-all hover:bg-red-600 hover:text-white active:scale-[0.98]"
                    >
                        <span>Mulai Pendaftaran Mandiri</span>
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>

                <!-- Action 2: Ajukan Anggota Sebagai El Presidente -->
                <div 
                    class="group flex flex-col justify-between rounded-2xl border p-5 sm:p-6 transition-all duration-300 bg-white shadow-xl shadow-red-100/40"
                    :class="!settings.ajukan_anggota || hasAlreadyNominated ? 'opacity-65 border-zinc-200' : (activeForm === 'member' ? 'border-red-600 ring-2 ring-red-100' : 'border-red-100 hover:border-red-400 hover:shadow-2xl hover:-translate-y-0.5')"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="rounded-xl bg-red-50 border border-red-100 p-3 text-red-600 group-hover:bg-red-600 group-hover:text-white transition-colors">
                                <Users class="h-6 w-6" />
                            </div>
                            <span class="rounded bg-red-600/10 px-2.5 py-0.5 font-mono text-[10px] sm:text-xs font-bold text-red-500 border border-red-500/20">Endorsement</span>
                        </div>
                        
                        <h3 class="font-oswald text-lg sm:text-xl font-bold tracking-wide text-zinc-950 mt-4 uppercase">
                            Ajukan Anggota Sebagai El Presidente
                        </h3>
                        <p class="text-xs sm:text-sm leading-relaxed text-zinc-500 mt-2">
                            Calonkan saudara (Life Member / SS Diponegoro terdaftar minimal 10 tahun dan berstatus Clean/tanpa penalty) yang Anda nilai layak. Semua status member (berstatus Clean) dapat mengajukan rekomendasi ini.
                        </p>
                    </div>

                    <div v-if="!settings.ajukan_anggota" class="mt-6 w-full text-center py-2.5 sm:py-3 bg-zinc-100 text-zinc-500 text-xs font-bold uppercase rounded-xl border border-zinc-200">
                        Rekomendasi Saudara Ditutup
                    </div>
                    <div v-else-if="hasAlreadyNominated" class="mt-6 w-full text-center py-2.5 sm:py-3 bg-red-100/60 text-red-600 text-xs font-bold uppercase rounded-xl border border-red-200 cursor-not-allowed">
                        Hak Pengajuan Telah Digunakan
                    </div>
                    <button 
                        v-else
                        @click="selectForm('member')"
                        class="mt-6 w-full flex items-center justify-center gap-1.5 rounded-xl border border-red-500/20 bg-red-50 px-4 py-3 text-xs sm:text-sm font-bold uppercase tracking-wider text-red-600 transition-all hover:bg-red-600 hover:text-white active:scale-[0.98]"
                    >
                        <span>Rekomendasikan Saudara</span>
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>

            </div>

            <!-- Dynamic Input Forms based on selection -->
            <div v-if="activeForm" id="nomination-form-section" class="bg-white rounded-2xl border border-red-200 p-4 sm:p-6 shadow-xl shadow-red-100/50 mb-8 animate-in fade-in zoom-in-95 duration-200 scroll-mt-6">
                
                <!-- OTP Modal -->
                <div v-if="showOtpModal" class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-50">
                    <div class="bg-white rounded-xl shadow-lg p-6 w-80">
                        <h3 class="text-lg font-semibold mb-2">Masukkan Kode OTP</h3>
                        <p class="text-sm text-gray-600 mb-4">{{ otpSentMessage }}</p>
                        <input v-model="otpCode" type="text" maxlength="6" placeholder="6 digit OTP" class="w-full border border-gray-300 rounded px-3 py-2 mb-2 focus:outline-none focus:border-red-500 font-mono text-center tracking-widest text-lg" />
                        <p v-if="otpError" class="text-red-500 text-sm mb-2">{{ otpError }}</p>
                        <div class="flex items-center justify-between mt-3 mb-1">
                            <span class="text-xs text-gray-500">Belum terima kode?</span>
                            <button
                                type="button"
                                @click="otpTarget && requestOtp(otpTarget, true)"
                                :disabled="otpCountdown > 0 || isSendingOtp"
                                class="text-xs font-semibold text-red-600 hover:text-red-700 disabled:text-gray-400 disabled:cursor-not-allowed transition-colors"
                            >
                                {{ otpCountdown > 0 ? `Kirim Ulang (${otpCountdown}s)` : (isSendingOtp ? 'Mengirim...' : 'Kirim Ulang OTP') }}
                            </button>
                        </div>
                        <div class="flex justify-end space-x-2 mt-4">
                            <button @click="closeOtpModal" type="button" class="px-4 py-2 text-sm rounded bg-gray-200 hover:bg-gray-300">Batal</button>
                            <button @click="confirmOtpAndSubmit" :disabled="isSubmittingWithOtp" class="px-4 py-2 text-sm rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-50">{{ isSubmittingWithOtp ? 'Mengirim...' : 'Konfirmasi' }}</button>
                        </div>
                    </div>
                </div>

                <!-- Photo Modal (Enlarge Photo View) -->
                <div v-if="selectedPhotoModal" class="fixed inset-0 flex items-center justify-center bg-black/80 backdrop-blur-md z-[100] p-4 animate-in fade-in duration-200">
                    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-zinc-100 flex flex-col relative animate-in zoom-in-95 duration-200">
                        <!-- Header / Title -->
                        <div class="p-4 sm:p-5 border-b border-zinc-100 flex items-center justify-between bg-zinc-50/80">
                            <div class="flex items-center gap-2">
                                <span class="rounded bg-red-600/10 px-2 py-0.5 font-mono text-[10px] font-bold text-red-600 border border-red-500/20 uppercase">{{ selectedPhotoModal.title }}</span>
                            </div>
                            <button @click="closePhotoModal" type="button" class="rounded-full p-1 text-zinc-400 hover:bg-zinc-200 hover:text-zinc-700 transition-colors">
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Photo Display -->
                        <div class="p-6 bg-zinc-950 flex items-center justify-center relative min-h-[260px] max-h-[60vh]">
                            <img 
                                v-if="selectedPhotoModal.foto" 
                                :src="'/storage/' + selectedPhotoModal.foto" 
                                class="max-h-[50vh] w-auto object-contain rounded-xl shadow-lg mx-auto" 
                                alt="Foto Anggota Diperbesar"
                            />
                        </div>

                        <!-- Details Below Photo -->
                        <div class="p-5 bg-white text-center sm:text-left space-y-2">
                            <h4 class="font-oswald text-xl font-bold text-zinc-950 uppercase tracking-wide">
                                {{ selectedPhotoModal.nama_lengkap }}
                            </h4>
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-1.5 text-xs">
                                <span class="rounded bg-zinc-100 px-2 py-0.5 font-mono font-bold text-zinc-800 border border-zinc-200">KTA: {{ selectedPhotoModal.no_kartu }}</span>
                                <span class="rounded bg-zinc-100 px-2 py-0.5 font-semibold text-zinc-800 border border-zinc-200">Chapter: {{ selectedPhotoModal.chapter }}</span>
                                <span class="rounded bg-red-50 px-2 py-0.5 font-bold text-red-600 border border-red-200">Status: {{ selectedPhotoModal.status_keanggotaan }}</span>
                            </div>
                        </div>

                        <!-- Modal Action -->
                        <div class="p-4 border-t border-zinc-100 bg-zinc-50 flex justify-end">
                            <button @click="closePhotoModal" type="button" class="px-5 py-2 text-xs font-bold uppercase tracking-wider rounded-xl bg-zinc-900 text-white hover:bg-zinc-800 transition-colors">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Header with Back Button -->
                <div class="border-b border-red-100 pb-4 mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <button 
                            @click="activeForm = null" 
                            type="button"
                            class="group inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-red-50 hover:bg-red-600 text-red-600 hover:text-white text-xs font-bold uppercase tracking-wider transition-all border border-red-200 shadow-sm shrink-0"
                        >
                            <ArrowLeft class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                            <span>Kembali ke Menu</span>
                        </button>
                        <h3 class="font-oswald text-base sm:text-lg font-bold text-zinc-950 uppercase tracking-wide flex items-center gap-2">
                            <BookOpen class="h-5 w-5 text-red-600 shrink-0" />
                            <span>{{ activeForm === 'self' ? 'Formulir Pengajuan Diri Calon Presiden' : 'Formulir Rekomendasi Calon Presiden' }}</span>
                        </h3>
                    </div>
                </div>

                <div class="mb-5 rounded-xl bg-red-50/70 border border-red-100 p-3.5 flex items-start gap-2.5 text-xs text-zinc-600">
                    <Info class="h-4.5 w-4.5 text-red-600 shrink-0 mt-0.5" />
                    <span class="leading-relaxed">
                        <strong>Perhatian:</strong> Sesuai ketentuan pemilihan, setiap anggota hanya memiliki <strong>1 (satu) kali hak pengajuan</strong> dan <strong>satu pilihan saja</strong> (memilih diri sendiri <em>ataupun</em> mengajukan orang lain). Setelah pengajuan berhasil diverifikasi, Anda tidak dapat mengubah atau mengajukan pilihan lain.
                    </span>
                </div>

                <!-- FORM A: Self Nomination Form -->
                <form v-if="activeForm === 'self'" @submit.prevent="requestOtp('self')" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-1">
                            Masukkan No. Kartu BBMC Anda <span class="text-red-500">*</span>
                        </label>
                        <div class="relative w-full max-w-sm">
                            <input 
                                v-model="selfCardQuery" 
                                type="text" 
                                maxlength="4" 
                                placeholder="Masukkan 4 digit nomor KTA Anda" 
                                class="f-input font-mono pr-10" 
                                required 
                            />
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <Loader2 v-if="isSelfSearching" class="h-4 w-4 text-red-500 animate-spin" />
                                <Search v-else class="h-4 w-4 text-zinc-400" />
                            </div>
                        </div>
                        
                        <!-- Search Error Warning -->
                        <p v-if="selfSearchError" class="text-xs text-red-600 font-medium mt-1 flex items-center gap-1 animate-in fade-in">
                            <UserX class="h-3.5 w-3.5" />
                            <span>{{ selfSearchError }}</span>
                        </p>
                    </div>

                    <!-- AUTO-COMPLETE DISPLAY: Profile Card with Centered Photo for Self Nomination -->
                    <div 
                        v-if="selfMemberDetails" 
                        class="bg-gradient-to-b from-red-50/80 via-white to-red-50/30 rounded-3xl border-2 border-red-200/80 p-6 flex flex-col items-center text-center shadow-lg shadow-red-100/50 animate-in zoom-in-95 duration-300 relative overflow-hidden"
                    >
                        <span class="inline-block rounded-full bg-red-600/10 px-3 py-1 font-mono text-xs font-bold uppercase tracking-wider text-red-600 border border-red-500/20 mb-4">
                            Pendaftar Mandiri Terverifikasi
                        </span>

                        <!-- Centered Circular Photo with Hover/Click Enlarge Overlay -->
                        <div 
                            class="relative group cursor-pointer" 
                            @click="selfMemberDetails.foto && openPhotoModal(selfMemberDetails, 'Foto Pendaftar Mandiri')"
                        >
                            <div class="h-32 w-32 sm:h-36 sm:w-36 rounded-full border-4 border-white shadow-xl overflow-hidden bg-zinc-100 flex items-center justify-center relative ring-4 ring-red-500/20 group-hover:ring-red-500/40 transition-all duration-300 mx-auto">
                                <img 
                                    v-if="selfMemberDetails.foto" 
                                    :src="'/storage/' + selfMemberDetails.foto" 
                                    class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300" 
                                    alt="Foto Member" 
                                />
                                <UserCheck v-else class="h-14 w-14 text-red-400" />
                            </div>
                            
                            <!-- Enlarge photo badge overlay -->
                            <div v-if="selfMemberDetails.foto" class="absolute inset-0 rounded-full bg-black/50 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center text-white transition-opacity duration-200">
                                <Search class="h-6 w-6 mb-1" />
                                <span class="text-[10px] font-bold uppercase tracking-wider">Perbesar</span>
                            </div>
                        </div>

                        <!-- Candidate Details -->
                        <div class="mt-4 space-y-2 w-full">
                            <h4 class="font-oswald text-xl sm:text-2xl font-bold text-zinc-950 uppercase tracking-wide">
                                {{ selfMemberDetails.nama_lengkap }}
                                <span v-if="selfMemberDetails.nama_panggilan" class="block sm:inline text-base font-normal text-zinc-500">({{ selfMemberDetails.nama_panggilan }})</span>
                            </h4>
                            
                            <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
                                <span class="rounded-lg bg-zinc-100 px-2.5 py-1 font-mono text-xs font-bold text-zinc-800 border border-zinc-200">
                                    KTA: {{ selfMemberDetails.no_kartu }}
                                </span>
                                <span class="rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-800 border border-zinc-200">
                                    Chapter: {{ selfMemberDetails.chapter }}
                                </span>
                                <span v-if="selfMemberDetails.checkpoint" class="rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-800 border border-zinc-200">
                                    Checkpoint: {{ selfMemberDetails.checkpoint }}
                                </span>
                                <span v-if="selfMemberDetails.region" class="rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-800 border border-zinc-200">
                                    Region: {{ selfMemberDetails.region }}
                                </span>
                                <span class="rounded-lg bg-red-100/80 px-2.5 py-1 text-xs font-bold text-red-700 border border-red-200">
                                    Status: {{ selfMemberDetails.status_keanggotaan }}
                                </span>
                            </div>
                        </div>
                    </div>



                    <div class="flex justify-end pt-3">
                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all disabled:opacity-40 disabled:pointer-events-none"
                            :disabled="!selfMemberDetails || selfForm.processing"
                        >
                            <Loader2 v-if="selfForm.processing" class="h-4 w-4 animate-spin" />
                            <Send v-else class="h-4 w-4" />
                            <span>Kirim Pengajuan Calon Presiden</span>
                        </button>
                    </div>
                </form>

                <!-- FORM B: Member Nomination Form -->
                <form v-if="activeForm === 'member'" @submit.prevent="handleMemberSubmit" class="space-y-5">
                    <!-- Step 1: Nominator KTA verification -->
                    <div class="bg-zinc-50/70 rounded-2xl border border-zinc-200/80 p-4 sm:p-5 space-y-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-700 tracking-wider mb-1 flex items-center justify-between">
                                <span>Langkah 1: Nomor Kartu Anda / KTA Pengusul <span class="text-red-500">*</span></span>
                                <span class="text-[10px] font-normal text-zinc-500">Ketik 4 digit KTA untuk verifikasi kelayakan</span>
                            </label>
                            <div class="relative max-w-sm">
                                <input 
                                    v-model="nominatorCardQuery" 
                                    type="text" 
                                    maxlength="4" 
                                    placeholder="KTA Anda (Contoh: 0016)" 
                                    class="f-input font-mono pr-10 text-base font-bold" 
                                    required 
                                />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <Loader2 v-if="isNominatorSearching" class="h-4 w-4 text-red-500 animate-spin" />
                                    <Search v-else class="h-4 w-4 text-zinc-400" />
                                </div>
                            </div>
                            <!-- Nominator Search Error -->
                            <p v-if="nominatorSearchError" class="text-xs text-red-600 font-semibold mt-1.5 flex items-center gap-1.5 animate-in fade-in">
                                <CircleAlert class="h-3.5 w-3.5 shrink-0" />
                                <span>{{ nominatorSearchError }}</span>
                            </p>
                        </div>

                        <!-- AUTO-COMPLETE DISPLAY FOR NOMINATOR: Show when nominator is fetched successfully -->
                        <div 
                            v-if="nominatorMemberDetails && !nominatorSearchError" 
                            class="bg-gradient-to-r from-green-50 to-emerald-50/50 rounded-xl border border-green-200/80 px-4 py-3 flex items-center gap-3 animate-in slide-in-from-left-4 duration-300"
                        >
                            <CheckCircle2 class="h-5 w-5 text-green-600 shrink-0" />
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-green-700 bg-green-100/80 px-1.5 py-0.5 rounded border border-green-200">Pengusul Terverifikasi &amp; Layak</span>
                                <h5 class="font-oswald text-sm font-bold text-zinc-900 uppercase mt-1">{{ nominatorMemberDetails.nama_lengkap }} <span class="text-xs font-mono text-zinc-500 font-normal">({{ nominatorMemberDetails.no_kartu }})</span></h5>
                                <p class="text-[10px] text-zinc-600 font-medium">Chapter: {{ nominatorMemberDetails.chapter }}<template v-if="nominatorMemberDetails.region"> | Region: {{ nominatorMemberDetails.region }}</template> | Status: {{ nominatorMemberDetails.status_keanggotaan }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Candidate Search Field (ONLY APPEARS after nominator is valid/eligible/not yet submitted) -->
                    <div 
                        v-if="nominatorMemberDetails && !nominatorSearchError" 
                        class="bg-white rounded-2xl border border-red-100 p-4 sm:p-5 space-y-4 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300"
                    >
                        <div class="relative">
                            <label class="block text-xs font-bold uppercase text-zinc-800 tracking-wider mb-1 flex items-center justify-between">
                                <span>Langkah 2: Nama Anggota Yang Diajukan (Calon Presiden) <span class="text-red-500">*</span></span>
                                <span class="text-[10px] font-normal text-zinc-500">Cari calon yang berstatus LIFE MEMBER / SS DIPONEGORO</span>
                            </label>
                            <div class="relative">
                                <input 
                                    v-model="candidateSearchQuery" 
                                    type="text" 
                                    placeholder="Cari nama, panggilan, KTA, chapter, atau checkpoint..." 
                                    class="f-input pr-10" 
                                    required 
                                    @focus="showCandidateDropdown = candidateSearchResults.length > 0"
                                />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <Loader2 v-if="isCandidateSearching" class="h-4 w-4 text-red-500 animate-spin" />
                                    <Search v-else class="h-4 w-4 text-zinc-400" />
                                </div>
                            </div>

                            <!-- Floating Autocomplete Candidate Search Dropdown -->
                            <div 
                                v-if="showCandidateDropdown && candidateSearchResults.length > 0" 
                                class="absolute z-20 w-full mt-1 bg-white border border-red-100 rounded-xl shadow-lg max-h-48 overflow-y-auto divide-y divide-zinc-50 animate-in fade-in zoom-in-95 duration-100"
                            >
                                <button
                                    v-for="m in candidateSearchResults"
                                    :key="m.id"
                                    type="button"
                                    @click="selectCandidate(m)"
                                    class="w-full text-left px-4 py-2.5 hover:bg-red-50/50 flex items-center gap-3 transition-colors"
                                >
                                    <div class="h-8 w-8 rounded-full border border-red-100 overflow-hidden bg-zinc-50 flex items-center justify-center shrink-0">
                                        <img v-if="m.foto" :src="'/storage/' + m.foto" class="h-full w-full object-cover" />
                                        <Users v-else class="h-4 w-4 text-red-500" />
                                    </div>
                                    <div>
                                        <span class="block text-sm font-semibold text-zinc-800 uppercase leading-none">
                                            {{ m.nama_lengkap }}
                                            <span v-if="m.nama_panggilan" class="text-xs text-zinc-500 font-normal">({{ m.nama_panggilan }})</span>
                                        </span>
                                        <span class="block text-[10px] text-zinc-500 mt-0.5">KTA: {{ m.no_kartu }} | Chapter: {{ m.chapter }}<template v-if="m.checkpoint"> | Checkpoint: {{ m.checkpoint }}</template><template v-if="m.region"> | Region: {{ m.region }}</template> | Status: {{ m.status_keanggotaan }}</span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- AUTO-COMPLETE DISPLAY FOR CANDIDATE: Profile Card with Centered Photo -->
                        <div 
                            v-if="candidateMemberDetails" 
                            class="bg-gradient-to-b from-red-50/80 via-white to-orange-50/30 rounded-3xl border-2 border-red-200/80 p-6 flex flex-col items-center text-center shadow-lg shadow-red-100/50 animate-in zoom-in-95 duration-300 relative overflow-hidden"
                        >
                            <span class="inline-block rounded-full bg-red-600/10 px-3 py-1 font-mono text-xs font-bold uppercase tracking-wider text-red-600 border border-red-500/20 mb-4">
                                Target Pencalonan (Calon Presiden)
                            </span>

                            <!-- Centered Circular Photo with Hover/Click Enlarge Overlay -->
                            <div 
                                class="relative group cursor-pointer" 
                                @click="candidateMemberDetails.foto && openPhotoModal(candidateMemberDetails, 'Foto Target Pencalonan')"
                            >
                                <div class="h-32 w-32 sm:h-36 sm:w-36 rounded-full border-4 border-white shadow-xl overflow-hidden bg-zinc-100 flex items-center justify-center relative ring-4 ring-red-500/20 group-hover:ring-red-500/40 transition-all duration-300 mx-auto">
                                    <img 
                                        v-if="candidateMemberDetails.foto" 
                                        :src="'/storage/' + candidateMemberDetails.foto" 
                                        class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300" 
                                        alt="Foto Target Pencalonan" 
                                    />
                                    <Users v-else class="h-14 w-14 text-red-400" />
                                </div>
                                
                                <!-- Enlarge photo badge overlay -->
                                <div v-if="candidateMemberDetails.foto" class="absolute inset-0 rounded-full bg-black/50 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center text-white transition-opacity duration-200">
                                    <Search class="h-6 w-6 mb-1" />
                                    <span class="text-[10px] font-bold uppercase tracking-wider">Perbesar</span>
                                </div>
                            </div>

                            <!-- Candidate Details -->
                            <div class="mt-4 space-y-2 w-full">
                                <h4 class="font-oswald text-xl sm:text-2xl font-bold text-zinc-950 uppercase tracking-wide">
                                    {{ candidateMemberDetails.nama_lengkap }}
                                    <span v-if="candidateMemberDetails.nama_panggilan" class="block sm:inline text-base font-normal text-zinc-500">({{ candidateMemberDetails.nama_panggilan }})</span>
                                </h4>
                                
                                <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
                                    <span class="rounded-lg bg-zinc-100 px-2.5 py-1 font-mono text-xs font-bold text-zinc-800 border border-zinc-200">
                                        KTA: {{ candidateMemberDetails.no_kartu }}
                                    </span>
                                    <span class="rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-800 border border-zinc-200">
                                        Chapter: {{ candidateMemberDetails.chapter }}
                                    </span>
                                    <span v-if="candidateMemberDetails.checkpoint" class="rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-800 border border-zinc-200">
                                        Checkpoint: {{ candidateMemberDetails.checkpoint }}
                                    </span>
                                    <span v-if="candidateMemberDetails.region" class="rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-800 border border-zinc-200">
                                        Region: {{ candidateMemberDetails.region }}
                                    </span>
                                    <span class="rounded-lg bg-red-100/80 px-2.5 py-1 text-xs font-bold text-red-700 border border-red-200">
                                        Status: {{ candidateMemberDetails.status_keanggotaan }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-3 border-t border-zinc-100">
                            <button 
                                type="submit" 
                                class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 text-xs font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all disabled:opacity-45"
                                :disabled="!candidateMemberDetails || !nominatorMemberDetails || memberForm.processing"
                            >
                                <Loader2 v-if="memberForm.processing" class="h-4 w-4 animate-spin" />
                                <Send v-else class="h-4 w-4" />
                                <span>Kirim Rekomendasi Calon</span>
                            </button>
                        </div>
                    </div>
                </form>

            </div>

          

        </main>

        <!-- Footer Segment consistent with Welcome.vue -->
        <footer class="relative z-10 w-full border-t border-red-100 bg-white/40 py-8 backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <!-- Motto Segment -->
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center gap-4 w-full max-w-2xl mb-3">
                        <div class="h-[1px] flex-grow bg-red-100"></div>
                        <span class="font-bebas text-xs sm:text-sm tracking-[0.25em] text-zinc-400 select-none">Bikers Brotherhood MC Indonesia</span>
                        <div class="h-[1px] flex-grow bg-red-100"></div>
                    </div>
                    
                    <span class="font-bebas text-xl sm:text-3xl tracking-[0.15em] text-red-600 font-bold">
                        BROTHERHOOD, LOYAL, RESPECT, HONOR, PRIDE
                    </span>
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