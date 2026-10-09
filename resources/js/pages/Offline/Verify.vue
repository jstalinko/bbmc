<template>
  <div class="min-h-screen flex flex-col" style="background: linear-gradient(135deg, #fff5f5 0%, #fff9f7 40%, #fef2f2 100%);">
    <!-- Top accent bar -->
    <div class="h-1.5 bg-gradient-to-r from-red-700 via-red-500 to-red-700"></div>

    <div class="flex-1 flex flex-col items-center justify-start px-4 py-8">
      <div class="w-full" :class="props.current_pengurus ? 'max-w-5xl' : 'max-w-md'">

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
            VERIFIKASI PEMILIH OFFLINE
          </h1>
          <p class="text-gray-500 text-sm tracking-wider uppercase">Bikers Brotherhood Motorcycle Club</p>
          <div class="mt-3 h-0.5 w-32 mx-auto bg-gradient-to-r from-transparent via-red-400 to-transparent"></div>
        </div>

        <!-- STATE 1: PENGURUS ACCESS CODE GATE (NOT LOGGED IN) -->
        <div v-if="!props.current_pengurus" class="w-full max-w-md mx-auto">
          <div class="bg-white rounded-2xl shadow-xl shadow-red-100/80 border border-red-100 overflow-hidden">
            <div class="bg-gradient-to-r from-red-700 via-red-600 to-red-700 px-6 py-4">
              <h2 class="text-white font-bold text-base uppercase tracking-wider flex items-center gap-2">
                <span>🔐</span> Akses Khusus Pengurus
              </h2>
              <p class="text-red-100 text-xs mt-0.5 opacity-80">Masukkan kode akses pengurus untuk verifikasi pemilih offline</p>
            </div>

            <div class="p-6">
              <form @submit.prevent="submitLogin" class="space-y-4">
                <div>
                  <label class="field-label">Kode Akses Pengurus <span class="text-red-500">*</span></label>
                  <div
                    class="flex items-center rounded-lg overflow-hidden transition-all duration-200 border-2"
                    :class="loginForm.errors.kode_akses ? 'border-red-400 ring-2 ring-red-100' : 'border-gray-200 focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100'"
                  >
                    <span class="bg-gray-50 px-3 py-2.5 text-sm font-mono font-bold text-gray-500 border-r border-gray-200 select-none">
                      🔑
                    </span>
                    <input
                      v-model="loginForm.kode_akses"
                      type="text"
                      placeholder="Masukkan Kode Akses"
                      class="flex-1 min-w-0 bg-white px-3 py-2.5 text-sm font-mono font-bold text-gray-700 uppercase placeholder-gray-300 outline-none tracking-wider"
                    />
                  </div>
                  <p v-if="loginForm.errors.kode_akses" class="field-error">{{ loginForm.errors.kode_akses }}</p>
                </div>

                <button
                  type="submit"
                  :disabled="loginForm.processing"
                  class="w-full flex items-center justify-center gap-2 px-7 py-3 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all duration-200 active:scale-95"
                >
                  <svg v-if="loginForm.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  {{ loginForm.processing ? 'Memverifikasi...' : 'Masuk Sebagai Pengurus →' }}
                </button>
              </form>
            </div>
          </div>

          <div class="mt-4 text-center">
            <p class="text-xs text-gray-400">
              Kode akses diatur oleh panitia di Dashboard > Setting Pemilihan.
            </p>
          </div>
        </div>

        <!-- STATE 2: PENGURUS IS AUTHENTICATED -->
        <div v-else class="space-y-6">

          <!-- Pengurus Identity & Session Bar -->
          <div class="bg-white rounded-xl border border-red-100 shadow-sm p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-red-100 text-red-700 flex items-center justify-center font-bold text-lg">
                👤
              </div>
              <div>
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Petugas Verifikasi:</div>
                <div class="text-base font-bold text-gray-800 flex items-center gap-2">
                  <span>{{ props.current_pengurus.nama_pengurus }}</span>
                  <span class="text-xs bg-red-50 text-red-600 font-mono font-semibold px-2 py-0.5 rounded border border-red-200">
                    KODE: {{ props.current_pengurus.kode_akses }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <button
                @click="refreshQueue"
                type="button"
                class="px-3.5 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-semibold transition-colors flex items-center gap-1.5"
                title="Refresh Daftar Antrean"
              >
                <span>🔄</span>
                <span>Refresh Antrean</span>
              </button>
              <button
                @click="submitLogout"
                type="button"
                class="px-3.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold transition-colors flex items-center gap-1.5"
              >
                <span>🚪</span>
                <span>Ganti Pengurus</span>
              </button>
            </div>
          </div>

          <!-- Main Verification Form (Matching /member/validate design) -->
          <div class="max-w-xl mx-auto w-full">
            <div class="bg-white rounded-2xl shadow-xl shadow-red-100/80 border border-red-100 overflow-hidden">
              <div class="bg-gradient-to-r from-red-700 via-red-600 to-red-700 px-6 py-4">
                <h2 class="text-white font-bold text-base uppercase tracking-wider flex items-center gap-2">
                  <span>🔍</span> Validasi & Ambil Antrean Offline
                </h2>
                <p class="text-red-100 text-xs mt-0.5 opacity-80">Masukkan 4 digit nomor kartu anggota yang hadir di TPS</p>
              </div>

              <div class="p-6">
                <form @submit.prevent="submitVerify" class="space-y-4">
                  <!-- No. Kartu -->
                  <div>
                    <label class="field-label">Nomor Kartu Member <span class="text-red-500">*</span></label>
                    <div
                      class="flex items-center gap-0 rounded-lg overflow-hidden transition-all duration-200 border-2"
                      :class="verifyForm.errors.no_kartu ? 'border-red-400 ring-2 ring-red-100' : 'border-gray-200 focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100'"
                    >
                      <span class="bg-gray-50 px-3 py-2.5 text-sm font-mono font-bold text-gray-500 border-r border-gray-200 whitespace-nowrap select-none">
                        BBMC 38 2026
                      </span>
                      <input
                        v-model="verifyForm.no_kartu"
                        type="text"
                        maxlength="4"
                        inputmode="numeric"
                        placeholder="0000"
                        @input="handleNoKartuInput"
                        class="flex-1 min-w-0 bg-white px-3 py-2.5 text-sm font-mono font-bold text-gray-700 placeholder-gray-300 outline-none tracking-widest text-center sm:text-left"
                      />
                    </div>
                    <p v-if="verifyForm.errors.no_kartu" class="field-error">{{ verifyForm.errors.no_kartu }}</p>
                  </div>

                  <!-- Nomor Antrean (Input Manual) -->
                  <div>
                    <label class="field-label">Nomor Antrean <span class="text-red-500">*</span></label>
                    <div
                      class="flex items-center rounded-lg overflow-hidden transition-all duration-200 border-2"
                      :class="verifyForm.errors.no_antrian ? 'border-red-400 ring-2 ring-red-100' : 'border-gray-200 focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100'"
                    >
                      <span class="bg-gray-50 px-3.5 py-2.5 text-sm font-mono font-bold text-gray-500 border-r border-gray-200 whitespace-nowrap select-none">
                        ANTREAN
                      </span>
                      <input
                        v-model="verifyForm.no_antrian"
                        type="number"
                        min="1"
                        inputmode="numeric"
                        :placeholder="props.next_queue_suggestion ? `Masukkan nomor antrean (cth: ${props.next_queue_suggestion})` : 'Masukkan nomor antrean'"
                        class="flex-1 min-w-0 bg-white px-3 py-2.5 text-sm font-mono font-bold text-gray-700 placeholder-gray-300 outline-none"
                      />
                    </div>
                    <p v-if="verifyForm.errors.no_antrian" class="field-error">{{ verifyForm.errors.no_antrian }}</p>
                    <p class="text-[11px] text-gray-400 mt-1">Masukkan nomor antrean fisik yang dibagikan kepada anggota secara manual.</p>
                  </div>

                  <!-- Submit Button -->
                  <button
                    type="submit"
                    :disabled="verifyForm.processing"
                    class="w-full flex items-center justify-center gap-2 px-7 py-3 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all duration-200 active:scale-95"
                  >
                    <svg v-if="verifyForm.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ verifyForm.processing ? 'Memverifikasi Anggota...' : 'Verifikasi & Ambil Nomor Antrean ✓' }}
                  </button>
                </form>
              </div>
            </div>

            <!-- Error Banner Card (When rejected) -->
            <div
              v-if="$page.props.errors && Object.keys($page.props.errors).length > 0 && !verifyForm.no_kartu"
              class="mt-4 p-4 rounded-xl bg-red-50 border-2 border-red-300 text-red-900 shadow-md animate-in fade-in"
            >
              <div class="flex items-start gap-3">
                <span class="text-2xl shrink-0">⚠️</span>
                <div>
                  <h4 class="font-bold text-sm uppercase tracking-wide text-red-800">
                    Pendaftaran Ditolak:
                  </h4>
                  <ul class="mt-1 text-xs space-y-1 list-disc list-inside">
                    <li v-for="(errMsg, key) in $page.props.errors" :key="key">
                      {{ errMsg }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Newly Verified Success Card -->
            <div
              v-if="$page.props.flash?.verified_success"
              class="mt-4 rounded-2xl border-2 border-green-400 bg-white shadow-xl overflow-hidden animate-in fade-in slide-in-from-top-4"
            >
              <div class="bg-gradient-to-r from-green-700 via-green-600 to-green-700 px-6 py-4 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="text-2xl">🎉</span>
                  <div>
                    <h3 class="font-black text-sm uppercase tracking-wider">
                      BERHASIL DIDAFTARKAN KE ANTREAN OFFLINE
                    </h3>
                    <p class="text-xs text-green-100">
                      Silakan arahkan anggota ke ruang tunggu antrean
                    </p>
                  </div>
                </div>
                <div class="bg-white text-green-800 font-black px-4 py-1.5 rounded-xl text-lg shadow">
                  #{{ $page.props.flash.verified_success.no_antrian }}
                </div>
              </div>

              <div class="p-6 space-y-3">
                <div class="flex items-center justify-between border-b pb-3">
                  <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Nama Lengkap Anggota</div>
                    <div class="text-lg font-black text-gray-800 uppercase" style="font-family: Georgia, serif;">
                      {{ $page.props.flash.verified_success.nama_lengkap }}
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">No. Kartu</div>
                    <div class="text-sm font-mono font-bold text-red-700">
                      BBMC 38 2026 {{ $page.props.flash.verified_success.no_kartu }}
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs pt-1">
                  <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Status Keanggotaan</div>
                    <div class="font-bold text-gray-700">{{ $page.props.flash.verified_success.status_keanggotaan }}</div>
                  </div>
                  <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Chapter</div>
                    <div class="font-semibold text-gray-700">{{ $page.props.flash.verified_success.chapter }}</div>
                  </div>
                </div>

                <div class="p-3 bg-green-50 rounded-lg border border-green-200 text-xs text-green-800 flex items-center justify-between">
                  <span v-if="$page.props.flash.verified_success.no_tps && $page.props.flash.verified_success.no_tps !== '—'"><strong>TPS:</strong> {{ $page.props.flash.verified_success.no_tps }}</span>
                  <span v-else><strong>Status:</strong> Terdaftar dalam antrean</span>
                  <span><strong>Petugas:</strong> {{ $page.props.flash.verified_success.nama_pengurus }}</span>
                </div>
              </div>
            </div>

          </div>

          <!-- SECTION 3: DAFTAR ANTREAN PEMILIH OFFLINE (QUEUE LIST) -->
          <div class="bg-white rounded-2xl border border-red-100 shadow-xl overflow-hidden mt-8">
            <!-- Header with Title & Stats -->
            <div class="bg-gradient-to-r from-red-700 via-red-600 to-red-700 px-6 py-4 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <div>
                <h3 class="font-black text-lg uppercase tracking-wider flex items-center gap-2">
                  <span>📋</span> DAFTAR ANTREAN PEMILIH OFFLINE
                </h3>
                <p class="text-xs text-red-100 mt-0.5">
                  Antrean diurutkan dari nomor terkecil hingga terbesar untuk pemanggilan pemilihan satu per satu
                </p>
              </div>

              <!-- Quick Stats -->
              <div v-if="props.queue_stats" class="flex flex-wrap items-center gap-2 text-xs">
                <span class="bg-amber-400/20 text-amber-100 border border-amber-300/30 px-2.5 py-1 rounded-lg font-bold">
                  Antrean: {{ props.queue_stats.total_antrian }}
                </span>
                <span class="bg-green-400/20 text-green-100 border border-green-300/30 px-2.5 py-1 rounded-lg font-bold">
                  Sudah Memilih: {{ props.queue_stats.total_sudah_memilih }}
                </span>
                <span class="bg-gray-400/20 text-gray-100 border border-gray-300/30 px-2.5 py-1 rounded-lg font-bold">
                  Tidak Memilih: {{ props.queue_stats.total_tidak_memilih }}
                </span>
                <span class="bg-white/10 text-white px-2.5 py-1 rounded-lg font-bold border border-white/20">
                  Total: {{ props.queue_stats.total_semua }}
                </span>
              </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row items-center justify-between gap-3">
              <!-- Search Box -->
              <div class="w-full sm:w-72 relative">
                <input
                  v-model="searchQuery"
                  @input="handleSearch"
                  type="text"
                  placeholder="Cari nomor antrean, kartu, nama..."
                  class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-gray-200 bg-white focus:border-red-400 focus:ring-1 focus:ring-red-200 outline-none"
                />
                <span class="absolute left-3 top-2.5 text-xs text-gray-400">🔍</span>
              </div>

              <!-- Filter Tabs -->
              <div class="flex items-center gap-1 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
                <button
                  v-for="tab in filterTabs"
                  :key="tab.value"
                  type="button"
                  @click="applyStatusFilter(tab.value)"
                  class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors whitespace-nowrap"
                  :class="selectedStatus === tab.value ? 'bg-red-600 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                >
                  {{ tab.label }}
                </button>
              </div>
            </div>

            <!-- Queue Table / List -->
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="bg-gray-100/70 text-gray-600 font-bold uppercase tracking-wider text-[11px] border-b border-gray-200">
                  <tr>
                    <th class="py-3 px-4 text-center w-20">No. Antrean</th>
                    <th class="py-3 px-4">Kartu & Anggota</th>
                    <th class="py-3 px-4">Chapter / Status</th>
                    <th class="py-3 px-4 text-center">TPS</th>
                    <th class="py-3 px-4 text-center">Status Pemilihan</th>
                    <th class="py-3 px-4">Petugas Verifikasi</th>
                    <th class="py-3 px-4 text-center w-48">Aksi Pengurus</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr
                    v-for="item in queueData"
                    :key="item.id"
                    class="hover:bg-red-50/30 transition-colors"
                    :class="item.voting_status === 'antrean' ? 'bg-amber-50/20' : ''"
                  >
                    <!-- No. Antrean Badge -->
                    <td class="py-3.5 px-4 text-center">
                      <span
                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl font-black text-sm shadow-sm"
                        :class="queueBadgeClass(item.voting_status)"
                      >
                        #{{ item.no_antrian }}
                      </span>
                    </td>

                    <!-- Kartu & Anggota -->
                    <td class="py-3.5 px-4">
                      <div class="font-bold text-sm text-gray-800">
                        {{ item.member ? item.member.nama_lengkap : 'Anggota #' + item.member_id }}
                      </div>
                      <div class="font-mono text-[11px] font-semibold text-red-700 mt-0.5">
                        BBMC 38 2026 {{ item.member ? item.member.no_kartu : '—' }}
                      </div>
                    </td>

                    <!-- Chapter / Keanggotaan -->
                    <td class="py-3.5 px-4">
                      <div class="font-semibold text-gray-700">
                        {{ item.member ? item.member.chapter : '—' }}
                      </div>
                      <div class="text-[10px] text-gray-400 font-medium">
                        {{ item.member ? item.member.status_keanggotaan : '—' }}
                      </div>
                    </td>

                    <!-- TPS -->
                    <td class="py-3.5 px-4 text-center">
                      <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
                        {{ item.no_tps || '—' }}
                      </span>
                    </td>

                    <!-- Status Pemilihan Badge -->
                    <td class="py-3.5 px-4 text-center">
                      <span
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider"
                        :class="statusPillClass(item.voting_status)"
                      >
                        {{ statusIcon(item.voting_status) }}
                        {{ statusLabel(item.voting_status) }}
                      </span>
                    </td>

                    <!-- Petugas -->
                    <td class="py-3.5 px-4">
                      <div class="text-xs font-semibold text-gray-700">{{ item.nama_pengurus }}</div>
                      <div class="text-[10px] font-mono text-gray-400">{{ formatTime(item.created_at) }}</div>
                    </td>

                    <!-- Action Buttons -->
                    <td class="py-3.5 px-4 text-center">
                      <!-- When status is antrean: Primary call actions -->
                      <div v-if="item.voting_status === 'antrean'" class="flex items-center justify-center gap-1.5">
                        <button
                          type="button"
                          @click="changeStatus(item, 'sudah_memilih')"
                          class="px-2.5 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white font-bold text-xs shadow-sm transition-all active:scale-95 flex items-center gap-1"
                          title="Tandai Sudah Memilih"
                        >
                          <span>✓</span>
                          <span>Memilih</span>
                        </button>
                        <button
                          type="button"
                          @click="changeStatus(item, 'tidak_memilih')"
                          class="px-2.5 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-sm transition-all active:scale-95"
                          title="Tandai Hadir Tapi Tidak Memilih"
                        >
                          <span>Tidak Memilih</span>
                        </button>
                        <button
                          type="button"
                          @click="changeStatus(item, 'cancel_vote')"
                          class="px-2 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 font-bold text-xs transition-all"
                          title="Batalkan Antrean"
                        >
                          ✕
                        </button>
                      </div>

                      <!-- When status already updated: quick adjustment dropdown/toggle -->
                      <div v-else class="flex items-center justify-center gap-1">
                        <select
                          :value="item.voting_status"
                          @change="(e) => changeStatus(item, (e.target as HTMLSelectElement).value)"
                          class="bg-white border border-gray-200 text-gray-700 text-[11px] font-bold rounded-lg px-2 py-1 outline-none focus:border-red-400 cursor-pointer"
                        >
                          <option value="antrean">⏳ Kembali ke Antrean</option>
                          <option value="sudah_memilih">✅ Sudah Memilih</option>
                          <option value="tidak_memilih">⛔ Tidak Memilih</option>
                          <option value="cancel_vote">❌ Batal Memilih</option>
                        </select>
                      </div>
                    </td>
                  </tr>

                  <!-- Empty state -->
                  <tr v-if="!queueData || queueData.length === 0">
                    <td colspan="7" class="py-12 text-center text-gray-400">
                      <div class="text-3xl mb-2">📭</div>
                      <p class="font-bold text-sm">Belum ada anggota dalam antrean pemilihan offline</p>
                      <p class="text-xs text-gray-400 mt-1">
                        Masukkan nomor kartu anggota di form atas untuk memverifikasi dan menambahkan ke antrean.
                      </p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination / Footer if present -->
            <div
              v-if="props.queue_list?.links && props.queue_list.links.length > 3"
              class="p-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between"
            >
              <div class="text-xs text-gray-500">
                Menampilkan <strong>{{ props.queue_list.from || 0 }}</strong> - <strong>{{ props.queue_list.to || 0 }}</strong> dari <strong>{{ props.queue_list.total || 0 }}</strong> pemilih
              </div>
              <div class="flex items-center gap-1">
                <button
                  v-for="(link, lIdx) in props.queue_list.links"
                  :key="lIdx"
                  @click="goToPage(link.url)"
                  :disabled="!link.url || link.active"
                  v-html="link.label"
                  class="px-2.5 py-1 text-xs rounded border transition-colors"
                  :class="link.active ? 'bg-red-600 text-white font-bold border-red-600' : (!link.url ? 'opacity-40 cursor-not-allowed border-gray-200' : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-200')"
                />
              </div>
            </div>
          </div>

        </div>

        <!-- Back to Member Validation or Portal -->
        <div class="text-center mt-8">
          <a
            href="/member/validate"
            class="inline-flex items-center gap-2 text-red-600 hover:text-red-800 text-xs font-semibold transition-colors duration-200"
          >
            <span>←</span>
            Kembali ke Validasi Kartu Publik (/member/validate)
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
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

// Declare Ziggy route function
declare function route(name: string, params?: any): string

interface Pengurus {
  kode_akses: string
  nama_pengurus: string
}

interface MemberInfo {
  id: number
  nama_lengkap: string
  nama_panggilan?: string
  no_kartu: string
  status_keanggotaan: string
  chapter: string
  foto?: string
}

interface OfflineQueueItem {
  id: number
  nama_pengurus: string
  kode_akses: string
  member_id: number
  no_antrian: number
  no_tps?: string | null
  voting_status: string
  created_at: string
  member?: MemberInfo
}

interface PaginationData {
  data: OfflineQueueItem[]
  from: number
  to: number
  total: number
  links: Array<{ url: string | null; label: string; active: boolean }>
}

interface QueueStats {
  total_antrian: number
  total_sudah_memilih: number
  total_tidak_memilih: number
  total_batal: number
  total_semua: number
}

const props = defineProps<{
  current_pengurus?: Pengurus | null
  queue_list?: PaginationData | null
  queue_stats?: QueueStats | null
  next_queue_suggestion?: number | null
  filters?: {
    search: string
    status: string
  }
  status_labels?: Record<string, string>
}>()

// Login form for pengurus code gate
const loginForm = useForm({
  kode_akses: '',
})

function submitLogin() {
  loginForm.post(route('offline.login'), {
    preserveScroll: true,
  })
}

function submitLogout() {
  router.post(route('offline.logout'))
}

// Verification form for member card
const verifyForm = useForm({
  no_kartu: '',
  no_antrian: '' as string | number,
})

function handleNoKartuInput(e: Event) {
  const input = e.target as HTMLInputElement
  verifyForm.no_kartu = input.value.replace(/\D/g, '').slice(0, 4)
  input.value = verifyForm.no_kartu
  if (verifyForm.errors.no_kartu) {
    delete verifyForm.errors.no_kartu
  }
}

function submitVerify() {
  verifyForm.post(route('offline.verify_member'), {
    preserveScroll: true,
    onSuccess: () => {
      verifyForm.no_kartu = ''
      verifyForm.no_antrian = ''
    },
  })
}

// Change voting status action
function changeStatus(item: OfflineQueueItem, newStatus: string) {
  router.post(
    route('offline.queue_status', { offlineLog: item.id }),
    { voting_status: newStatus },
    { preserveScroll: true }
  )
}

// Search and filter handling
const searchQuery = ref(props.filters?.search || '')
const selectedStatus = ref(props.filters?.status || 'all')

const filterTabs = [
  { label: 'Semua', value: 'all' },
  { label: '⏳ Dalam Antrean', value: 'antrean' },
  { label: '✅ Sudah Memilih', value: 'sudah_memilih' },
  { label: '⛔ Tidak Memilih', value: 'tidak_memilih' },
  { label: '❌ Batal', value: 'cancel_vote' },
]

let searchTimer: any = null
function handleSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    executeFilter()
  }, 400)
}

