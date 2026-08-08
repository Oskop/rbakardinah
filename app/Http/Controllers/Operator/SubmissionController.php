<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RbaHeader;
use App\Models\RbaSubmission;
use App\Models\RbaAccountPagu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmissionController extends Controller
{
    public function index()
    {
        $unitId = Auth::user()->unit_id;
        $submissions = RbaSubmission::with(['header.period', 'unit'])
            ->where('unit_id', $unitId)
            ->latest()
            ->get();

        return view('operator.submissions.index', compact('submissions'));
    }

    public function show(RbaSubmission $submission)
    {
        // Ensure operator can only see their unit's submission
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $submission->load(['details' => function ($query) {
            $query->where('created_by', Auth::id());
        }, 'details.accountCode', 'details.attachments', 'header.period', 'documents' => function ($query) {
            $query->where('user_id', Auth::id());
        }, 'documents.versions', 'documents.latestVersion']);

        // Load pagu for this header
        $pagus = RbaAccountPagu::where('rba_header_id', $submission->rba_header_id)->get()->keyBy('account_code_id');

        // Determine previous RBA header to fetch nominal pagu AWAL
        $currentHeader = $submission->header;
        $currentYear = $currentHeader->year;
        $currentPeriodName = $currentHeader->period->name ?? '';

        $previousHeader = null;
        if (stripos($currentPeriodName, 'Perubahan') !== false) {
            // If current is Perubahan -> Previous is Murni for the SAME year
            $previousHeader = RbaHeader::where('year', $currentYear)
                ->where('id', '!=', $currentHeader->id)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Murni%');
                })
                ->first();
        } else {
            // If current is Murni -> Previous is Perubahan for the PREVIOUS year (year - 1)
            $previousHeader = RbaHeader::where('year', $currentYear - 1)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Perubahan%');
                })
                ->first();
        }

        // Fallback: Closest preceding RBA header by ID/Year
        if (!$previousHeader) {
            $previousHeader = RbaHeader::where('id', '<', $currentHeader->id)
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->first();
        }

        $previousPagus = $previousHeader
            ? RbaAccountPagu::where('rba_header_id', $previousHeader->id)->get()->keyBy('account_code_id')
            : collect();

        // Calculate totals per account code for this header (for visual indicator)
        $headerTotals = \App\Models\RbaDetail::whereHas('submission', function ($q) use ($submission) {
            $q->where('rba_header_id', $submission->rba_header_id);
        })
            ->selectRaw('account_code_id, SUM(nominal_request) as total')
            ->groupBy('account_code_id')
            ->get()
            ->keyBy('account_code_id');

        return view('operator.submissions.show', compact('submission', 'pagus', 'headerTotals', 'previousPagus'));
    }

    public function submit(RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        if ($submission->status_submission !== 'Draft') {
            return back()->with('error', 'Only Draft submissions can be submitted.');
        }

        $submission->update(['status_submission' => 'Pending Supervisor']);

        return redirect()->route('operator.submissions.index')->with('success', 'Submission sent to Supervisor.');
    }

    public function updateBackground(Request $request, RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $request->validate([
            'background' => 'required|string',
        ]);

        $submission->update([
            'background' => $request->background,
        ]);

        return back()->with('success', 'Latar belakang RBA berhasil diperbarui.');
    }

    public function printPreview(Request $request, RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $includeBackground = $request->get('include_background', '1') == '1';

        $submission->load(['details' => function ($query) {
            $query->where('created_by', Auth::id());
        }, 'details.accountCode', 'header.period', 'unit']);

        $currentHeader = $submission->header;
        $currentYear = $currentHeader->year;
        $currentPeriodName = $currentHeader->period->name ?? '';

        $previousHeader = null;
        if (stripos($currentPeriodName, 'Perubahan') !== false) {
            $previousHeader = RbaHeader::where('year', $currentYear)
                ->where('id', '!=', $currentHeader->id)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Murni%');
                })
                ->first();
        } else {
            $previousHeader = RbaHeader::where('year', $currentYear - 1)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Perubahan%');
                })
                ->first();
        }

        if (!$previousHeader) {
            $previousHeader = RbaHeader::where('id', '<', $currentHeader->id)
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->first();
        }

        $previousPagus = $previousHeader
            ? RbaAccountPagu::where('rba_header_id', $previousHeader->id)->get()->keyBy('account_code_id')
            : collect();

        return view('reports.operator_rba_print', compact('submission', 'includeBackground', 'previousPagus'));
    }

    public function exportPdf(Request $request, RbaSubmission $submission)
    {
        if ($submission->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $includeBackground = $request->get('include_background', '1') == '1';

        $submission->load(['details' => function ($query) {
            $query->where('created_by', Auth::id());
        }, 'details.accountCode', 'header.period', 'unit']);

        $currentHeader = $submission->header;
        $currentYear = $currentHeader->year;
        $currentPeriodName = $currentHeader->period->name ?? '';

        $previousHeader = null;
        if (stripos($currentPeriodName, 'Perubahan') !== false) {
            $previousHeader = RbaHeader::where('year', $currentYear)
                ->where('id', '!=', $currentHeader->id)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Murni%');
                })
                ->first();
        } else {
            $previousHeader = RbaHeader::where('year', $currentYear - 1)
                ->whereHas('period', function ($q) {
                    $q->where('name', 'like', '%Perubahan%');
                })
                ->first();
        }

        if (!$previousHeader) {
            $previousHeader = RbaHeader::where('id', '<', $currentHeader->id)
                ->orderByDesc('year')
                ->orderByDesc('id')
                ->first();
        }

        $previousPagus = $previousHeader
            ? RbaAccountPagu::where('rba_header_id', $previousHeader->id)->get()->keyBy('account_code_id')
            : collect();

        $html = view('reports.operator_rba_print', compact('submission', 'includeBackground', 'previousPagus'))->render();

        // Check if mPDF is available
        if (class_exists(\Mpdf\Mpdf::class)) {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 12,
                'margin_bottom' => 15,
            ]);

            $mpdf->SetHTMLFooter('<table width="100%" style="font-size: 9px; color: #64748b;"><tr><td width="50%">SIPAKAR RSUD Kardinah Kota Tegal</td><td width="50%" style="text-align: right;">Halaman {PAGENO} dari {nbpg}</td></tr></table>');
            $mpdf->WriteHTML($html);

            $filename = 'RBA_Usulan_' . \Str::slug($submission->unit->name ?? 'Unit') . '_' . ($submission->header->year ?? date('Y')) . '.pdf';
            $pdfContent = $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }

        // Fallback: If mPDF is loading/not instantiated, render HTML preview
        return view('reports.operator_rba_print', compact('submission', 'includeBackground', 'previousPagus'));
    }
}
