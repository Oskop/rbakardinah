<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\RbaDetail;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function show(RbaDetail $detail)
    {
        // Basic authorization: check if user belongs to the same unit OR is Admin / Supervisor
        $user = \Auth::user();
        if ($user->role !== 'Administrator' && $user->role !== 'Supervisor' && $user->unit_id !== $detail->submission->unit_id) {
            abort(403);
        }

        $detail->load(['attachments.user', 'attachments.document', 'attachments.details.accountCode', 'accountCode']);
        $attachments = $detail->attachments()->with(['document', 'details.accountCode'])->orderByDesc('rba_attachments.version_number')->get();

        return view('general.history', compact('detail', 'attachments'));
    }
}
