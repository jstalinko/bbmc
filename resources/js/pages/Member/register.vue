<template>
  <div class="min-h-screen flex flex-col" style="background: linear-gradient(135deg, #fff5f5 0%, #fff9f7 40%, #fef2f2 100%);">
    <!-- Top accent bar -->
    <div class="h-1.5 bg-gradient-to-r from-red-700 via-red-500 to-red-700"></div>

    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center px-4 py-10">
      <div class="w-full max-w-2xl">

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
            BBMC REGISTRATION
          </h1>
          <p class="text-gray-500 text-sm tracking-wider uppercase">Bikers Brotherhood Motorcycle Club</p>
          <div class="mt-3 h-0.5 w-32 mx-auto bg-gradient-to-r from-transparent via-red-400 to-transparent"></div>
        </div>

        <!-- Global Error Banner -->
        <div v-if="hasErrors && showErrors"
          class="mb-5 bg-red-50 border border-red-200 rounded-xl px-5 py-4 flex gap-3 items-start shadow-sm">
          <div class="text-red-500 text-xl mt-0.5">⚠️</div>
          <div class="flex-1">
            <p class="text-red-700 font-semibold text-sm mb-1">Terdapat kesalahan yang perlu diperbaiki:</p>
            <ul class="space-y-0.5">
              <li v-for="(err, key) in form.errors" :key="key" class="text-red-600 text-xs flex items-center gap-1.5">
                <span class="w-1 h-1 rounded-full bg-red-400 inline-block"></span>{{ err }}
              </li>
            </ul>
          </div>
          <button @click="showErrors = false" class="text-red-400 hover:text-red-600 text-lg leading-none">&times;</button>
        </div>

        <!-- Success Banner -->
        <div v-if="$page.props.flash?.success"
          class="mb-5 bg-green-50 border border-green-200 rounded-xl px-5 py-4 flex gap-3 items-center shadow-sm">
          <div class="text-green-500 text-xl">✅</div>
          <p class="text-green-700 font-semibold text-sm">{{ $page.props.flash.success }}</p>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center justify-center mb-7">
          <button @click="currentStep = 1" class="flex items-center gap-2 focus:outline-none">
            <div :class="[
              'w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all duration-300',
              currentStep === 1
                ? 'bg-red-600 border-red-600 text-white shadow-md shadow-red-200'
                : 'bg-red-100 border-red-300 text-red-600'
            ]">
              <svg v-if="currentStep > 1" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
              <span v-else>1</span>
            </div>
            <span :class="['text-xs font-bold uppercase tracking-wider hidden sm:inline', currentStep >= 1 ? 'text-red-600' : 'text-gray-400']">Data Pribadi</span>
          </button>
          <div class="w-16 sm:w-24 h-0.5 mx-3 rounded-full transition-all duration-500" :class="currentStep > 1 ? 'bg-red-400' : 'bg-red-100'"></div>
          <div class="flex items-center gap-2">
            <div :class="[
              'w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all duration-300',
              currentStep === 2
                ? 'bg-red-600 border-red-600 text-white shadow-md shadow-red-200'
                : 'bg-red-100 border-red-300 text-red-400'
            ]">2</div>
            <span :class="['text-xs font-bold uppercase tracking-wider hidden sm:inline', currentStep === 2 ? 'text-red-600' : 'text-gray-400']">Data Keanggotaan</span>
          </div>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-red-100/80 border border-red-100 overflow-hidden">

          <!-- Card Header -->
          <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-4">
            <h2 class="text-white font-bold text-base uppercase tracking-wider">
              {{ currentStep === 1 ? '📋 Data Pribadi' : '🏍️ Data Keanggotaan' }}
            </h2>
            <p class="text-red-100 text-xs mt-0.5 opacity-80">Langkah {{ currentStep }} dari 2 — Isi semua field yang diperlukan</p>
          </div>

          <div class="p-6">

            <!-- ─── STEP 1 ─── -->
            <div v-if="currentStep === 1" class="space-y-4">

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="field-label">Nama Lengkap <span class="text-red-500">*</span></label>
                  <input v-model="form.nama_lengkap" type="text" placeholder="Nama sesuai KTP"
                    :class="['field-input', form.errors.nama_lengkap ? 'border-red-400 bg-red-50' : '']" />
                  <p v-if="form.errors.nama_lengkap" class="field-error">{{ form.errors.nama_lengkap }}</p>
                </div>
                <div>
                  <label class="field-label">Nama Panggilan <span class="text-red-500">*</span></label>
                  <input v-model="form.nama_panggilan" type="text" placeholder="Nama alias / panggilan"
                    :class="['field-input', form.errors.nama_panggilan ? 'border-red-400 bg-red-50' : '']" />
                  <p v-if="form.errors.nama_panggilan" class="field-error">{{ form.errors.nama_panggilan }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="field-label">Tempat Lahir <span class="text-red-500">*</span></label>
                  <input v-model="form.tempat_lahir" type="text" placeholder="Kota tempat lahir"
                    :class="['field-input', form.errors.tempat_lahir ? 'border-red-400 bg-red-50' : '']" />
                  <p v-if="form.errors.tempat_lahir" class="field-error">{{ form.errors.tempat_lahir }}</p>
                </div>
                <div>
                  <label class="field-label">Tanggal Lahir <span class="text-red-500">*</span></label>
                  <input
                    :value="form.tanggal_lahir"
                    @input="handleTanggalLahirInput"
                    type="text"
                    placeholder="DD/MM/YYYY"
                    maxlength="10"
                    inputmode="numeric"
                    :class="['field-input font-mono tracking-widest', form.errors.tanggal_lahir ? 'border-red-400 bg-red-50' : '']"
                  />
                  <p v-if="form.errors.tanggal_lahir" class="field-error">{{ form.errors.tanggal_lahir }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="field-label">Jenis Kelamin <span class="text-red-500">*</span></label>
                  <select v-model="form.jenis_kelamin" :class="['field-input', form.errors.jenis_kelamin ? 'border-red-400 bg-red-50' : '']">
                    <option value="" disabled>Pilih jenis kelamin</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                  </select>
                  <p v-if="form.errors.jenis_kelamin" class="field-error">{{ form.errors.jenis_kelamin }}</p>
                </div>
                <div>
                  <label class="field-label">Gol. Darah <span class="text-red-500">*</span></label>
                  <select v-model="form.gol_darah" :class="['field-input', form.errors.gol_darah ? 'border-red-400 bg-red-50' : '']">
                    <option value="" disabled>Pilih gol. darah</option>
                    <option value="-">Tidak Tau</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="AB">AB</option>
                    <option value="O">O</option>
                  </select>
                  <p v-if="form.errors.gol_darah" class="field-error">{{ form.errors.gol_darah }}</p>
                </div>
              </div>

              <div>
                <label class="field-label">NIK (No. KTP) <span class="text-red-500">*</span></label>
                <input v-model="form.nik" type="text" maxlength="16" placeholder="16 digit nomor NIK"
                  :class="['field-input', form.errors.nik ? 'border-red-400 bg-red-50' : '']" />
                <p v-if="form.errors.nik" class="field-error">{{ form.errors.nik }}</p>
              </div>

              <div>
                <label class="field-label">Alamat Lengkap <span class="text-red-500">*</span></label>
                <textarea v-model="form.alamat" rows="3" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Provinsi"
                  :class="['field-input resize-none', form.errors.alamat ? 'border-red-400 bg-red-50' : '']"></textarea>
                <p v-if="form.errors.alamat" class="field-error">{{ form.errors.alamat }}</p>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="field-label">No. WhatsApp <span class="text-red-500">*</span></label>
                  <input
                    :value="form.no_wa"
                    @input="handleNoWaInput"
                    type="tel"
                    placeholder="628xxxxxxxxxx"
                    inputmode="numeric"
                    :class="['field-input font-mono', form.errors.no_wa ? 'border-red-400 bg-red-50' : '']"
                  />
                  <p class="text-gray-400 text-[10px] mt-1">Format otomatis: <span class="font-mono font-semibold">628</span>xxxxxxxxxx</p>
                  <p v-if="form.errors.no_wa" class="field-error">{{ form.errors.no_wa }}</p>
                </div>
                <div>
                  <label class="field-label">Email</label>
                  <input v-model="form.email" type="email" placeholder="email@domain.com"
                    :class="['field-input', form.errors.email ? 'border-red-400 bg-red-50' : '']" />
                  <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>
                </div>
              </div>

              <div>
                <label class="field-label">Profesi / Keahlian</label>
                <input v-model="form.profesi" type="text" placeholder="Pekerjaan atau keahlian utama" class="field-input" />
              </div>

              <!-- Foto Upload -->
              <div>
                <label class="field-label">Foto Diri</label>
                <div
                  class="relative border-2 border-dashed rounded-xl p-5 text-center cursor-pointer transition-all duration-200 group"
                  :class="fotoPreview ? 'border-red-300 bg-red-50' : 'border-red-200 hover:border-red-400 bg-red-50/40 hover:bg-red-50'"
                  @click="($refs.fotoInput as HTMLInputElement).click()"
                  @dragover.prevent
                  @drop.prevent="handleDrop"
                >
                  <input ref="fotoInput" type="file" accept="image/*" class="hidden" @change="handleFotoChange" />
                  <div v-if="!fotoPreview" class="space-y-1.5 pointer-events-none">
                    <div class="text-3xl">📷</div>
                    <p class="text-gray-500 text-sm">Klik atau seret foto ke sini</p>
                    <p class="text-gray-400 text-xs">JPG, PNG, WEBP — maks. 5MB</p>
                  </div>
                  <div v-else class="flex items-center gap-4 text-left pointer-events-none">
                    <img :src="fotoPreview" class="w-16 h-16 rounded-full object-cover border-2 border-red-300 shadow" />
                    <div>
                      <p class="text-gray-700 text-sm font-medium">{{ (form.foto as File)?.name }}</p>
                      <p class="text-gray-400 text-xs">{{ fotoSize }}</p>
                      <button @click.stop="clearFoto" class="text-red-500 text-xs hover:text-red-700 mt-1 underline pointer-events-auto">Hapus foto</button>
                    </div>
                  </div>
                </div>
                <p v-if="form.errors.foto" class="field-error">{{ form.errors.foto }}</p>
              </div>
            </div>

            <!-- ─── STEP 2 ─── -->
            <div v-if="currentStep === 2" class="space-y-4">

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="field-label">No. Kartu</label>
                  <div class="flex items-center gap-0 border border-gray-200 rounded-lg overflow-hidden focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-100 transition-all duration-200">
                    <span class="bg-gray-50 px-3 py-2.5 text-sm font-mono font-bold text-gray-500 border-r border-gray-200 whitespace-nowrap select-none">BBMC 36 2026</span>
                    <input
                      v-model="form.no_kartu"
                      type="text"
                      maxlength="4"
                      inputmode="numeric"
                      placeholder="0000"
                      @input="form.no_kartu = form.no_kartu.replace(/\D/g, '').slice(0, 4)"
                      class="flex-1 min-w-0 bg-white px-3 py-2.5 text-sm font-mono font-bold text-gray-700 placeholder-gray-300 outline-none tracking-widest"
                    />
                  </div>
                </div>
                <div>
                  <label class="field-label">Status Keanggotaan <span class="text-red-500">*</span></label>
                  <select v-model="form.status_keanggotaan" :class="['field-input', form.errors.status_keanggotaan ? 'border-red-400 bg-red-50' : '']">
                    <option value="" disabled>Pilih status</option>
                    <option>SS DIPONEGORO</option>
                    <option>LIFE MEMBER</option>
                    <option>HONORARY</option>
                    <option>VIRGIN</option>
                    <option>PROSPECT</option>
                  </select>
                  <p v-if="form.errors.status_keanggotaan" class="field-error">{{ form.errors.status_keanggotaan }}</p>
                </div>
              </div>

              <div>
                <label class="field-label">Chapter <span class="text-red-500">*</span></label>
                <select v-model="form.chapter" :class="['field-input', form.errors.chapter ? 'border-red-400 bg-red-50' : '']">
                  <option value="" disabled>Pilih chapter</option>
                  <option>Mother Chapter</option>
                  <option>Lombok</option>
                  <option>Jakarta</option>
                  <option>Central Java</option>
                  <option>East Java</option>
                  <option>Borneo</option>
                  <option>Sumatra</option>
                </select>
                <p v-if="form.errors.chapter" class="field-error">{{ form.errors.chapter }}</p>
              </div>

              <div>
                <label class="field-label">Checkpoint</label>
                <select v-model="form.checkpoint" class="field-input">
                  <option value="">— Tidak ada / pilih checkpoint —</option>
                  <option v-for="cp in checkpoints" :key="cp">{{ cp }}</option>
                </select>
              </div>

              <!-- Region — hanya muncul jika checkpoint = Bandung -->
              <div v-if="form.checkpoint === 'Bandung'" class="transition-all duration-300">
                <label class="field-label">Region <span class="text-red-500">*</span></label>
                <select v-model="form.region" :class="['field-input', form.errors.region ? 'border-red-400 bg-red-50' : '']">
                  <option value="" disabled>Pilih region</option>
                  <option value="West Region">West Region</option>
                  <option value="East Region">East Region</option>
                  <option value="North Region">North Region</option>
                  <option value="South Region">South Region</option>
                </select>
                <p v-if="form.errors.region" class="field-error">{{ form.errors.region }}</p>
              </div>

              <div>
                <label class="field-label">Terdaftar Sejak (Tahun)</label>
                <input
                  v-model="form.terdaftar_sejak"
                  type="number"
                  min="1970"
                  :max="new Date().getFullYear()"
                  placeholder="Contoh: 2020"
                  class="field-input"
                />
              </div>

            </div>
          </div>

          <!-- Action Buttons -->
          <div class="px-6 pb-6 flex items-center justify-between gap-3 border-t border-red-50 pt-5">
            <button v-if="currentStep === 2" @click="currentStep = 1"
              class="flex items-center gap-1.5 px-5 py-2.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-semibold transition-all duration-200 active:scale-95">
              ← Kembali
            </button>
            <div v-else></div>

            <button v-if="currentStep === 1" @click="nextStep"
              class="ml-auto flex items-center gap-1.5 px-7 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all duration-200 active:scale-95">
              Selanjutnya →
            </button>

            <button v-if="currentStep === 2" @click="submitForm" :disabled="form.processing"
              class="flex items-center gap-2 px-7 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-bold uppercase tracking-wider shadow-md shadow-red-200 transition-all duration-200 active:scale-95">
              <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ form.processing ? 'Mengirim...' : 'Daftar Sekarang ✓' }}
            </button>
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

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const currentStep = ref(1)
const fotoPreview = ref<string | null>(null)
const fotoSize = ref('')
const fotoInput = ref<HTMLInputElement | null>(null)
const showErrors = ref(false)

