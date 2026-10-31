<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use GazaDelivery\Foundation\Enums\StoreStatus;
use GazaDelivery\Foundation\Models\Store;
use GazaDelivery\Foundation\Services\StoreDirectory;
use GazaDelivery\Foundation\Services\StoreReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

final class StoreController extends Controller
{
    public function index(Request $request, StoreDirectory $directory)
    {
        $data = $request->validate(['status' => ['nullable', Rule::enum(StoreStatus::class)], 'q' => ['nullable', 'string', 'max:100']]);
        $query = $directory->queryFor($request->user('staff'))->with('owner:id,name,phone');
        $this->filters($query, $data);

        return view('admin.stores.index', [
            'stores' => $query->latest('id')->paginate(10)->withQueryString(),
            'filters' => $data,
        ]);
    }

    public function data(Request $request, StoreDirectory $directory)
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::enum(StoreStatus::class)],
            'length' => ['required', 'integer', Rule::in([10, 25, 50])],
            'start' => ['required', 'integer', 'min:0', 'max:100000'],
            'draw' => ['required', 'integer', 'min:0'],
            'search.value' => ['nullable', 'string', 'max:100'],
            'search.regex' => ['nullable', Rule::in(['false', false, '0', 0])],
            'order' => ['nullable', 'array', 'max:1'],
            'order.*.column' => ['required', 'integer', 'min:0', 'max:2'],
            'order.*.dir' => ['required', Rule::in(['asc', 'desc'])],
        ]);
        // The browser never chooses database column names or searchable relationships.
        $request->merge(['columns' => [
            ['data' => 'name', 'name' => 'name', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'status_label', 'name' => 'status', 'searchable' => 'false', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'created_at', 'name' => 'created_at', 'searchable' => 'false', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ['data' => 'details_url', 'name' => '', 'searchable' => 'false', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']],
        ]]);
        $query = $directory->queryFor($request->user('staff'))->select('stores.*');
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        return DataTables::eloquent($query)
            ->escapeColumns([])
            ->whitelist(['name', 'status', 'created_at'])
            ->addColumn('status_label', fn (Store $store) => config('delivery.status_labels.'.$store->status->value))
            ->addColumn('details_url', fn (Store $store) => route('admin.stores.show', $store))
            ->editColumn('created_at', fn (Store $store) => $store->created_at->timezone('Asia/Hebron')->format('Y/m/d'))
            ->only(['public_id', 'name', 'status_label', 'created_at', 'details_url'])
            ->toJson();
    }

    public function show(Request $request, string $publicId, StoreDirectory $directory)
    {
        $store = $directory->findFor($request->user('staff'), $publicId)->load('owner', 'reviewer');

        return view('admin.stores.show', compact('store'));
    }

    public function update(Request $request, string $publicId, StoreDirectory $directory, StoreReview $review)
    {
        $store = $directory->findFor($request->user('staff'), $publicId);
        Gate::authorize('review', $store);
        $data = $request->validate(['status' => ['required', Rule::enum(StoreStatus::class)], 'reason' => ['required', 'string', 'min:3', 'max:500']]);
        $review->transition($request->user('staff'), $store, StoreStatus::from($data['status']), $data['reason']);

        return redirect()->route('admin.stores.show', $store)->with('status', 'تم تحديث حالة المتجر وتسجيل القرار.');
    }

    private function filters($query, array $data): void
    {
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (! empty($data['q'])) {
            $query->where('name', 'like', '%'.$data['q'].'%');
        }
    }
}
