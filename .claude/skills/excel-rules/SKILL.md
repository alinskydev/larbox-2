---
name: excel-rules
description: Mandatory standards for Excel export/import and PDF generation in app/Excel (Export/, Import/), modules/*/Excel, modules/*/PDF, App\Helpers\PdfHelper and their controller wiring via the `format` query param. Use when creating or editing an Excel export writer/job, an Excel import request/controller trait/example file, or a PDF view/download.
---

# Excel & PDF Rules

Base kit: `app/Excel/Export` (`ExcelExportHelper`, `ExcelExportJob`, `ExcelExportWriter`, on `avadim/fast-excel-writer`) and `app/Excel/Import` (`ExcelImportFormRequest`, `ExcelImportJob`, `ExcelImportHelper`, on `phpoffice/phpspreadsheet`). Module code: `modules/<Module>/Excel/Export/{Jobs,Writers}/`, `modules/<Module>/Excel/Import/Examples/<table>s.xlsx`, `modules/<Module>/PDF/<name>.php`.
Canonical (the only implementation): `modules/Product/Excel/Export/`, `http/Company/Product/Requests/Product/ProductExcelImportProcessRequest.php`, `http/Company/Product/Controllers/ProductController.php` + `Traits/Product/ProductControllerExcelImportTrait.php`, `modules/Product/Models/Product/ProductImport.php`.

## Export rules
- One writer and one job per entity: `<Entity>ExcelExportWriter extends ExcelExportWriter` (`Modules\<Module>\Excel\Export\Writers`) and `<Entity>ExcelExportJob extends ExcelExportJob` (`Modules\<Module>\Excel\Export\Jobs`).
- The job declares only `public static int $instantLimit` / `$totalLimit` and implements `createWriter(): ExcelExportWriter`: `<Model>::query()->with(['rel:id,col', ...])->whereRaw("id in ($this->sql)", $this->bindings)->getQueryWithRelations()->lazyByIdDesc(10000)` → `new <Entity>ExcelExportWriter($data)`. Never override the constructor, `handle()` or `handleSync()`.
- Controller: `index(): JsonResponse|StreamedResponse` with `switch (request()->query('format'))`; `case 'excel': return ExcelExportHelper::process(jobClass: <Entity>ExcelExportJob::class, query: $this->search->query);`; `default:` returns `parent::index()` (it may post-process the response, e.g. `ProductController` adds `summary`).
- `ExcelExportHelper::process()`:
  - count > `$totalLimit`: throws `\Exception` (`Нельзя экспортировать более :limit записей`, rendered as 400).
  - count ≤ `$instantLimit`: streams `<uuid>.xlsx` right away (`handleSync()` → `processAsStream()`).
  - otherwise: dispatches the job (queue `excel`) and `abort(202, __('Вы получите уведомление, как только файл будет готов'))`.
  - The job gets the ids subquery as SQL plus prepared bindings, never an id list.
- Queued result: `ExcelExportJob::handle()` saves `storage/larbox/_tmp/<uuid>.xlsx` and notifies `NotificationTypeEnum::EXCEL_EXPORT_COMPLETED` (`params.file`), or on error `fail()` + `Log::error()` + `EXCEL_EXPORT_FAILED` (`params.message`). Download: `GET common/user/notification/{model}/download` (`NotificationController::download()`, deletes the file after streaming). `app:process_db_daily` removes `_tmp` files older than 1 day and those notifications.
- Writer:
  - Set `protected int $maxColumn`.
  - In `beforeFilling()`: `$this->sumColumns`, `$this->formatGroups` (`'### ### ### ##0.00' => [5]`), `$this->sheet->setColWidth(col, width)`.
  - `fillHeaderCells()`: `$this->sheet->writeTo([col, 1], __('fields.<column>'))`. `fillBodyCells()`: `$this->sheet->writeRow(ExcelExportHelper::sanitizeRow([...]))` per record.
  - The base `process()` runs `beforeFilling` → `setStyles` (column formats, header style, freeze) → header → body → `Итого` footer with `=SUM()` for `$sumColumns` → `afterFilling`.
