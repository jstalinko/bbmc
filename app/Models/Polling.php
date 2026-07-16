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
}
