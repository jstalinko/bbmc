<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { 
    Users, 
    Hourglass, 
    Loader2, 
    ShieldCheck
} from 'lucide-vue-next';

const position = ref<number | null>(null);
const isPolling = ref(false);
const errorCount = ref(0);
let pollInterval: number;

const checkQueueStatus = async () => {
    if (isPolling.value) return;
    isPolling.value = true;
    
    try {
        const response = await fetch('/election/queue-status', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            errorCount.value++;
            if (errorCount.value > 5) {
                // Too many errors, might be logged out or server down
                router.visit(route('election.login'));
            }
            return;
        }

        const data = await response.json();
        
        if (data.status === 'active' && data.redirect) {
            // It's our turn! Clear interval and redirect
            clearInterval(pollInterval);
            router.visit(data.redirect);
            return;
        }
        
        if (data.status === 'expired' || data.status === 'unauthorized') {
            clearInterval(pollInterval);
            router.visit(route('election.login'), {
                onError: () => {},
                onSuccess: () => {}
            });
            return;
        }

        if (data.status === 'waiting') {
            position.value = data.position;
            errorCount.value = 0; // reset errors on success
        }
    } catch (e) {
        errorCount.value++;
    } finally {
        isPolling.value = false;
    }
};

onMounted(() => {
    // Initial check
    checkQueueStatus();
    
    // Poll every 10 seconds
    pollInterval = window.setInterval(checkQueueStatus, 10000);
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});
</script>

<template>
    <Head title="Waiting in Queue - El Presidente BBMC" />

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
            <div class="mx-auto flex max-w-7xl items-center justify-center">
                <div class="flex items-center gap-3">
                    <img src="/bbmc-logo.png" class="h-8 w-auto filter drop-shadow" alt="Logo" />
                    <span class="font-bebas text-lg tracking-wider text-red-600">BBMC ELECTION 2026</span>
                </div>
            </div>
        </header>

        <!-- Queue Section -->
        <main class="relative z-10 mx-auto flex w-full max-w-md flex-grow flex-col justify-center px-4 py-12">
            
            <div class="bg-white rounded-2xl border border-red-100 p-8 shadow-xl shadow-red-100/40 relative overflow-hidden text-center">
                
                <div class="mb-6 flex justify-center">
                    <div class="h-16 w-16 bg-red-50 rounded-full flex items-center justify-center border-4 border-red-100 animate-pulse">
                        <Hourglass class="h-8 w-8 text-red-600 animate-bounce" />
                    </div>
                </div>

                <h2 class="font-bebas text-3xl tracking-wide text-zinc-950 uppercase mb-2">
                    Anda Berada di Antrean
                </h2>
                
                <p class="text-xs text-zinc-500 mb-6 px-4 leading-relaxed">
                    Sistem saat ini sedang mencapai kapasitas maksimal. Mohon tunggu sebentar, Anda akan otomatis masuk ke Dashboard setelah giliran Anda tiba.
                </p>

                <div class="bg-gradient-to-br from-red-600 to-red-800 rounded-xl p-6 text-white shadow-lg relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 text-white/10">
                        <Users class="h-24 w-24" />
                    </div>
                    
                    <p class="text-[10px] font-bold uppercase tracking-widest text-red-200 mb-1 relative z-10">Posisi Antrean Anda</p>
                    <div class="flex items-baseline justify-center gap-2 relative z-10">
                        <span class="font-bebas text-6xl tracking-wider">
                            {{ position !== null ? position : '--' }}
                        </span>
                        <span class="text-sm font-semibold text-red-200">/ antrean</span>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-center gap-2 text-xs font-semibold text-zinc-600">
                    <Loader2 class="h-4 w-4 animate-spin text-red-600" />
                    <span>Memperbarui status secara otomatis...</span>
                </div>
            </div>

            <!-- Security Badge Info -->
            <div class="mt-6 flex justify-center">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-red-100 bg-white/60 text-[10px] text-zinc-500 font-medium">
                    <ShieldCheck class="h-3.5 w-3.5 text-red-600" />
                    <span>Sistem Antrean Pemilihan BBMC Terenkripsi</span>
                </div>
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
.font-sans {
    font-family: 'Outfit', 'Roboto', sans-serif;
}
</style>
