<template>
  <div class="min-h-screen flex flex-col" style="background: linear-gradient(135deg, #fff5f5 0%, #fff9f7 40%, #fef2f2 100%);">
    <!-- Top accent bar -->
    <div class="h-1.5 bg-gradient-to-r from-red-700 via-red-500 to-red-700"></div>

    <div class="flex-1 flex items-center justify-center px-4 py-10">
      <div class="w-full max-w-lg">

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
            BBMC MEMBER
          </h1>
          <p class="text-gray-500 text-sm tracking-wider uppercase">Bikers Brotherhood Motorcycle Club</p>
          <div class="mt-3 h-0.5 w-32 mx-auto bg-gradient-to-r from-transparent via-red-400 to-transparent"></div>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-red-100/80 border border-red-100 overflow-hidden">

          <!-- Card top band with gradient -->
          <div class="bg-gradient-to-r from-red-700 via-red-600 to-red-700 px-6 py-5 relative overflow-hidden">
            <!-- Diagonal texture overlay -->
            <div class="absolute inset-0 opacity-10" style="background-image: repeating-linear-gradient(-45deg, #fff 0px, #fff 1px, transparent 1px, transparent 8px);"></div>

            <div class="relative flex items-center gap-4">
              <!-- Avatar / Foto -->
              <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-white/40 shadow-lg bg-red-800 flex items-center justify-center flex-shrink-0">
                <img
                  v-if="member.foto"
                  :src="`/storage/${member.foto}`"
                  :alt="member.nama_lengkap"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-2xl font-black text-white" style="font-family: Georgia, serif;">
                  {{ member.nama_lengkap.charAt(0).toUpperCase() }}
                </span>
              </div>

              <div class="flex-1 min-w-0">
                <div class="text-white font-black text-lg uppercase tracking-wide leading-tight truncate" style="font-family: Georgia, serif;">
                  {{ member.nama_lengkap }}
                </div>
                <div class="text-red-200 text-sm italic mt-0.5">"{{ member.nama_panggilan }}"</div>
                <!-- Status badge -->
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                  <span :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border', statusClass]">
                    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
                    {{ member.status_keanggotaan }}
                  </span>
                  <span
                    v-if="member.penalty && member.penalty !== 'clean'"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-500 text-white shadow-sm"
                  >
                    ⚠️ Penalty: {{ member.penalty }}
                  </span>
                </div>
              </div>

              <!-- No Kartu -->
              <div class="text-right flex-shrink-0">
                <div class="text-red-200 text-[9px] uppercase tracking-widest font-semibold">No. Kartu</div>
                <div class="text-white font-black text-base font-mono tracking-widest">BBMC 38 2026 {{ member.no_kartu }}</div>
              </div>
            </div>
          </div>

          <!-- Info rows -->
          <div class="divide-y divide-red-50">

            <div class="grid grid-cols-2 gap-0 divide-x divide-red-50">
              <div class="px-5 py-3.5">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Chapter</div>
                <div class="text-sm font-semibold text-gray-800">{{ member.chapter }}</div>
              </div>
              <div class="px-5 py-3.5" v-if="member.checkpoint">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Checkpoint</div>
                <div class="text-sm font-semibold text-gray-800">
                  {{ member.checkpoint }}
                  <span v-if="member.region" class="text-gray-400 font-normal">— {{ member.region }}</span>
                </div>
              </div>
              <div class="px-5 py-3.5" v-else>
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Status</div>
                <div class="text-sm font-semibold text-gray-800">{{ member.status_keanggotaan }}</div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-0 divide-x divide-red-50">
              <div class="px-5 py-3.5">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Tempat, Tanggal Lahir</div>
                <div class="text-sm font-semibold text-gray-800">{{ member.tempat_lahir }}, {{ member.tanggal_lahir }}</div>
              </div>
              <div class="px-5 py-3.5">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Jenis Kelamin</div>
                <div class="text-sm font-semibold text-gray-800">{{ member.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-0 divide-x divide-red-50">
              <div class="px-5 py-3.5">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Golongan Darah</div>
                <div class="text-sm font-bold text-red-600 font-mono text-base">{{ member.gol_darah }}</div>
              </div>
              <div class="px-5 py-3.5" v-if="member.profesi">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Profesi</div>
                <div class="text-sm font-semibold text-gray-800">{{ member.profesi }}</div>
              </div>
              <div class="px-5 py-3.5" v-else-if="member.terdaftar_sejak">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Terdaftar Sejak</div>
                <div class="text-sm font-semibold text-gray-800">{{ member.terdaftar_sejak }}</div>
              </div>
            </div>

            <!-- No WA -->
            <div class="px-5 py-3.5 flex items-center justify-between gap-3">
              <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">No. WhatsApp</div>
                <div class="text-sm font-mono font-semibold text-gray-800">{{ member.no_wa }}</div>
              </div>
              <a
                :href="`https://wa.me/${member.no_wa}`"
                target="_blank"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-500 hover:bg-green-600 text-white text-xs font-bold transition-all duration-200 active:scale-95 shadow-sm"
              >
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                Chat WA
              </a>
            </div>

            <!-- Profesi & Terdaftar (jika kedua-duanya ada) -->
            <div v-if="member.profesi && member.terdaftar_sejak" class="grid grid-cols-2 gap-0 divide-x divide-red-50">
              <div class="px-5 py-3.5">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Profesi</div>
                <div class="text-sm font-semibold text-gray-800">{{ member.profesi }}</div>
              </div>
              <div class="px-5 py-3.5">
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-0.5">Terdaftar Sejak</div>
                <div class="text-sm font-semibold text-gray-800">{{ member.terdaftar_sejak }}</div>
              </div>
            </div>

            <!-- Penalty Alert Row -->
            <div v-if="member.penalty && member.penalty !== 'clean'" class="px-5 py-4 bg-red-50/90 border-t border-red-100">
              <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-red-700 mb-1">
                <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Status Penalty: <span class="uppercase font-black">{{ member.penalty }}</span>
              </div>
              <div class="text-sm text-red-800 font-medium">
                {{ member.penalty_reason || 'Anggota memiliki status penalty aktif.' }}
              </div>
            </div>

          </div>

          <!-- Card number footer -->
          <div class="bg-gradient-to-r from-red-700 via-red-600 to-red-700 px-6 py-3 flex items-center justify-between">
            <span class="text-red-200 text-xs font-semibold tracking-wider uppercase">Registration Card</span>
            <span class="text-white font-black font-mono text-sm tracking-widest">BBMC 38 2026 {{ member.no_kartu }}</span>
          </div>

        </div>

        <!-- Back button -->
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
    <footer class="text-center py-5 border-t border-red-100">
      <p class="text-gray-400 text-xs tracking-wide">
        Copyright © 2026 <span class="text-red-500 font-semibold">BBMC</span> | Bikers Brotherhood Motorcycle Club - Indonesia.
      </p>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  member: {
    nama_lengkap: string
    nama_panggilan: string
    tempat_lahir: string
    tanggal_lahir: string
    jenis_kelamin: string
    gol_darah: string
    alamat: string
    no_wa: string
    email?: string
    profesi?: string
    foto?: string
    no_kartu?: string
    status_keanggotaan: string
    chapter: string
    checkpoint?: string
    region?: string
    terdaftar_sejak?: string
  }
}>()

const statusClass = computed(() => {
  const map: Record<string, string> = {
    'SS DIPONEGORO': 'bg-purple-50 text-purple-700 border-purple-200',
    'LIFE MEMBER':   'bg-blue-50   text-blue-700   border-blue-200',
    'HONORARY':      'bg-amber-50  text-amber-700  border-amber-200',
    'VIRGIN':        'bg-green-50  text-green-700  border-green-200',
    'PROSPECT':      'bg-red-50    text-red-700    border-red-200',
  }
  return map[props.member.status_keanggotaan] ?? 'bg-red-50 text-red-700 border-red-200'
})
</script>