<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ElectionAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('election_member_id')) {
            return redirect()->route('election.login');
        }

        $memberId = $request->session()->get('election_member_id');
        $member = \App\Models\Member::find($memberId);
        if (!$member || $member->offline_voter) {
            $request->session()->forget('election_member_id');
            return redirect()->route('election.login')->withErrors([
                'no_kartu' => 'Anda telah terdaftar sebagai pemilih offline dan tidak dapat melakukan pemilihan secara online.'
            ]);
        }

        return $next($request);
    }
}
