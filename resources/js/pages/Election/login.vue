<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    ShieldCheck, 
    KeyRound, 
    User, 
    Loader2, 
    ArrowLeft, 
    Send,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps<{
    errors?: Record<string, string>;
    flash?: {
        success?: boolean;
        message?: string;
    };
}>();

// Form state
const noKartu = ref('');
const otpCode = ref('');
const step = ref<'nocard' | 'otp'>('nocard');

// Loading states
const isSendingOtp = ref(false);
const otpSentMessage = ref('');
const searchError = ref('');

const form = useForm({
    no_kartu: '',
    otp: '',
});

const handleSendOtp = async () => {
    if (!noKartu.value || noKartu.value.length < 2) {
        searchError.value = 'Masukkan nomor KTA yang valid.';
        return;
    }
    
    isSendingOtp.value = true;
    searchError.value = '';
    
    try {
        const response = await fetch('/api/send-login-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ no_kartu: noKartu.value })
        });
        const data = await response.json();
        if (response.ok && data.success) {
            form.no_kartu = noKartu.value;
            otpSentMessage.value = data.message;
            step.value = 'otp';
        } else {
            searchError.value = data.message || 'Gagal mengirim OTP.';
        }
    } catch (e) {
        searchError.value = 'Gagal menghubungi server.';
    } finally {
        isSendingOtp.value = false;
    }
};

const handleLogin = () => {
    form.otp = otpCode.value;
    form.post(route('election.login_post'), {
        onError: (err) => {
            // Errors will be set automatically on form.errors
        }
    });
};

const goBackToNocard = () => {
    step.value = 'nocard';
    otpCode.value = '';
    form.errors.otp = '';
};

</script>