function applyStatusFilter(status: string) {
  selectedStatus.value = status
  executeFilter()
}

function executeFilter() {
  router.get(
    route('offline.verify'),
    {
      search: searchQuery.value || undefined,
      status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

function refreshQueue() {
  router.reload({ preserveScroll: true })
}

function goToPage(url: string | null) {
  if (url) {
    router.visit(url, { preserveScroll: true, preserveState: true })
  }
}

const queueData = computed(() => {
  return props.queue_list?.data || []
})

// Helpers for badges and styles
function queueBadgeClass(status: string) {
  if (status === 'antrean') return 'bg-amber-500 text-white shadow-amber-200'
  if (status === 'sudah_memilih') return 'bg-green-600 text-white shadow-green-200'
  if (status === 'tidak_memilih') return 'bg-gray-600 text-white shadow-gray-200'
  return 'bg-red-400 text-white'
}

function statusPillClass(status: string) {
  if (status === 'antrean') return 'bg-amber-100 text-amber-800 border border-amber-200'
  if (status === 'sudah_memilih') return 'bg-green-100 text-green-800 border border-green-200'
  if (status === 'tidak_memilih') return 'bg-gray-100 text-gray-800 border border-gray-200'
  return 'bg-red-100 text-red-800 border border-red-200'
}

function statusIcon(status: string) {
  if (status === 'antrean') return '⏳'
  if (status === 'sudah_memilih') return '✅'
  if (status === 'tidak_memilih') return '⛔'
  return '❌'
}

function statusLabel(status: string) {
  if (status === 'antrean') return 'Dalam Antrean'
  if (status === 'sudah_memilih') return 'Sudah Memilih'
  if (status === 'tidak_memilih') return 'Tidak Memilih'
  if (status === 'cancel_vote') return 'Batal'
  return status
}

function formatTime(iso: string) {
  if (!iso) return ''
  try {
    const d = new Date(iso)
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return iso
  }
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