const checkpoints = [
  'Bandung', 'Bogor', 'Garut', 'Sumedang', 'Malang', 'Lamongan',
  'Cirebon', 'Batam', 'Sukabumi', 'Pekalongan', 'Sleman', 'Solo', 'Jogjakarta'
]

const form = useForm({
  nama_lengkap: '',
  nama_panggilan: '',
  tempat_lahir: '',
  tanggal_lahir: '',
  jenis_kelamin: '',
  gol_darah: '',
  nik: '',
  alamat: '',
  no_wa: '',
  email: '',
  profesi: '',
  foto: null as File | null,
  no_kartu: '',
  status_keanggotaan: '',
  chapter: '',
  checkpoint: '',
  region: '',
  terdaftar_sejak: '',
})

// Reset region jika checkpoint bukan Bandung
watch(() => form.checkpoint, (val) => {
  if (val !== 'Bandung') {
    form.region = ''
  }
})

// ── Masking tanggal lahir DD/MM/YYYY ──
function handleTanggalLahirInput(e: Event) {
  const input = e.target as HTMLInputElement
  // Strip semua non-digit, simpan posisi caret sebelum masking
  let raw = input.value.replace(/\D/g, '').slice(0, 8)
  let masked = ''

  if (raw.length > 0) masked += raw.substring(0, 2)
  if (raw.length >= 3) masked += '/' + raw.substring(2, 4)
  if (raw.length >= 5) masked += '/' + raw.substring(4, 8)

  form.tanggal_lahir = masked
  input.value = masked
}

