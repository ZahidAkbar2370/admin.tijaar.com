<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TijaarExpense;
use App\Support\UploadHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $query = TijaarExpense::query()->with('creator')->orderByDesc('expense_date')->orderByDesc('id');

        if ($request->filled('search')) {
            $q = trim((string) $request->search);
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category') && array_key_exists($request->category, TijaarExpense::CATEGORIES)) {
            $query->where('category', $request->category);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        $totalFiltered = (clone $query)->sum('amount');
        $expenses = $query->paginate(20)->withQueryString();

        return view('admin.expenses.index', [
            'expenses' => $expenses,
            'categories' => TijaarExpense::CATEGORIES,
            'totalFiltered' => (float) $totalFiltered,
        ]);
    }

    public function create(): View
    {
        return view('admin.expenses.create', [
            'categories' => TijaarExpense::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('proof_image')) {
            $data['proof_image'] = UploadHelper::storePublic($request->file('proof_image'), 'expenses/proof');
        }

        $data['created_by'] = auth()->id();

        TijaarExpense::create($data);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense recorded.');
    }

    public function show(TijaarExpense $expense): View
    {
        $expense->load('creator');

        return view('admin.expenses.show', compact('expense'));
    }

    public function edit(TijaarExpense $expense): View
    {
        return view('admin.expenses.edit', [
            'expense' => $expense,
            'categories' => TijaarExpense::CATEGORIES,
        ]);
    }

    public function update(Request $request, TijaarExpense $expense): RedirectResponse
    {
        $data = $this->validated($request, $expense);

        if ($request->boolean('remove_proof_image')) {
            UploadHelper::deletePublic($expense->proof_image);
            $data['proof_image'] = null;
        } elseif ($request->hasFile('proof_image')) {
            UploadHelper::deletePublic($expense->proof_image);
            $data['proof_image'] = UploadHelper::storePublic($request->file('proof_image'), 'expenses/proof');
        }

        $expense->update($data);

        return redirect()->route('admin.expenses.show', $expense)->with('success', 'Expense updated.');
    }

    public function destroy(TijaarExpense $expense): RedirectResponse
    {
        UploadHelper::deletePublic($expense->proof_image);
        $expense->delete();

        return redirect()->route('admin.expenses.index')->with('success', 'Expense deleted.');
    }

    private function validated(Request $request, ?TijaarExpense $expense = null): array
    {
        $categoryKeys = implode(',', array_keys(TijaarExpense::CATEGORIES));

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:' . $categoryKeys,
            'amount' => 'required|numeric|min:0.01|max:999999999.99',
            'expense_date' => 'nullable|date',
            'description' => 'nullable|string|max:5000',
            'proof_image' => ($expense ? 'nullable' : 'nullable') . '|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
            'remove_proof_image' => 'sometimes|boolean',
        ]);

        return [
            'title' => trim((string) $request->title),
            'category' => (string) $request->category,
            'amount' => round((float) $request->amount, 2),
            'expense_date' => $request->filled('expense_date') ? $request->expense_date : null,
            'description' => $request->filled('description') ? trim((string) $request->description) : null,
        ];
    }
}
