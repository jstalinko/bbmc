<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Polling extends Model
{
    protected $fillable = ['member_id', 'calon_id'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function calon()
    {
        return $this->belongsTo(Calon::class);
    }

    public function vote()
    {
        $member = Auth::user();
        $polling = new Polling();
        $polling->member_id = $member->id;
        $polling->calon_id = $this->id;
        $polling->save();
    }

    public function hasVoted()
    {
        $member = Auth::user();
        $calonIds = Calon::where('member_id', $this->member_id ? $this->member_id : ($this->calon ? $this->calon->member_id : 0))->pluck('id');
        return Polling::where('member_id', $member->id)->whereIn('calon_id', $calonIds)->exists();
    }

    public function totalVoteByMember($member_id)
    {
        $calonIds = Calon::where('member_id', $member_id)->pluck('id');
        return Polling::whereIn('calon_id', $calonIds)->count();
    }

    public function totalVote($calon_id)
    {
        $calon = Calon::find($calon_id);
        if ($calon) {
            return $this->totalVoteByMember($calon->member_id);
        }
        return Polling::where('calon_id', $calon_id)->count();
    }

    public function resultVotes()
    {
        $calons = Calon::with('member')->where('status', 'ditetapkan')->get()->unique('member_id')->values();
        $result = [];
        foreach ($calons as $calon) {
            $result[] = [
                'calon_id' => $calon->id,
                'calon_name' => $calon->member ? $calon->member->nama_lengkap : 'Unknown',
                'calon_foto' => $calon->foto_calon,
                'total_vote' => $this->totalVoteByMember($calon->member_id),
            ];
        }
        return $result;
    }

    /**
     * Server-side simulated gradual vote release (throttled polling).
     * Releases votes step-by-step from session/cache so users see numbers increment smoothly over time.
     */
    public function getThrottledResultVotes()
    {
        $realResults = $this->resultVotes();
        $realTotalVotes = array_sum(array_column($realResults, 'total_vote'));

        // Pengaturan via .env (atau hardcoded defaults)
        $throttleEnabled = filter_var(env('POLLING_THROTTLE_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
        
        $stepEnv = env('POLLING_THROTTLE_STEP', '10');
        if (str_contains($stepEnv, ',')) {
            $parts = explode(',', $stepEnv);
            $stepMin = max(1, (int) trim($parts[0]));
            $stepMax = max($stepMin, (int) trim($parts[1]));
        } else {
            $baseStep = max(1, (int) $stepEnv);
            // Default random range disekitar baseStep jika tidak ada _MIN / _MAX khusus di .env
            $stepMin = max(1, (int) env('POLLING_THROTTLE_STEP_MIN', max(1, (int) ($baseStep * 0.4))));
            $stepMax = max($stepMin, (int) env('POLLING_THROTTLE_STEP_MAX', (int) ($baseStep * 1.6)));
        }

        $intervalSeconds = (int) env('POLLING_THROTTLE_INTERVAL', 10); // jeda detik per penambahan suara

        $totalVoters = \Illuminate\Support\Facades\Cache::remember('polling_total_eligible_voters', 60, function () {
            $currentYear = intval(date('Y'));
            $cutoffYear = $currentYear - 10;
            return \App\Models\Member::where(function($sq) {
                $sq->whereRaw('UPPER(status_keanggotaan) = ?', ['LIFE MEMBER'])
                   ->orWhereRaw('UPPER(status_keanggotaan) = ?', ['SS DIPONEGORO']);
            })
            ->where(function($pq) {
                $pq->whereNull('penalty')
                   ->orWhere('penalty', '')
                   ->orWhere('penalty', 'clean');
            })
            ->whereNotNull('terdaftar_sejak')
            ->where('terdaftar_sejak', '<=', $cutoffYear)
            ->count();
        });

        if (!$throttleEnabled || $realTotalVotes <= 0) {
            foreach ($realResults as &$res) {
                $res['percentage'] = $realTotalVotes > 0 ? round(($res['total_vote'] / $realTotalVotes) * 100, 1) : 0;
            }
            $percentageVoted = $totalVoters > 0 ? round(($realTotalVotes / $totalVoters) * 100, 1) : 0;
            return [
                'results' => $realResults,
                'totalVotes' => $realTotalVotes,
                'totalVoters' => $totalVoters,
                'percentageVoted' => $percentageVoted
            ];
        }

        $cacheKey = 'polling_throttled_state';
        $state = \Illuminate\Support\Facades\Cache::get($cacheKey);

        // Reset state jika belum ada, atau jika total suara asli berubah/berkurang (misal di-re-seed)
        $needsReset = !$state || 
                      !isset($state['released_total']) || 
                      ($state['real_total'] ?? -1) != $realTotalVotes ||
                      $state['released_total'] > $realTotalVotes;

        if ($needsReset) {
            // Mulai dari angka awal random (misal antara stepMin dan stepMax)
            $initialStep = rand($stepMin, $stepMax);
            $initialTotal = min($initialStep, $realTotalVotes);
            $candidateVotes = [];
            $distributed = 0;

            foreach ($realResults as $cand) {
                $candReal = $cand['total_vote'];
                $prop = $realTotalVotes > 0 ? (int) round(($candReal / $realTotalVotes) * $initialTotal) : 0;
                $candidateVotes[$cand['calon_id']] = min($prop, $candReal);
                $distributed += $candidateVotes[$cand['calon_id']];
            }

            // Sesuaikan pembulatan agar total pas dengan initialTotal
            while ($distributed < $initialTotal) {
                $added = false;
                foreach ($realResults as $cand) {
                    $cid = $cand['calon_id'];
                    if ($distributed < $initialTotal && ($candidateVotes[$cid] ?? 0) < $cand['total_vote']) {
                        $candidateVotes[$cid] = ($candidateVotes[$cid] ?? 0) + 1;
                        $distributed++;
                        $added = true;
                    }
                }
                if (!$added) break;
            }

            $state = [
                'real_total' => $realTotalVotes,
                'released_total' => $distributed,
                'last_tick_time' => time(),
                'candidate_votes' => $candidateVotes
            ];
            \Illuminate\Support\Facades\Cache::forever($cacheKey, $state);
        } else {
            // Cek apakah jeda waktu (intervalSeconds) sudah berlalu
            $elapsed = time() - $state['last_tick_time'];
            if ($elapsed >= $intervalSeconds && $state['released_total'] < $realTotalVotes) {
                $stepsPassed = floor($elapsed / $intervalSeconds);
                $votesToAdd = 0;
                for ($s = 0; $s < $stepsPassed; $s++) {
                    $votesToAdd += rand($stepMin, $stepMax);
                    if ($state['released_total'] + $votesToAdd >= $realTotalVotes) {
                        break;
                    }
                }
                $votesToAdd = min($votesToAdd, $realTotalVotes - $state['released_total']);

                if ($votesToAdd > 0) {
                    $added = 0;
                    while ($added < $votesToAdd) {
                        $addedInLoop = false;
                        foreach ($realResults as $cand) {
                            $cid = $cand['calon_id'];
                            $current = $state['candidate_votes'][$cid] ?? 0;
                            if ($current < $cand['total_vote']) {
                                $state['candidate_votes'][$cid] = $current + 1;
                                $added++;
                                $addedInLoop = true;
                                if ($added >= $votesToAdd) break;
                            }
                        }
                        if (!$addedInLoop) break;
                    }

                    $state['released_total'] += $added;
                    $state['last_tick_time'] = $state['last_tick_time'] + ($stepsPassed * $intervalSeconds);
                    \Illuminate\Support\Facades\Cache::forever($cacheKey, $state);
                }
            }
        }

        // Format hasil untuk ditampilkan ke UI
        $currentTotal = $state['released_total'];
        $formattedResults = [];
        foreach ($realResults as $cand) {
            $cid = $cand['calon_id'];
            $cvote = $state['candidate_votes'][$cid] ?? 0;
            $formattedResults[] = [
                'calon_id' => $cid,
                'calon_name' => $cand['calon_name'],
                'calon_foto' => $cand['calon_foto'],
                'total_vote' => $cvote,
                'percentage' => $currentTotal > 0 ? round(($cvote / $currentTotal) * 100, 1) : 0
            ];
        }

        $percentageVoted = $totalVoters > 0 ? round(($currentTotal / $totalVoters) * 100, 1) : 0;

        return [
            'results' => $formattedResults,
            'totalVotes' => $currentTotal,
            'totalVoters' => $totalVoters,
            'percentageVoted' => $percentageVoted
        ];
    }
}
