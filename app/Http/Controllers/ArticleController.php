<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        return view('articles.index', [
            'articles' => Article::when($request->string('q')->toString(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'q' => $request->string('q')->toString(),
            'taxRates' => TaxRate::query()->pluck('rate', 'label')->all(),
        ]);
    }

    public function create(): View
    {
        return view('articles.form', $this->formData());
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        Article::create($this->priceInMinorUnits($request->validated()));

        return redirect()->route('articles.index')->with('status', 'Artikl je kreiran.');
    }

    public function edit(Article $article): View
    {
        return view('articles.form', $this->formData($article));
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $article->update($this->priceInMinorUnits($request->validated()));

        return redirect()->route('articles.index')->with('status', 'Izmjene su sačuvane.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('articles.index')->with('status', 'Artikl je obrisan.');
    }

    private function priceInMinorUnits(array $data): array
    {
        $data['last_unit_price'] = ($data['last_unit_price'] ?? null) !== null
            ? (int) round(((float) $data['last_unit_price']) * 100)
            : null;

        return $data;
    }

    /** @return array{article: ?Article, taxRateOptions: array<string, string>} */
    private function formData(?Article $article = null): array
    {
        return [
            'article' => $article,
            'taxRateOptions' => ['' => '—'] + TaxRate::query()
                ->orderBy('label')
                ->get(['label', 'rate'])
                ->mapWithKeys(fn (TaxRate $taxRate): array => [
                    $taxRate->label => $taxRate->label.' — '.$taxRate->rate.'%',
                ])
                ->all(),
        ];
    }
}
