<?php

namespace App\Http\Controllers;

use App\Services\FiscalDeviceHealth;
use App\Services\FiscalInvoiceImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/** Uvoz izdatih računa sa fiskalne kase u aplikaciju. */
class FiscalImportController extends Controller
{
    public function __construct(
        private FiscalInvoiceImporter $importer,
        private FiscalDeviceHealth $health,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        return $this->run(fn (): array => $this->importer->search($data['from'], $data['to']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numbers' => ['required', 'array', 'min:1'],
            'numbers.*' => ['required', 'string'],
            'use_fiscal_numbers' => ['boolean'],
        ]);

        return $this->run(fn (): array => $this->importer->import(
            $data['numbers'],
            (bool) ($data['use_fiscal_numbers'] ?? false),
        ));
    }

    /** @param  callable(): array<string, mixed>  $action */
    private function run(callable $action): JsonResponse
    {
        try {
            $result = $action();
            $this->health->markReady();
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);
            $this->health->markUnavailable();

            return response()->json(['message' => 'Kasa trenutno nije dostupna. Pokušajte ponovo.'], 500);
        }

        return response()->json($result);
    }
}
