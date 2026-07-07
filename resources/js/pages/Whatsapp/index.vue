<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, nextTick, onBeforeUnmount } from 'vue';
import { 
  Users, MessageSquare, Plus, Check, ChevronsUpDown, X, 
  Send, Sparkles, RefreshCw, AlertCircle, CheckCircle2, XCircle, Info 
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import axios from 'axios';

interface Member {
  id: number;
  nama_lengkap: string;
  no_wa: string;
  email: string | null;
  no_kartu: string | null;
}

interface LogEntry {
  id: number;
  recipient_name: string;
  recipient_phone: string;
  message: string;
  status: 'pending' | 'sending' | 'success' | 'failed';
  response: string | null;
  updated_at: string;
}

interface BlastStats {
  total: number;
  completed: number;
  success: number;
  failed: number;
  progress: number;
}

const props = defineProps<{
  members: Member[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Whatsapp Blast', href: '/dashboard/whatsapp' },
];

// Message variables
const messageText = ref('');
const textareaRef = ref<HTMLTextAreaElement | null>(null);

// Member selection variables
const showDropdown = ref(false);
const searchQuery = ref('');
const selectedMemberIds = ref<number[]>([]);

// Placeholders shortcuts
const placeholders = [
  { label: 'Nama Lengkap', value: '[[nama_lengkap]]' },
  { label: 'Nama Panggilan', value: '[[nama_panggilan]]' },
  { label: 'No. Kartu', value: '[[no_kartu]]' },
  { label: 'No. WA', value: '[[no_wa]]' },
  { label: 'Email', value: '[[email]]' },
  { label: 'Chapter', value: '[[chapter]]' },
  { label: 'Checkpoint', value: '[[checkpoint]]' },
];

// Filter members by search query
const filteredMembers = computed(() => {
  const q = searchQuery.value.toLowerCase().trim();
  if (!q) return props.members;
  return props.members.filter(m => 
    m.nama_lengkap.toLowerCase().includes(q) || 
    (m.nama_panggilan && m.nama_panggilan.toLowerCase().includes(q)) ||
    (m.no_wa && m.no_wa.includes(q)) ||
    (m.no_kartu && m.no_kartu.includes(q))
  );
});

// Selected members array
const selectedMembers = computed(() => {
  return props.members.filter(m => selectedMemberIds.value.includes(m.id));
});

// Toggle member selection
function toggleMember(memberId: number) {
  const index = selectedMemberIds.value.indexOf(memberId);
  if (index === -1) {
    selectedMemberIds.value.push(memberId);
  } else {
    selectedMemberIds.value.splice(index, 1);
  }
}

// Select all matching search results
function selectFiltered() {
  filteredMembers.value.forEach(m => {
    if (!selectedMemberIds.value.includes(m.id)) {
      selectedMemberIds.value.push(m.id);
    }
  });
}

// Clear selection
function clearSelection() {
  selectedMemberIds.value = [];
}

// Insert placeholder at cursor location
function insertPlaceholder(val: string) {
  if (!textareaRef.value) {
    messageText.value += val;
    return;
  }
  const start = textareaRef.value.selectionStart;
  const end = textareaRef.value.selectionEnd;
  const text = messageText.value;
  messageText.value = text.substring(0, start) + val + text.substring(end);
  
  nextTick(() => {
    if (textareaRef.value) {
      textareaRef.value.focus();
      textareaRef.value.selectionStart = textareaRef.value.selectionEnd = start + val.length;
    }
  });
}

// Blast Status / Feedback Panel variables
const isSubmitting = ref(false);
const currentBatchId = ref<string | null>(null);
const blastLogs = ref<LogEntry[]>([]);
const blastStats = ref<BlastStats | null>(null);
const errorMessage = ref<string | null>(null);
let pollingInterval: number | null = null;

// Clean phone helper
function cleanPhone(phone: string) {
  return phone.replace(/\D/g, '');
}

// Submit bulk sending
async function submitBlast() {
  if (selectedMemberIds.value.length === 0) {
    errorMessage.value = 'Silakan pilih minimal satu anggota penerima.';
    return;
  }
  if (!messageText.value.trim()) {
    errorMessage.value = 'Pesan blast tidak boleh kosong.';
    return;
  }

  isSubmitting.value = true;
  errorMessage.value = null;
  currentBatchId.value = null;
  blastLogs.value = [];
  blastStats.value = null;

  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }

  try {
    const res = await axios.post('/dashboard/whatsapp/send', {
      member_ids: selectedMemberIds.value,
      message: messageText.value,
    });

    currentBatchId.value = res.data.batch_id;
    blastStats.value = res.data.stats;

    if (res.data.queued) {
      // Start polling status
      startPolling(res.data.batch_id);
    } else {
      // Sync complete
      blastLogs.value = res.data.logs;
      isSubmitting.value = false;
    }
  } catch (err: any) {
    console.error(err);
    errorMessage.value = err.response?.data?.message || 'Terjadi kesalahan saat mengirim pesan.';
    isSubmitting.value = false;
  }
}

// Poll status of the queued batch
function startPolling(batchId: string) {
  pollingInterval = window.setInterval(async () => {
    try {
      const res = await axios.get(`/dashboard/whatsapp/status/${batchId}`);
      blastLogs.value = res.data.logs;
      blastStats.value = res.data.stats;

      if (res.data.stats.progress >= 100) {
        stopPolling();
      }
    } catch (err) {
      console.error('Error polling status:', err);
      stopPolling();
    }
  }, 2000);
}

function stopPolling() {
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
  isSubmitting.value = false;
}

// Clean up interval on component destroy
onBeforeUnmount(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
});

