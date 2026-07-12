<template>
  <div class="min-h-screen flex flex-col" style="background: linear-gradient(135deg, #fff5f5 0%, #fff9f7 40%, #fef2f2 100%);">
    <!-- Top accent bar -->
    <div class="h-1.5 bg-gradient-to-r from-red-700 via-red-500 to-red-700"></div>

    <div class="flex-1 flex items-center justify-center px-4 py-10">
      <div class="w-full max-w-md">

        <!-- Header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-28 h-28 rounded-full bg-white border-4 border-red-100 shadow-xl shadow-red-100 mb-5">
            <img
              src="https://bikersbrotherhoodmc.id/wp-content/uploads/bbmc-indonesia-logo-150px.png"
              alt="BBMC Logo"
              class="w-20 h-20 object-contain"
            />
          </div>
          <h1 class="text-3xl font-black text-red-700 uppercase tracking-widest mb-1" style="font-family: Georgia, serif;">
            VALIDATE MEMBER
          </h1>
          <p class="text-gray-500 text-sm tracking-wider uppercase">Bikers Brotherhood Motorcycle Club</p>
          <div class="mt-3 h-0.5 w-32 mx-auto bg-gradient-to-r from-transparent via-red-400 to-transparent"></div>
        </div>

        <!-- Validation Form Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-red-100/80 border border-red-100 overflow-hidden">
          <div class="bg-gradient-to-r from-red-700 via-red-600 to-red-700 px-6 py-4">
            <h2 class="text-white font-bold text-base uppercase tracking-wider">
              🔍 Validasi Nomor Kartu
            </h2>
            <p class="text-red-100 text-xs mt-0.5 opacity-80">Masukkan 4 digit nomor kartu member Anda</p>
          </div>

          <div class="p-6">
            <!-- Form -->
            <form @submit.prevent="submitForm" class="space-y-4">
              <div>
                <label class="field-label">Nomor Kartu Member <span class="text-red-500">*</span></label>
                <div
                  class="flex items-center gap-0 rounded-lg overflow-hidden transition-all duration-200 border-2"
                  :class="form.errors.no_kartu ? 'border-red-400 ring-2 ring-red-100' : 'border-gray-200 focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100'"
                >
                  <span class="bg-gray-50 px-3 py-2.5 text-sm font-mono font-bold text-gray-500 border-r border-gray-200 whitespace-nowrap select-none">BBMC 38 2026</span>
                  <input
                    v-model="form.no_kartu"
                    type="text"
                    maxlength="4"
                    inputmode="numeric"
                    placeholder="0000"
                    @input="handleNoKartuInput"
                    class="flex-1 min-w-0 bg-white px-3 py-2.5 text-sm font-mono font-bold text-gray-700 placeholder-gray-300 outline-none tracking-widest text-center sm:text-left"
                  />
                </div>
                <p v-if="form.errors.no_kartu" class="field-error">{{ form.errors.no_kartu }}</p>
              </div>

              <!-- Submit Button -->
              <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 px-7 py-3 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all duration-200 active:scale-95"
              >
                <svg v-if="form.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ form.processing ? 'Memeriksa...' : 'Periksa Anggota ✓' }}
              </button>
            </form>
          </div>
        </div>

        <!-- Penalty Member Result Card -->
        <div
          v-if="props.penalty_member"
          class="mt-6 rounded-2xl border-2 overflow-hidden shadow-xl transition-all duration-300 animate-in fade-in slide-in-from-top-4"
          :class="penaltyCardBorderClass(props.penalty_member.penalty)"
        >
          <!-- Header banner -->
          <div
            class="px-6 py-4 flex items-center justify-between text-white"
            :class="penaltyCardHeaderClass(props.penalty_member.penalty)"
          >
            <div class="flex items-center gap-3">
              <span class="text-2xl">{{ penaltyIcon(props.penalty_member.penalty) }}</span>
              <div>
                <h3 class="font-black text-sm uppercase tracking-wider">
                  STATUS PENALTY: {{ formatPenalty(props.penalty_member.penalty) }}
                </h3>
                <p class="text-xs opacity-90">
                  Hasil Validasi Kartu BBMC 38 2026 {{ props.penalty_member.no_kartu }}
                </p>
              </div>
            </div>
            <span
              class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest bg-white/20 border border-white/30"
            >
              {{ props.penalty_member.penalty }}
            </span>
          </div>

          <!-- Body -->
          <div class="bg-white p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
              <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Nama Anggota</div>
                <div class="text-base font-black text-gray-800 uppercase" style="font-family: Georgia, serif;">
                  {{ props.penalty_member.nama_lengkap }}
                </div>
              </div>
              <div class="text-right">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">No. Kartu</div>
                <div class="text-sm font-mono font-bold text-gray-700">
                  BBMC 38 2026 {{ props.penalty_member.no_kartu }}
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-xs">
              <div v-if="props.penalty_member.status_keanggotaan">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Status Keanggotaan</div>
                <div class="font-semibold text-gray-700 mt-0.5">{{ props.penalty_member.status_keanggotaan }}</div>
              </div>
              <div v-if="props.penalty_member.chapter">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Chapter</div>
                <div class="font-semibold text-gray-700 mt-0.5">{{ props.penalty_member.chapter }}</div>
              </div>
            </div>

            <!-- Alasan Penalty -->
            <div
              class="rounded-xl p-4 border"
              :class="penaltyReasonBoxClass(props.penalty_member.penalty)"
            >
              <div class="text-xs font-bold uppercase tracking-wider mb-1 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Alasan / Keterangan Penalty
              </div>
              <p class="text-sm font-medium leading-relaxed">
                {{ props.penalty_member.penalty_reason || 'Tidak ada keterangan tambahan yang dicantumkan.' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Back to Registration -->
        <div class="text-center mt-6">
          <a
            href="/member/register"
            class="inline-flex items-center gap-2 text-red-600 hover:text-red-800 text-sm font-semibold transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Daftar sebagai anggota BBMC
          </a>
        </div>

      </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-5 border-t border-red-100 mt-auto">
      <p class="text-gray-400 text-xs tracking-wide">
        Copyright © 2026 <span class="text-red-500 font-semibold">BBMC</span> | Bikers Brotherhood Motorcycle Club - Indonesia.
      </p>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

// Inject standard Ziggy route helper globally/locally
declare function route(name: string, params?: any): string

const props = defineProps<{
  penalty_member?: {
    nama_lengkap: string
    no_kartu: string
    penalty: string
    penalty_reason?: string
    status_keanggotaan?: string
    chapter?: string
  } | null
}>()

const form = useForm({
  no_kartu: '',
})

function handleNoKartuInput(e: Event) {
  const input = e.target as HTMLInputElement
  form.no_kartu = input.value.replace(/\D/g, '').slice(0, 4)
  input.value = form.no_kartu
  if (form.errors.no_kartu) {
    delete form.errors.no_kartu
  }
}

function submitForm() {
  form.get(route('member.validate'), {
    preserveState: true,
  })
}

function formatPenalty(p?: string) {
  if (!p || p === 'clean') return 'Clean'
  return p.charAt(0).toUpperCase() + p.slice(1)
}

function penaltyIcon(p?: string) {
  if (p === 'warning') return '⚠️'
  if (p === 'blacklist') return '🚫'
  if (p === 'banned') return '❌'
  return 'ℹ️'
}

function penaltyCardBorderClass(p?: string) {
  if (p === 'warning') return 'border-amber-300 shadow-amber-100'
  if (p === 'blacklist') return 'border-red-400 shadow-red-100'
  if (p === 'banned') return 'border-purple-400 shadow-purple-100'
  return 'border-gray-200'
}

function penaltyCardHeaderClass(p?: string) {
  if (p === 'warning') return 'bg-gradient-to-r from-amber-600 via-amber-500 to-amber-600'
  if (p === 'blacklist') return 'bg-gradient-to-r from-red-700 via-red-600 to-red-700'
  if (p === 'banned') return 'bg-gradient-to-r from-purple-800 via-purple-700 to-purple-800'
  return 'bg-gradient-to-r from-gray-700 to-gray-600'
}

function penaltyReasonBoxClass(p?: string) {
  if (p === 'warning') return 'bg-amber-50 border-amber-200 text-amber-900'
  if (p === 'blacklist') return 'bg-red-50 border-red-200 text-red-900'
  if (p === 'banned') return 'bg-purple-50 border-purple-200 text-purple-900'
  return 'bg-gray-50 border-gray-200 text-gray-800'
}
</script>

<style scoped>
.field-label {
  @apply block text-gray-600 text-xs font-semibold uppercase tracking-wider mb-1.5;
}
.field-error {
  @apply text-red-500 text-xs mt-1 flex items-center gap-1;
}
.field-error::before {
  content: '⚠ ';
}
</style>