// ── Validasi nilai tanggal lahir ──
const maxYear = new Date().getFullYear() - 18

function validateTanggalLahir(): string | null {
  const val = form.tanggal_lahir
  if (!val || val.length < 10) return 'Tanggal lahir wajib diisi dengan format DD/MM/YYYY.'

  const parts = val.split('/')
  if (parts.length !== 3) return 'Format tanggal lahir harus DD/MM/YYYY.'

  const dd = parseInt(parts[0], 10)
  const mm = parseInt(parts[1], 10)
  const yyyy = parseInt(parts[2], 10)

  if (isNaN(dd) || dd < 1 || dd > 31) return 'Tanggal (DD) tidak valid, harus antara 01–31.'
  if (isNaN(mm) || mm < 1 || mm > 12) return 'Bulan (MM) tidak valid, harus antara 01–12.'
  if (isNaN(yyyy) || yyyy > maxYear) return `Tahun lahir maksimal ${maxYear} (minimal usia 18 tahun).`
  if (yyyy < 1900) return 'Tahun lahir tidak valid.'

  return null
}

// ── Masking nomor kartu: BBMC 36 2026 XXXX ──
const noKartuMasked = computed(() => {
  const last4 = form.no_kartu ? form.no_kartu.slice(-4).padStart(4, '0') : '0000'
  return `BBMC 36 2026 ${last4}`
})