// Expand/Collapse response message modal or display
const activeResponseId = ref<number | null>(null);
function toggleResponse(id: number) {
  activeResponseId.value = activeResponseId.value === id ? null : id;
}

function formatResponse(respStr: string | null) {
  if (!respStr) return 'Tidak ada detail.';
  try {
    const parsed = JSON.parse(respStr);
    return JSON.stringify(parsed, null, 2);
  } catch {
    return respStr;
  }
}
</script>

<template>
  <Head title="WhatsApp Blast" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4 sm:p-6 max-w-7xl mx-auto w-full">
      <!-- Header -->
      <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold tracking-tight flex items-center gap-2">
          <MessageSquare class="h-6 w-6 text-red-500" />
          WhatsApp Blast
        </h1>
        <p class="text-sm text-muted-foreground">
          Kirim pesan WhatsApp massal ke anggota terdaftar secara dinamis dan efisien.
        </p>
      </div>

      <!-- Main Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Form Section: Left Column -->
        <div class="lg:col-span-7 flex flex-col gap-6">
          
          <!-- Member Selection Card -->
          <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
            <div>
              <h2 class="text-base font-bold flex items-center gap-2">
                <Users class="h-5 w-5 text-red-500" />
                Pilih Penerima
              </h2>
              <p class="text-xs text-muted-foreground">Cari dan pilih anggota yang akan menerima pesan.</p>
            </div>

            <!-- Custom Multi-select Dropdown Search -->
            <div class="relative">
              <div 
                @click="showDropdown = !showDropdown"
                class="w-full flex items-center justify-between border border-input rounded-lg px-3 py-2 text-sm bg-background cursor-pointer hover:border-red-500/30 transition-all"
              >
                <div class="flex items-center gap-2 overflow-hidden mr-2">
                  <span v-if="selectedMemberIds.length === 0" class="text-muted-foreground select-none">
                    Pilih Anggota...
                  </span>
                  <span v-else class="font-semibold text-xs text-red-600 dark:text-red-400">
                    {{ selectedMemberIds.length }} Anggota Terpilih
                  </span>
                </div>
                <ChevronsUpDown class="h-4 w-4 shrink-0 text-muted-foreground" />
              </div>

              <!-- Dropdown Content -->
              <div 
                v-if="showDropdown"
                class="absolute z-50 left-0 right-0 mt-1 rounded-xl border bg-popover text-popover-foreground shadow-lg overflow-hidden flex flex-col max-h-64"
              >
                <!-- Search Input -->
                <div class="p-2 border-b">
                  <Input 
                    v-model="searchQuery"
                    placeholder="Cari nama, no. kartu, no. WA..."
                    class="h-8 text-xs"
                    @click.stop
                  />
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between px-3 py-1.5 border-b bg-muted/20 text-xs">
                  <button 
                    @click.stop="selectFiltered"
                    class="text-red-500 font-semibold hover:underline"
                  >
                    Pilih Semua Hasil ({{ filteredMembers.length }})
                  </button>
                  <button 
                    @click.stop="clearSelection"
                    class="text-muted-foreground hover:text-foreground font-semibold hover:underline"
                  >
                    Bersihkan Semua
                  </button>
                </div>

                <!-- Member Options List -->
                <div class="overflow-y-auto flex-1 py-1 max-h-40">
                  <div 
                    v-for="member in filteredMembers" 
                    :key="member.id"
                    @click.stop="toggleMember(member.id)"
                    class="flex items-center justify-between px-3 py-2 text-xs hover:bg-accent cursor-pointer transition-colors"
                  >
                    <div class="flex flex-col gap-0.5">
                      <span class="font-semibold">{{ member.nama_lengkap }}</span>
                      <span class="text-[10px] text-muted-foreground font-mono">
                        {{ member.no_kartu ? 'KTA: ' + member.no_kartu : '—' }} | WA: {{ member.no_wa }}
                      </span>
                    </div>
                    <div 
                      class="h-4 w-4 border rounded flex items-center justify-center transition-all"
                      :class="selectedMemberIds.includes(member.id) ? 'bg-red-500 border-red-500 text-white' : 'border-input'"
                    >
                      <Check v-if="selectedMemberIds.includes(member.id)" class="h-3 w-3 stroke-[3]" />
                    </div>
                  </div>
                  <div 
                    v-if="filteredMembers.length === 0"
                    class="p-4 text-center text-xs text-muted-foreground"
                  >
                    Tidak ada anggota cocok dengan pencarian.
                  </div>
                </div>
              </div>
            </div>

            <!-- Click-outside handler to close dropdown -->
            <div 
              v-if="showDropdown" 
              class="fixed inset-0 z-40 bg-transparent" 
              @click="showDropdown = false"
            ></div>

            <!-- Selected Members Tags -->
            <div 
              v-if="selectedMembers.length > 0"
              class="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto border rounded-lg p-2.5 bg-muted/20"
            >
              <div 
                v-for="member in selectedMembers" 
                :key="member.id"
                class="inline-flex items-center gap-1 bg-background border px-2 py-0.5 rounded-md text-xs"
              >
                <span class="font-medium max-w-[120px] truncate">{{ member.nama_lengkap }}</span>
                <button 
                  @click="toggleMember(member.id)"
                  class="text-muted-foreground hover:text-red-500 transition-colors"
                >
                  <X class="h-3 w-3" />
                </button>
              </div>
            </div>
          </div>

          <!-- Message Editor Card -->
          <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
            <div>
              <h2 class="text-base font-bold flex items-center gap-2">
                <Sparkles class="h-5 w-5 text-red-500" />
                Editor Pesan
              </h2>
              <p class="text-xs text-muted-foreground">Tulis template pesan. Gunakan shortcut untuk memasukkan placeholder dinamis.</p>
            </div>

            <!-- Placeholder Shortcuts -->
            <div class="flex flex-col gap-2">
              <span class="text-xs font-semibold text-muted-foreground">Klik Shortcut Placeholder:</span>
              <div class="flex flex-wrap gap-1">
                <button
                  v-for="item in placeholders"
                  :key="item.value"
                  @click="insertPlaceholder(item.value)"
                  class="inline-flex items-center gap-0.5 border border-dashed hover:border-red-500/40 hover:bg-red-500/5 px-2 py-1 rounded-lg text-xs font-mono transition-colors"
                >
                  <Plus class="h-3 w-3" />
                  {{ item.label }}
                </button>
              </div>
            </div>

            <!-- Textarea Editor -->
            <div class="flex flex-col gap-1.5">
              <textarea 
                ref="textareaRef"
                v-model="messageText"
                rows="6"
                placeholder="Halo [[nama_lengkap]], ini adalah pesan blast dari BBMC..."
                class="w-full border border-input rounded-xl bg-background px-3.5 py-3 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none transition-all resize-none"
              ></textarea>
              <div class="flex justify-between items-center text-xs text-muted-foreground">
                <span>Masukkan placeholder seperti <strong>[[nama_lengkap]]</strong></span>
                <span>{{ messageText.length }} karakter</span>
              </div>
            </div>

            <!-- Error Banner -->
            <div 
              v-if="errorMessage" 
              class="flex items-center gap-2 rounded-lg bg-red-500/10 border border-red-500/30 p-3 text-xs text-red-600 dark:text-red-400"
            >
              <AlertCircle class="h-4 w-4 shrink-0" />
              <span>{{ errorMessage }}</span>
            </div>

            <!-- Submit Button -->
            <Button 
              @click="submitBlast"
              :disabled="isSubmitting || selectedMemberIds.length === 0"
              class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold h-10 gap-2 shadow-sm shrink-0"
            >
              <Send class="h-4 w-4" />
              <template v-if="isSubmitting">
                Mengirim...
              </template>
              <template v-else-if="selectedMemberIds.length <= 2">
                Kirim Langsung ke {{ selectedMemberIds.length }} Anggota
              </template>
              <template v-else>
                Kirim Massal via Antrean ({{ selectedMemberIds.length }} Anggota)
              </template>
            </Button>
          </div>

        </div>

        <!-- Progress Tracker / Feedback panel: Right Column -->
        <div class="lg:col-span-5 rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-5 min-h-[400px]">
          <div>
            <h2 class="text-base font-bold flex items-center gap-2">
              <RefreshCw class="h-5 w-5 text-red-500" :class="{ 'animate-spin': isSubmitting }" />
              Status Pengiriman
            </h2>
            <p class="text-xs text-muted-foreground">Status real-time dari WhatsApp blast saat ini.</p>
          </div>

          <!-- Empty State -->
          <div 
            v-if="!blastStats && blastLogs.length === 0" 
            class="flex-1 flex flex-col items-center justify-center text-center p-8 text-muted-foreground"
          >
            <MessageSquare class="h-12 w-12 opacity-25 mb-3" />
            <p class="text-sm font-semibold">Belum Ada Pengiriman Aktif</p>
            <p class="text-xs max-w-xs mt-1">
              Pilih anggota dan tulis pesan di panel sebelah kiri untuk memulai pengiriman blast.
            </p>
          </div>

          <!-- Progress Details -->
          <div v-else class="flex flex-col gap-5 flex-1">
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-3 gap-2">
              <div class="border rounded-xl p-3 text-center bg-muted/10">
                <span class="block text-2xl font-bold font-mono">{{ blastStats?.total ?? 0 }}</span>
                <span class="text-[10px] text-muted-foreground font-medium uppercase tracking-wider">Total</span>
              </div>
              <div class="border rounded-xl p-3 text-center bg-green-500/5 border-green-500/20">
                <span class="block text-2xl font-bold font-mono text-green-600 dark:text-green-400">
                  {{ blastStats?.success ?? 0 }}
                </span>
                <span class="text-[10px] text-green-600 dark:text-green-400 font-medium uppercase tracking-wider">Sukses</span>
              </div>
              <div class="border rounded-xl p-3 text-center bg-red-500/5 border-red-500/20">
                <span class="block text-2xl font-bold font-mono text-red-600 dark:text-red-400">
                  {{ blastStats?.failed ?? 0 }}
                </span>
                <span class="text-[10px] text-red-600 dark:text-red-400 font-medium uppercase tracking-wider">Gagal</span>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="flex flex-col gap-1.5">
              <div class="flex justify-between text-xs font-semibold">
                <span>Progress Pengiriman</span>
                <span>{{ blastStats?.progress ?? 0 }}%</span>
              </div>
              <div class="w-full bg-muted rounded-full h-2.5 overflow-hidden">
                <div 
                  class="bg-gradient-to-r from-red-600 to-amber-500 h-full rounded-full transition-all duration-500"
                  :style="{ width: `${blastStats?.progress ?? 0}%` }"
                ></div>
              </div>
            </div>

            <!-- Log Entries List -->
            <div class="flex-1 flex flex-col gap-2 min-h-0">
              <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Log Detail:</span>
              <div class="border rounded-xl overflow-hidden flex-1 overflow-y-auto max-h-80 bg-muted/10">
                
                <div 
                  v-for="log in blastLogs" 
                  :key="log.id"
                  class="flex flex-col border-b last:border-0 p-3 hover:bg-muted/30 transition-colors"
                >
                  <div class="flex items-center justify-between">
                    <span class="font-semibold text-xs">{{ log.recipient_name }}</span>
                    <!-- Status badges -->
                    <span 
                      v-if="log.status === 'success'" 
                      class="inline-flex items-center gap-1 text-[10px] text-green-600 dark:text-green-400 font-bold bg-green-500/10 px-2 py-0.5 rounded-full"
                    >
                      <CheckCircle2 class="h-3 w-3" /> Sukses
                    </span>
                    <span 
                      v-else-if="log.status === 'failed'" 
                      class="inline-flex items-center gap-1 text-[10px] text-red-600 dark:text-red-400 font-bold bg-red-500/10 px-2 py-0.5 rounded-full"
                    >
                      <XCircle class="h-3 w-3" /> Gagal
                    </span>
                    <span 
                      v-else-if="log.status === 'sending'" 
                      class="inline-flex items-center gap-1 text-[10px] text-blue-600 dark:text-blue-400 font-bold bg-blue-500/10 px-2 py-0.5 rounded-full"
                    >
                      <RefreshCw class="h-3 w-3 animate-spin" /> Mengirim
                    </span>
                    <span 
                      v-else 
                      class="inline-flex items-center gap-1 text-[10px] text-muted-foreground font-bold bg-muted px-2 py-0.5 rounded-full"
                    >
                      Antrean
                    </span>
                  </div>
                  
                  <div class="flex items-center justify-between text-[10px] text-muted-foreground mt-1">
                    <span>Phone: {{ log.recipient_phone }}</span>
                    <button 
                      v-if="log.response" 
                      @click="toggleResponse(log.id)"
                      class="text-red-500 font-semibold hover:underline inline-flex items-center gap-0.5"
                    >
                      <Info class="h-3 w-3" /> Detail Respon
                    </button>
                  </div>

                  <!-- Expanded response detail -->
                  <div 
                    v-if="activeResponseId === log.id"
                    class="mt-2 p-2 bg-background border rounded-lg text-[10px] font-mono whitespace-pre-wrap overflow-x-auto text-muted-foreground"
                  >
                    {{ formatResponse(log.response) }}
                  </div>
                </div>

              </div>
            </div>

          </div>

        </div>

      </div>

    </div>
  </AppLayout>
</template>
