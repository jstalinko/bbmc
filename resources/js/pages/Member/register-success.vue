<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Pendaftaran Berhasil - BBMC" />

  <div class="min-h-screen flex flex-col" style="background: linear-gradient(135deg, #fff5f5 0%, #fff9f7 40%, #fef2f2 100%);">
    <!-- Top accent bar -->
    <div class="h-1.5 bg-gradient-to-r from-red-700 via-red-500 to-red-700"></div>

    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center px-4 py-10">
      <div class="w-full max-w-lg">

        <!-- Header (sama dengan register.vue) -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-28 h-28 rounded-full bg-white border-4 border-red-100 shadow-xl shadow-red-100 mb-5">
            <img
              src="https://bikersbrotherhoodmc.id/wp-content/uploads/bbmc-indonesia-logo-150px.png"
              alt="BBMC Logo"
              class="w-20 h-20 object-contain"
            />
          </div>
          <h1 class="text-3xl font-black text-red-700 uppercase tracking-widest mb-1" style="font-family: Georgia, serif;">
            BBMC REGISTRATION
          </h1>
          <p class="text-gray-500 text-sm tracking-wider uppercase">Bikers Brotherhood Motorcycle Club</p>
          <div class="mt-3 h-0.5 w-32 mx-auto bg-gradient-to-r from-transparent via-red-400 to-transparent"></div>
        </div>

        <!-- Success Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-red-100/80 border border-red-100 overflow-hidden text-center">

          <!-- Card Top Banner -->
          <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 mb-3">
              <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h2 class="text-white text-xl font-bold uppercase tracking-wider">Pendaftaran Berhasil!</h2>
            <p class="text-red-100 text-sm mt-1 opacity-90">Data Anda telah berhasil dikirimkan</p>
          </div>

          <!-- Card Body -->
          <div class="px-8 py-8 space-y-5">
            <p class="text-gray-600 text-sm leading-relaxed">
              Terima kasih telah mendaftar sebagai anggota
              <span class="font-bold text-red-600">BBMC — Bikers Brotherhood Motorcycle Club Indonesia</span>.
              Pengurus kami akan segera meninjau data pendaftaran Anda.
            </p>

            <!-- Info Steps -->
           

            <div class="h-px bg-red-100"></div>

           
          </div>
        </div>

      </div>
    </div>

    <!-- Footer (sama dengan register.vue) -->
    <footer class="text-center py-5 border-t border-red-100">
      <p class="text-gray-400 text-xs tracking-wide">
        Copyright © 2026 <span class="text-red-500 font-semibold">BBMC</span> | Bikers Brotherhood Motorcycle Club - Indonesia.
      </p>
    </footer>
  </div>
</template>