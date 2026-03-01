<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\RbaDetail;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function show(RbaDetail $detail)
    {
        // Basic authorization: check if user belongs to the same unit OR is Admin
        $user = \Auth::user();
        if ($user->role !== 'Administrator' && $user->unit_id !== $detail->submission->unit_id) {
            abort(403);
        }

        $detail->load(['attachments.user', 'accountCode']);
        $attachments = $detail->attachments()->orderByDesc('version_number')->get();

        return view('general.history', compact('detail', 'attachments'));
    }
}