const hasErrors = computed(() => Object.keys(form.errors).length > 0)

// Step 1 required fields validation
const step1Fields = ['nama_lengkap', 'nama_panggilan', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'gol_darah', 'nik', 'alamat', 'no_wa']

function handleFotoChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  form.foto = file
  fotoSize.value = (file.size / 1024).toFixed(0) + ' KB'
  const reader = new FileReader()
  reader.onload = (ev) => { fotoPreview.value = ev.target?.result as string }
  reader.readAsDataURL(file)
}

function handleDrop(e: DragEvent) {
  const file = e.dataTransfer?.files?.[0]
  if (!file || !file.type.startsWith('image/')) return
  form.foto = file
  fotoSize.value = (file.size / 1024).toFixed(0) + ' KB'
  const reader = new FileReader()
  reader.onload = (ev) => { fotoPreview.value = ev.target?.result as string }
  reader.readAsDataURL(file)
}

function clearFoto() {
  fotoPreview.value = null
  form.foto = null
  fotoSize.value = ''
  if (fotoInput.value) fotoInput.value.value = ''
}

// ── Format No. WhatsApp → 628xx ──
function handleNoWaInput(e: Event) {
  const input = e.target as HTMLInputElement
  // Buang semua karakter bukan digit
  let digits = input.value.replace(/\D/g, '')
  // Ganti awalan 0 → 62 (misal 08xx → 628xx)
  if (digits.startsWith('0')) {
    digits = '62' + digits.slice(1)
  }
  // Pastikan selalu diawali 62
  if (digits.length > 0 && !digits.startsWith('62')) {
    digits = '62' + digits
  }
  form.no_wa = digits
  input.value = digits
}

