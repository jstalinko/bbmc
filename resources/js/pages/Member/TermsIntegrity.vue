<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

function handleClose() {
  if (window.opener || window.history.length === 1) {
    window.close()
  } else {
    window.location.href = '/member/register'
  }
}
</script>

<template>
  <Head title="Syarat Ketentuan & Kebijakan Privasi - BBMC" />

  <div class="min-h-screen flex flex-col" style="background: linear-gradient(135deg, #fff5f5 0%, #fff9f7 40%, #fef2f2 100%);">
    <!-- Top accent bar -->
    <div class="h-1.5 bg-gradient-to-r from-red-700 via-red-500 to-red-700"></div>

    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center px-4 py-10">
      <div class="w-full max-w-2xl">

        <!-- Header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-white border-4 border-red-100 shadow-xl shadow-red-100 mb-4">
            <img
              src="https://bikersbrotherhoodmc.id/wp-content/uploads/bbmc-indonesia-logo-150px.png"
              alt="BBMC Logo"
              class="w-16 h-16 object-contain"
            />
          </div>
          <h1 class="text-2xl font-black text-red-700 uppercase tracking-widest mb-1" style="font-family: Georgia, serif;">
            SYARAT KETENTUAN & KEBIJAKAN PRIVASI
          </h1>
          <p class="text-gray-500 text-xs tracking-wider uppercase">Bikers Brotherhood Motorcycle Club Indonesia</p>
          <div class="mt-3 h-0.5 w-32 mx-auto bg-gradient-to-r from-transparent via-red-400 to-transparent"></div>
        </div>

        <!-- Content Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-red-100/80 border border-red-100 overflow-hidden">
          
          <div class="p-6 sm:p-8 space-y-6">
            
            <!-- Syarat & Ketentuan -->
            <section class="space-y-3">
              <h2 class="text-red-700 text-lg font-bold uppercase tracking-wider flex items-center gap-2 border-b border-red-100 pb-2">
                <span>📋</span> Syarat & Ketentuan Anggota
              </h2>
              <p class="text-gray-600 text-sm font-semibold italic">
                Menyatakan Sebagai Berikut:
              </p>
              <ul class="space-y-3.5 text-gray-600 text-sm">
                <li class="flex gap-2.5 items-start">
                  <span class="text-red-600 font-bold min-w-[1.25rem]">1.</span>
                  <span>Tunduk dan patuh terhadap 5 asas hukum adat dan segala ketentuan yang tercantum dalam buku AD ART BBMC.</span>
                </li>
                <li class="flex gap-2.5 items-start">
                  <span class="text-red-600 font-bold min-w-[1.25rem]">2.</span>
                  <span>Setia, taat dan menjaga lambang organisasi, Lembaga - lembaga BBMC.</span>
                </li>
                <li class="flex gap-2.5 items-start">
                  <span class="text-red-600 font-bold min-w-[1.25rem]">3.</span>
                  <span>Menjaga kehormatan dan menjunjung tinggi nama baik organisasi dan individu BBMC.</span>
                </li>
                <li class="flex gap-2.5 items-start">
                  <span class="text-red-600 font-bold min-w-[1.25rem]">4.</span>
                  <span>Turut serta menjaga stabilitas keamanan, ketentraman, keutuhan organisasi BBMC.</span>
                </li>
                <li class="flex gap-2.5 items-start">
                  <span class="text-red-600 font-bold min-w-[1.25rem]">5.</span>
                  <span>Memelihara kebersamaan, kesetiakawanan sesama anggota BBMC.</span>
                </li>
              </ul>
            </section>

            <!-- Kebijakan Privasi -->
            <section class="space-y-3">
              <h2 class="text-red-700 text-lg font-bold uppercase tracking-wider flex items-center gap-2 border-b border-red-100 pb-2">
                <span>🔒</span> Kebijakan Privasi Data Member
              </h2>
              <p class="text-gray-600 text-sm">
                Bikers Brotherhood MC Indonesia menghargai privasi data pribadi Anda. Kebijakan ini menjelaskan bagaimana kami mengelola dan melindungi data keanggotaan Anda:
              </p>
              <ul class="space-y-3.5 text-gray-600 text-sm">
                <li class="flex gap-2.5 items-start">
                  <span class="text-red-600 font-bold min-w-[1.25rem]">1.</span>
                  <span><strong>Pengumpulan Data:</strong> Kami mengumpulkan informasi pribadi seperti nama lengkap, nama panggilan, tempat & tanggal lahir, jenis kelamin, golongan darah, alamat, nomor WhatsApp, email, profesi, dan foto diri yang diunggah melalui formulir registrasi.</span>
                </li>
                <li class="flex gap-2.5 items-start">
                  <span class="text-red-600 font-bold min-w-[1.25rem]">2.</span>
                  <span><strong>Penggunaan Informasi:</strong> Informasi ini digunakan secara eksklusif untuk kepentingan administratif organisasi, pencatatan data keanggotaan di masing-masing Chapter/Checkpoint, verifikasi identitas, pencetakan Kartu Tanda Anggota (KTA), serta pemilu internal.</span>
                </li>
                <li class="flex gap-2.5 items-start">
                  <span class="text-red-600 font-bold min-w-[1.25rem]">3.</span>
                  <span><strong>Perlindungan Data:</strong> Kami menerapkan standar keamanan terbaik untuk mencegah kehilangan data, penyalahgunaan, atau akses tidak sah. Kami menjamin data Anda tidak akan dibagikan, dipindahtangankan, atau dijual kepada pihak ketiga di luar organisasi tanpa persetujuan Anda.</span>
                </li>
                <li class="flex gap-2.5 items-start">
                  <span class="text-red-600 font-bold min-w-[1.25rem]">4.</span>
                  <span><strong>Pembaruan Data:</strong> Setiap anggota berhak memverifikasi, mengubah, atau memperbarui informasi pribadi mereka dengan mengajukan permohonan melalui administrator atau pengurus sekretariat BBMC terkait.</span>
                </li>
              </ul>
            </section>

            <div class="h-px bg-red-50 my-6"></div>

            <!-- Back/Close Button -->
            <div class="flex justify-center">
              <button
                @click="handleClose"
                class="px-8 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all duration-200 active:scale-95"
              >
                Kembali / Tutup Halaman
              </button>
            </div>

          </div>
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