- `$this->data` is a `LazyCollection` of stdClass rows:
  - Relations are objects: `$record->brand->name`, `$record->price?->price`.
  - Translatable JSON comes as a string: `json_decode($record->category->name, true)[$locale] ?? ''`, `$locale = app()->getLocale()` once before the loop.
  - Dates: `date('d.m.Y H:i', strtotime($record->created_at))`.
- `afterFilling()`: `$this->tryToHideColumns(['<field>' => <colIndex>, ...])` (value may be a `[from => to]` range). Columns missing from `?excel_columns[]=` are hidden; the param is read from `request()` in the writer constructor, so it has no effect in queued exports.
- Writers work outside HTTP: `$writer = new <Entity>ExcelExportWriter($rows); $writer->process(); $writer->spreadsheet->save($path);` (`ProductMailingTelegramTemplate::process()`).

## Import rules
- Request: `<Entity>ExcelImportProcessRequest extends App\Excel\Import\ExcelImportFormRequest` in `http/<Area>/<Module>/Requests/<Entity>/`. Declare `protected array $excelColumns = [...]` (DB fields in file column order). `nonLocalizedRules()` returns only `'file' => ['required', ...ValidationFileRulesHelper::excel()]`.
- All parsing goes in `additionalValidation()`, starting with `$isCorrectExcel = $this->checkAndPrepareExcel(lastColumn: 'E', rowLimit: 10000); if (!$isCorrectExcel) return;`:
  - It checks the uncompressed zip size (100 MB), the row limit and the last column letter.
  - It fills `$this->fileContent` (tags escaped, header row 0 removed, so keys start at 1), `$this->excelSheetInfo` and `$this->excelColumnIndexes`.
- File-level errors: `$this->validator->errors()->add('file', __('...'))` + `return`. Duplicates: `array_diff_assoc($values, array_unique($values))`.
- Resolve names to ids with one bulk query per relation, never per row, scoped like the controller filter (`filterByNestedCompanies()`, `filterByOwnRecords(RolePermissionHelper::ROUTE_...)`): `select(['id', DB::raw('LOWER(name) as name')])` (or `LOWER(name->>'$locale')`) + `whereIn(DB::raw(...), $lowered)` + `->getQuery()->get()->pluck('id', 'name')`. An unknown name becomes `0` and fails `min:1` with `__('Выбранная запись не существует или была удалена')`.
- Per row (`foreach ($this->fileContent as $key => &$record)`):
  - `$record = array_map(fn($index) => StringHelper::normalizeSpaces($record[$index]), $this->excelColumnIndexes);`
  - `unset($this->fileContent[$key])` rows whose label is null; set `$record['id']` to the existing id or null.
  - `$this->validateExcelRow(record:, rules:, errorMessages:, row: $key + 1)`; `return` on `false` (it adds `Ошибка в ячейке ":cell"` to `file`).
- Limits for the whole file: `(new SubscriptionCheckLimitObserver())->creating(model: new <Model>(['company_id' => user()->company_id]), additionalCount: ...)` before the loop and `(new DailyLimitCreateObserver())->creating(model: ..., decreaseValue: $newRecordCount)` after it.
- Import model `<Entity>Import extends <Entity>` in `modules/<Module>/Models/<Entity>/`: empty `bootRecordActionModelTrait()` and `booted()` re-declaring observers without DailyLimit/Subscription. Use it for the request's queries and as `modelClass`.
- Controller trait `<Entity>ControllerExcelImportTrait` (`http/<Area>/<Module>/Controllers/Traits/<Entity>/`, `@mixin Controller`):
  - `excelImportExample(): StreamedResponse` → `ExcelImportHelper::downloadExample('modules/<Module>/Excel/Import/Examples/<table>s.xlsx')`.
  - `excelImportProcess(<Request> $request): JsonResponse` → `array_chunk($request->fileContent, 1000)`, one `dispatch(new ExcelImportJob(modelClass: <Entity>Import::class, records: $records, recordLabel: 'name', isLast: array_key_last($recordGroups) === $key))` per chunk, then `$this->successResponse()`.
