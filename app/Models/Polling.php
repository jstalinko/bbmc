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
        return Polling::where('member_id', $member->id)->where('calon_id', $this->id)->exists();
    }

    public function totalVote($calon_id)
    {
        return Polling::where('calon_id', $calon_id)->count();
    }
    public function resultVotes()
    {
        // all Calon where status ditetapkan then count polling->totalVote($calon->id)
        $calons = Calon::where('status', 'ditetapkan')->get();
        $result = [];
        foreach ($calons as $calon) {
            $result[] = [
                'calon_id' => $calon->id,
                'calon_name' => $calon->member->nama_lengkap,
                'calon_foto' => $calon->foto_calon,
                'total_vote' => $this->totalVote($calon->id),
            ];
        }
        return $result;
    }
}