<template>
    <Head title="Login - El Presidente Pra-Election Portal" />

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
                    <ArrowLeft class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                    <span class="text-xs font-bold uppercase tracking-wider">Kembali ke Portal</span>
                </Link>
                
                <div class="flex items-center gap-3">
                    <img src="/bbmc-logo.png" class="h-8 w-auto filter drop-shadow" alt="Logo" />
                    <span class="font-bebas text-lg tracking-wider text-red-600">BBMC ELECTION 2026</span>
                </div>
            </div>
        </header>

        <!-- Login Card Section -->
        <main class="relative z-10 mx-auto flex w-full max-w-md flex-grow flex-col justify-center px-4 py-12">
            
            <div class="bg-white rounded-2xl border border-red-100 p-8 shadow-xl shadow-red-100/40 relative overflow-hidden">
                <!-- Branding Header inside the card -->
                <div class="text-center mb-8">
                    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full border border-red-100 bg-red-50/50 px-3 py-1 text-xs uppercase tracking-wider text-red-600 shadow-sm font-semibold">
                        <ShieldCheck class="h-4 w-4" />
                        <span>Voter Verification</span>
                    </div>
                    <h2 class="font-bebas text-3xl tracking-wide text-zinc-950 uppercase">
                        Login Pra-Election
                    </h2>
                    <p class="text-xs text-zinc-500 mt-1">
                        Akses khusus untuk Anggota Terverifikasi BBMC Indonesia
                    </p>
                    <div class="mt-4 h-[2px] w-16 mx-auto bg-gradient-to-r from-transparent via-red-500 to-transparent"></div>
                </div>

                <!-- Session Flash Message -->
                <div v-if="$page.props.flash?.message" class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-xs font-semibold text-green-700 flex items-start gap-2">
                    <ShieldCheck class="h-4 w-4 text-green-600 shrink-0" />
                    <span>{{ $page.props.flash.message }}</span>
                </div>

                <!-- Form Step 1: No Kartu (KTA) -->
                <div v-if="step === 'nocard'" class="space-y-4">
                    <form @submit.prevent="handleSendOtp" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-2">
                                Masukkan Nomor Kartu Anda <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                                    <User class="h-4 w-4" />
                                </span>
                                <input 
                                    v-model="noKartu" 
                                    type="text" 
                                    placeholder="Contoh: 0023 atau 23" 
                                    class="f-input pl-10 font-mono" 
                                    required 
                                    autocomplete="off"
                                />
                            </div>
                            
                            <!-- Search Error Warning -->
                            <p v-if="searchError || form.errors.no_kartu" class="text-xs text-red-600 font-semibold mt-2 flex items-center gap-1">
                                <AlertCircle class="h-4 w-4 shrink-0" />
                                <span>{{ searchError || form.errors.no_kartu }}</span>
                            </p>
                        </div>

                        <button 
                            type="submit" 
                            class="w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white py-3 text-xs font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all disabled:opacity-50"
                            :disabled="isSendingOtp"
                        >
                            <Loader2 v-if="isSendingOtp" class="h-4 w-4 animate-spin" />
                            <Send v-else class="h-4 w-4" />
                            <span>Kirim OTP via WhatsApp</span>
                        </button>
                    </form>
                </div>

                <!-- Form Step 2: OTP Verification -->
                <div v-else class="space-y-4">
                    <div class="p-3 bg-red-50/50 border border-red-100 rounded-xl text-xs text-zinc-600 flex items-start gap-2">
                        <ShieldCheck class="h-4.5 w-4.5 text-red-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="font-bold text-red-700 uppercase tracking-wide">OTP Terkirim!</p>
                            <p class="mt-0.5 leading-relaxed">{{ otpSentMessage }}</p>
                        </div>
                    </div>

                    <form @submit.prevent="handleLogin" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-zinc-500 tracking-wider mb-2">
                                Kode OTP <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                                    <KeyRound class="h-4 w-4" />
                                </span>
                                <input 
                                    v-model="otpCode" 
                                    type="text" 
                                    maxlength="6"
                                    placeholder="Masukkan 6 digit kode OTP" 
                                    class="f-input pl-10 font-mono tracking-widest text-center text-lg animate-pulse" 
                                    required 
                                    autocomplete="one-time-code"
                                />
                            </div>
                            
                            <!-- Search Error Warning -->
                            <p v-if="form.errors.otp" class="text-xs text-red-600 font-semibold mt-2 flex items-center gap-1">
                                <AlertCircle class="h-4 w-4 shrink-0" />
                                <span>{{ form.errors.otp }}</span>
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="goBackToNocard"
                                class="w-1/3 rounded-xl border border-zinc-200 hover:bg-zinc-50 text-zinc-600 py-3 text-xs font-bold uppercase tracking-wider transition-all"
                            >
                                Kembali
                            </button>
                            <button 
                                type="submit" 
                                class="w-2/3 flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white py-3 text-xs font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                <ShieldCheck v-else class="h-4 w-4" />
                                <span>Masuk Portal</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Badge Info -->
            <div class="mt-6 text-center text-[10px] text-zinc-500 leading-relaxed max-w-xs mx-auto">
                Setiap aktivitas login dan verifikasi dicatat secara aman dalam sistem Dewan Adat BBMC Indonesia untuk menjaga integritas Pra-Election.
            </div>

        </main>

        <!-- Footer Segment -->
        <footer class="relative z-10 w-full border-t border-red-100 bg-white/40 py-6 backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 text-center">
                <span class="font-bebas text-xs tracking-[0.25em] text-zinc-400 select-none">Bikers Brotherhood MC Indonesia</span>
                <p class="text-[10px] text-zinc-500 mt-2">
                    &copy; {{ new Date().getFullYear() }} BBMC IT Division. All Rights Reserved.
                </p>
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
    @apply w-full bg-white border border-red-100 focus:border-red-500 text-zinc-800 placeholder-zinc-400 rounded-xl px-4 py-3 text-sm outline-none transition-all duration-200 focus:ring-2 focus:ring-red-100;
}
</style>