- `ExcelImportJob` (queue `excel`): loads existing ids `withTrashed()`, `safelySave()` per record (update by `id`, trashed not restored; create when `id` is null). Per-record errors → `EXCEL_IMPORT_FAILED` `params.records` (`label => message`); a whole-job error → `fail()` + `EXCEL_IMPORT_FAILED` `params.message`. In `finally`, on `isLast` it clears the lock and sends `EXCEL_IMPORT_COMPLETED`.
- Lock is per user: `user.is_excel_import_running`. The request constructor throws `\Exception('У вас есть активный процесс по импорту')` when set, `passedValidation()` sets it (and fails on empty `fileContent`), the last job clears it. Don't touch it elsewhere.
- Routes: inside the entity prefix, before `Route::apiResource('', ...)`: `Route::prefix('excel-import')` with `get('example')->name('example')` and `post('process')->name('process')`.
- Example file: row 1 holds field keys, ` *` marks required (`name *`). `downloadExample()` translates the first word via `__('fields.<key>')` and streams `<uuid>.xlsx`.
- Tests: `Tests/<Entity>/ExcelImport/{_TestCase,ExampleTest,ProcessTest}.php`, upload via `UploadedFile::fake()->createWithContent('example.xlsx', file_get_contents(base_path('<example>')))`. Export: `IndexTest::test_format_excel` (`format`, `excel_columns`); PDF: `ShowTest::test_format_pdf`.

## PDF rules
- `App\Helpers\PdfHelper::download(path: 'modules/<Module>/PDF', name: '<view>', params: [...])` streams `<uuid>.pdf` via `ControllerStreamHelper::download()`. `PdfHelper::create()` returns the rendered `Dompdf` (view namespace `pdf_<md5(path)>`, font `DejaVu Sans`, JS disabled).
- View: plain PHP `modules/<Module>/PDF/<name>.php` (`modules/Product/PDF/product.php`): `use` imports, `/** @var <Model> $var */`, `$locale = app()->getLocale();`, then a full HTML document with `<?= ?>`. Translatable: `$product->category->name[$locale] ?? ''`.
- Controller: `show(int $id): JsonResponse|StreamedResponse` → `match (request()->query('format')) { 'pdf' => PdfHelper::download(..., params: ['<model>' => $this->search->query->findOrFail($id)]), default => parent::show($id) }`.

## Template
```php
<?php

namespace Modules\<Module>\Excel\Export\Jobs;

use App\Excel\Export\ExcelExportJob;
use App\Excel\Export\ExcelExportWriter;
use Modules\<Module>\Excel\Export\Writers\<Entity>ExcelExportWriter;
use Modules\<Module>\Models\<Entity>;

class <Entity>ExcelExportJob extends ExcelExportJob
{
    public static int $instantLimit = 50000;
    public static int $totalLimit = 500000;

    public function createWriter(): ExcelExportWriter
    {
        $data = <Entity>::query()
            ->with([
                'brand:id,name',
            ])
            ->whereRaw("id in ($this->sql)", $this->bindings)
            ->getQueryWithRelations()
            ->lazyByIdDesc(10000);

        return new <Entity>ExcelExportWriter($data);
    }
}
```
The writer follows `ProductExcelExportWriter`: `$maxColumn`, `beforeFilling()`, `fillHeaderCells()`, `fillBodyCells()` with `sanitizeRow()`, `afterFilling()` with `tryToHideColumns()`.

## Don't
- Don't write a custom export `handle()`, file storage or delivery; `ExcelExportJob` and the notification download cover them.
- Don't pass id arrays or models into export jobs; only `ExcelExportHelper::process()` builds the job.
- Don't save imported rows in the request or controller; use `ExcelImportJob` with the `<Entity>Import` model.
- Don't query per row in an import request, or resolve names without the access scope.
- Don't write export files with phpspreadsheet; it is used only in `app/Excel/Import` (reading, `downloadExample()`) and for `Coordinate`.
- Don't skip `sanitizeRow()`, and don't hardcode header strings; use `__('fields.<column>')`.
- Don't use Blade or `resources/views` for PDF (`resources/views` is empty).
