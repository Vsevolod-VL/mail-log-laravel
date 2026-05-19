<?php

declare(strict_types=1);

namespace Phattarachai\MailLogLaravel\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Phattarachai\MailLogLaravel\Enums\MailLogStatus;
use Phattarachai\MailLogLaravel\Models\MailLog;
use Phattarachai\MailLogLaravel\Models\MailLogGroup;

class GroupController
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'has_failures' => $request->boolean('has_failures'),
        ];

        $query = MailLogGroup::query()->orderByDesc('updated_at');

        if ($filters['search'] !== '') {
            $term = '%'.$filters['search'].'%';
            $eventsTable = (new MailLog)->getTable();
            $groupsTable = (new MailLogGroup)->getTable();

            $query->where(function ($q) use ($term, $eventsTable, $groupsTable) {
                $q->where('subject', 'like', $term)
                    ->orWhere('mailable_class', 'like', $term)
                    ->orWhere('notification_class', 'like', $term)
                    ->orWhereExists(function ($sub) use ($term, $eventsTable, $groupsTable) {
                        $sub->select('id')
                            ->from($eventsTable)
                            ->whereColumn('group_id', $groupsTable.'.id')
                            ->where('to', 'like', $term);
                    });
            });
        }

        $statusEnum = MailLogStatus::tryFrom($filters['status']);
        if ($statusEnum !== null) {
            $query->where('latest_status', $statusEnum->value);
        }

        if ($filters['has_failures']) {
            $query->where('failed_count', '>', 0);
        }

        $groups = $query
            ->with(['latestEvent'])
            ->paginate((int) config('mail-log.ui.page_size', 25))
            ->withQueryString();

        $stats = [
            'groups' => MailLogGroup::query()->count(),
            'sends' => (int) (MailLogGroup::query()->sum('sent_count') + MailLogGroup::query()->sum('failed_count')),
        ];

        return view('mail-log::index', [
            'groups' => $groups,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, MailLogGroup $group): View
    {
        $group->load('media');

        $events = $group->events()
            ->paginate((int) config('mail-log.ui.page_size', 25))
            ->withQueryString();

        return view('mail-log::show', [
            'group' => $group,
            'events' => $events,
        ]);
    }

    public function destroy(Request $request, MailLogGroup $group): RedirectResponse
    {
        $group->delete();

        return redirect()
            ->route('mail-log.index')
            ->with('mail-log:flash', 'ลบกลุ่มและการส่งทั้งหมดในกลุ่มแล้ว');
    }
}
