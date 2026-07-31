<?php

namespace App\Http\Controllers;

use App\Enums\Unit;
use App\Http\Controllers\Concerns\DrawerForms;
use App\Models\Article;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    use DrawerForms;

    public function index(Request $request)
    {
        return view('articles.index', [
            'articles' => Article::when($request->string('q')->toString(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function create(Request $request)
    {
        return $this->formView($request, 'articles.form-fields', 'articles.form', ['article' => null]);
    }

    public function store(Request $request)
    {
        Article::create($this->validated($request));

        return $this->saved($request, 'articles.index', 'Artikl je kreiran.');
    }

    public function edit(Request $request, Article $article)
    {
        return $this->formView($request, 'articles.form-fields', 'articles.form', ['article' => $article]);
    }

    public function update(Request $request, Article $article)
    {
        $article->update($this->validated($request));

        return $this->saved($request, 'articles.index', 'Izmjene su sačuvane.');
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return redirect()->route('articles.index')->with('status', 'Artikl je obrisan.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit' => ['required', Rule::enum(Unit::class)],
            'tax_label' => ['nullable', Rule::in(array_keys(TaxRate::basisPointsByLabel()))],
            'gtin' => ['nullable', 'string', 'min:8', 'max:14'],
            'last_unit_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'naziv', 'description' => 'opis', 'unit' => 'jedinica mjere',
            'tax_label' => 'poreska oznaka', 'gtin' => 'GTIN', 'last_unit_price' => 'cijena',
        ]);

        $data['last_unit_price'] = $data['last_unit_price'] !== null
            ? (int) round(((float) $data['last_unit_price']) * 100)
            : null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