function nextStep() {
  // Client-side check for step 1 required fields
  const missing = step1Fields.filter((f) => !form[f as keyof typeof form])
  if (missing.length) {
    showErrors.value = true
    missing.forEach((f) => {
      form.errors[f as keyof typeof form.errors] = 'Field ini wajib diisi.'
    })
    return
  }

  // Validasi nilai tanggal lahir
  const tglError = validateTanggalLahir()
  if (tglError) {
    showErrors.value = true
    form.errors['tanggal_lahir' as keyof typeof form.errors] = tglError
    return
  }

  showErrors.value = false
  currentStep.value = 2
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function submitForm() {
  showErrors.value = true
  form.post(route('member.register_post'), {
    forceFormData: true,
    onSuccess: () => {
      form.reset()
      fotoPreview.value = null
      currentStep.value = 1
      showErrors.value = false
    },
    onError: () => {
      showErrors.value = true
      // If errors belong to step 1 fields, go back to step 1
      const step1Errors = step1Fields.some((f) => f in form.errors)
      if (step1Errors) currentStep.value = 1
    },
  })
}
</script>

<style scoped>
.field-label {
  @apply block text-gray-600 text-xs font-semibold uppercase tracking-wider mb-1.5;
}
.field-input {
  @apply w-full bg-white border border-gray-200 focus:border-red-400 text-gray-700 placeholder-gray-300 rounded-lg px-4 py-2.5 text-sm outline-none transition-all duration-200 focus:ring-2 focus:ring-red-100;
}
.field-error {
  @apply text-red-500 text-xs mt-1 flex items-center gap-1;
}
.field-error::before {
  content: '⚠ ';
}
</style>