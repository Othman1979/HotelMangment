<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Enums\TransactionType;
use App\Models\GuestAccount;
use App\Models\GuestTransaction;
use App\Models\HotelSetting;
use App\Models\NightAudit;
use App\Models\OutletCheck;
use App\Models\Payment;
use App\Models\ReservationRoom;
use App\Models\TransactionCode;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const REPORTS = ['revenue', 'payments', 'occupancy', 'balances', 'outlets', 'in-house'];

    public function index()
    {
        return view('reports.index', ['reports' => self::REPORTS]);
    }

    public function show(Request $request, string $report)
    {
        abort_unless(in_array($report, self::REPORTS, true), 404);
        $today = HotelSetting::businessDate();
        $from = $request->filled('from') ? $request->date('from') : $today->startOfMonth();
        $to = $request->filled('to') ? $request->date('to') : $today;
        [$columns, $rows, $totals] = $this->{str_replace('-', '', $report)}($from->toDateString(), $to->toDateString());

        if ($request->input('export') === 'csv') {
            return $this->csv($report, $columns, $rows);
        }

        return view('reports.show', compact('report', 'from', 'to', 'columns', 'rows', 'totals'));
    }

    private function revenue(string $from, string $to): array
    {
        $rows = GuestTransaction::query()
            ->join('transaction_codes as c', 'c.id', '=', 'guest_transactions.transaction_code_id')
            ->whereBetween('business_date', [$from, $to])
            ->where('c.type', '!=', TransactionType::Payment->value)->where('c.code', '!=', TransactionCode::TRANSFER)
            ->groupBy('c.code', 'c.name_ar', 'c.name_en', 'c.revenue_group')
            ->selectRaw('c.code, c.name_ar, c.name_en, c.revenue_group, SUM(amount) net, SUM(service_amount) service, SUM(tax_amount) tax, SUM(amount + service_amount + tax_amount) total')
            ->orderBy('c.code')->get()
            ->map(fn ($r) => [$r->code, app()->getLocale() === 'ar' ? $r->name_ar : $r->name_en, __($r->revenue_group ?? '-'), $r->net, $r->service, $r->tax, $r->total]);

        return [['Code', 'Description', 'Revenue group', 'Net', 'Service', 'Tax', 'Total'], $rows, [3 => $rows->sum(3), 4 => $rows->sum(4), 5 => $rows->sum(5), 6 => $rows->sum(6)]];
    }

    private function payments(string $from, string $to): array
    {
        $rows = Payment::query()->with(['method', 'account', 'user', 'voucher'])->whereBetween('business_date', [$from, $to])->orderBy('id')->get()
            ->map(fn ($p) => [$p->business_date->toDateString(), $p->voucher?->voucher_no, $p->account->account_no.' '.$p->account->name, $p->method->name(), $p->reference, $p->user->name, $p->amount]);

        return [['Date', 'Voucher', 'Account', 'Method', 'Reference', 'User', 'Amount'], $rows, [6 => $rows->sum(6)]];
    }

    private function occupancy(string $from, string $to): array
    {
        $rows = NightAudit::query()->whereBetween('business_date', [$from, $to])->orderBy('business_date')->get()
            ->map(fn ($a) => [$a->business_date->toDateString(), $a->rooms_total, $a->rooms_occupied, $a->rooms_out_of_order, $a->occupancyPercent().'%', $a->adr(), $a->revpar(), $a->room_revenue, $a->other_revenue, $a->no_shows]);

        return [['Date', 'Rooms', 'Occupied', 'Out of order', 'Occupancy', 'ADR', 'RevPAR', 'Room revenue', 'Other revenue', 'No-shows'], $rows, [2 => $rows->sum(2), 7 => $rows->sum(7), 8 => $rows->sum(8), 9 => $rows->sum(9)]];
    }

    private function balances(string $from, string $to): array
    {
        $rows = GuestAccount::query()->with('stay.room')->where('status', 'open')->where('balance', '!=', 0)->orderBy('type')->orderBy('account_no')->get()
            ->map(fn ($a) => [$a->account_no, $a->type->label(), $a->name, $a->stay?->room?->room_number, $a->balance]);

        return [['Account', 'Type', 'Name', 'Room', 'Balance'], $rows, [4 => $rows->sum(4)]];
    }

    private function outlets(string $from, string $to): array
    {
        $rows = OutletCheck::query()->with(['outlet', 'account.stay.room', 'method'])->whereBetween('business_date', [$from, $to])->orderBy('id')->get()
            ->map(fn ($c) => [$c->business_date->toDateString(), $c->check_no, $c->outlet->name(), $c->settlement === 'room' ? __('Room').' '.$c->account?->stay?->room?->room_number : $c->method?->name(), $c->subtotal, (float) $c->service_amount + (float) $c->tax_amount, $c->total]);

        return [['Date', 'Check', 'Outlet', 'Settlement', 'Subtotal', 'Service + tax', 'Total'], $rows, [4 => $rows->sum(4), 5 => $rows->sum(5), 6 => $rows->sum(6)]];
    }

    private function inhouse(string $from, string $to): array
    {
        $rows = ReservationRoom::query()->with(['room', 'reservation.guest', 'reservation.company', 'account'])->where('status', ReservationStatus::CheckedIn->value)->get()
            ->sortBy(fn ($s) => $s->room->room_number)->values()
            ->map(fn ($s) => [$s->room->room_number, $s->reservation->guest->full_name, $s->reservation->company?->name, $s->arrival_date->toDateString(), $s->departure_date->toDateString(), $s->adults, $s->nightly_rate, $s->account?->balance]);

        return [['Room', 'Guest', 'Company', 'Arrival', 'Departure', 'Adults', 'Rate', 'Balance'], $rows, [7 => $rows->sum(7)]];
    }

    private function csv(string $report, array $columns, $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(fn ($c) => __($c), $columns));
            foreach ($rows as $row) {
                fputcsv($out, array_values($row));
            }
            fclose($out);
        }, $report.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
