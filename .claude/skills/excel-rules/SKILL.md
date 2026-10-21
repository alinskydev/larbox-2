---
name: excel-rules
description: Mandatory standards for Excel export/import and PDF generation in app/Excel (Export/, Import/), modules/*/Excel, modules/*/PDF, App\Helpers\PdfHelper and their controller wiring via the `format` query param. Use when creating or editing an Excel export writer/job, an Excel import request/controller trait/example file, or a PDF view/download.
---

# Excel & PDF Rules

Base kit: `app/Excel/Export` (`ExcelExportHelper`, `ExcelExportJob`, `ExcelExportWriter`, on `avadim/fast-excel-writer`) and `app/Excel/Import` (`ExcelImportFormRequest`, `ExcelImportJob`, `ExcelImportHelper`, on `phpoffice/phpspreadsheet`). Module code: `modules/<Module>/Excel/Export/{Jobs,Writers}/`, `modules/<Module>/Excel/Import/Examples/<table>s.xlsx`, `modules/<Module>/PDF/<name>.php`.
Canonical: `modules/Product/Excel/Export/Writers/ProductExcelExportWriter.php`, `http/Company/Product/Requests/Product/ProductExcelImportProcessRequest.php`, `http/Company/Product/Controllers/ProductController.php`.

## Export rules
- Each entity gets one writer and one job: `<Entity>ExcelExportWriter extends ExcelExportWriter` (`Modules\<Module>\Excel\Export\Writers`) and `<Entity>ExcelExportJob extends ExcelExportJob` (`Modules\<Module>\Excel\Export\Jobs`).
- The job declares only `public static int $instantLimit` and `$totalLimit` and implements `createWriter(): ExcelExportWriter`. It runs `<Model>::query()->with(['rel:id,col', ...])->whereRaw("id in ($this->sql)", $this->bindings)->getQueryWithRelations()->lazyByIdDesc(10000)` and returns `new <Entity>ExcelExportWriter($data)` (`ProductExcelExportJob`). Never override the constructor or `handle()`.
- Controller: `index(): JsonResponse|StreamedResponse` does `switch (request()->query('format'))`. `case 'excel': return ExcelExportHelper::process(jobClass: <Entity>ExcelExportJob::class, query: $this->search->query);`, and `default:` falls through to `parent::index()`.
- `ExcelExportHelper::process()`:
  - count > `$totalLimit`: throws `\Exception` (rendered as 400).
  - count ≤ `$instantLimit`: streams the `.xlsx` right away (`handleSync()` → `processAsStream()`).
  - otherwise: dispatches the job on queue `excel` and calls `abort(202, __('Вы получите уведомление, как только файл будет готов'))`.
  - The job receives the ids subquery as SQL plus bindings, never an id list.
- Queued result: `ExcelExportJob::handle()` saves `storage/larbox/_tmp/<uuid>.xlsx` and notifies `NotificationTypeEnum::EXCEL_EXPORT_COMPLETED` (`params.file`) or `EXCEL_EXPORT_FAILED`. The user downloads it via `GET common/user/notification/{model}/download` (`NotificationController::download()`, which deletes the file). `app:process_db_daily` cleans `_tmp` and those notifications.
- Writer:
  - Set `protected int $maxColumn`.
  - In `beforeFilling()`, set `$this->sumColumns`, `$this->formatGroups` (`'### ### ### ##0.00' => [5]`) and `$this->sheet->setColWidth(col, width)`.
  - Implement `fillHeaderCells()` (`$this->sheet->writeTo([col, 1], __('fields.<column>'))`) and `fillBodyCells()` (`$this->sheet->writeRow(ExcelExportHelper::sanitizeRow([...]))` per record).
  - The base adds the header style, the freeze and the `Итого` footer with `=SUM()` for `$sumColumns`.
- `$this->data` is a `LazyCollection` of stdClass rows:
  - Relations are objects: `$record->brand->name`, `$record->price?->price`.
  - Translatable JSON comes as a string: `json_decode($record->category->name, true)[$locale] ?? ''`, with `$locale = app()->getLocale()` set once before the loop.
  - Dates: `date('d.m.Y H:i', strtotime($record->created_at))`.
- In `afterFilling()`, call `$this->tryToHideColumns(['<field>' => <colIndex>, ...])`. A value can also be a `[from => to]` range. Columns missing from `?excel_columns[]=` are hidden. The param is read from `request()` in the writer constructor, so it has no effect in queued exports.
- Writers can be used outside HTTP: `$writer = new <Entity>ExcelExportWriter($rows); $writer->process(); $writer->spreadsheet->save($path);` (`ProductMailingTelegramTemplate::process()`).

## Import rules
- The request is `<Entity>ExcelImportProcessRequest extends App\Excel\Import\ExcelImportFormRequest`, in `http/<Area>/<Module>/Requests/<Entity>/`. Declare `protected array $excelColumns = [...]`, the DB fields in file column order. `nonLocalizedRules()` returns only `'file' => ['required', ...ValidationFileRulesHelper::excel()]`.
- All parsing goes in `additionalValidation()` and starts with `$isCorrectExcel = $this->checkAndPrepareExcel(lastColumn: 'E', rowLimit: 10000)` + `if (!$isCorrectExcel) return;`:
  - It checks the uncompressed size, the row limit and the last column.
  - It fills `$this->fileContent` (tags escaped, header row removed), `$this->excelSheetInfo` and `$this->excelColumnIndexes`.
- File-level errors: `$this->validator->errors()->add('file', __('...'))`, then `return`. Find duplicates with `array_diff_assoc($values, array_unique($values))`.
- Resolve names to ids with one bulk query per relation, never one per row: `select(['id', DB::raw('LOWER(name) as name')])` (or `LOWER(name->>'$locale')`) + `whereIn(DB::raw(...), $lowered)` + `->getQuery()->get()->pluck('id', 'name')`. An unknown name becomes `0` and fails a `min:1` rule with `__('Выбранная запись не существует или была удалена')`.
- For each row:
  - Build the record with `array_map(fn($index) => StringHelper::normalizeSpaces($record[$index]), $this->excelColumnIndexes)`.
  - `unset` rows whose label is null.
  - Set `$record['id']` to the existing id, or null.
  - Call `$this->validateExcelRow(record:, rules:, errorMessages:, row: $key + 1)` and `return` on `false`.
- Check limits for the whole file: `(new SubscriptionCheckLimitObserver())->creating(model: new <Model>(['company_id' => user()->company_id]), additionalCount: ...)` and `(new DailyLimitCreateObserver())->creating(model: ..., decreaseValue: $newRecordCount)`.
- Import model: `<Entity>Import extends <Entity>` in `modules/<Module>/Models/<Entity>/` (`ProductImport`). It overrides `bootRecordActionModelTrait()` with an empty body and re-declares `booted()` observers without the DailyLimit and Subscription observers. Use it for the request's queries and as `modelClass`.
- Controller trait `<Entity>ControllerExcelImportTrait` (`http/<Area>/<Module>/Controllers/Traits/<Entity>/`, `@mixin Controller`):
  - `excelImportExample()` returns `ExcelImportHelper::downloadExample('modules/<Module>/Excel/Import/Examples/<table>s.xlsx')`.
  - `excelImportProcess(<Request> $request)` splits with `array_chunk($request->fileContent, 1000)`, then `dispatch(new ExcelImportJob(modelClass: <Entity>Import::class, records: $records, recordLabel: 'name', isLast: array_key_last($recordGroups) === $key))` per chunk, then returns `$this->successResponse()`.
- `ExcelImportJob` (queue `excel`):
  - Calls `safelySave()` per record. With an `id` it updates the record, trashed ones included. With a null `id` it creates one.
  - Collects per-record errors into `EXCEL_IMPORT_FAILED`.
  - On `isLast` it resets the lock and sends `EXCEL_IMPORT_COMPLETED`.
- Lock: `company.is_excel_import_running`. The request constructor checks it, `passedValidation()` sets it and the last job clears it. Don't touch it anywhere else.
- Routes: inside the entity prefix, before `Route::apiResource('', ...)`, add `Route::prefix('excel-import')` with `get('example')->name('example')` and `post('process')->name('process')`.
- Example file: row 1 holds field keys, with an optional ` *` marking required ones (`name *`, `description`). `downloadExample()` translates the first word via `__('fields.<key>')`.
- Tests: `Tests/<Entity>/ExcelImport/{ExampleTest,ProcessTest}.php`. They upload via `UploadedFile::fake()->createWithContent('example.xlsx', file_get_contents(base_path('<example>')))`. Export is covered by `IndexTest::test_format_excel` (query `format`, `excel_columns`).

## PDF rules
- `App\Helpers\PdfHelper::download(path: 'modules/<Module>/PDF', name: '<view>', params: [...])` streams `<uuid>.pdf`. `PdfHelper::create()` returns the rendered `Dompdf` (default font `DejaVu Sans`, JS disabled).
- The view is a plain PHP file `modules/<Module>/PDF/<name>.php` (`modules/Product/PDF/product.php`):
  - PHP header: `use` imports, `/** @var <Model> $var */` and `$locale = app()->getLocale();`.
  - Body: a full HTML document with `<?= ?>` output. Translatable fields: `$product->category->name[$locale] ?? ''`.
- Controller: `show(int $id): JsonResponse|StreamedResponse` returns `match (request()->query('format')) { 'pdf' => PdfHelper::download(..., params: ['<model>' => $this->search->query->findOrFail($id)]), default => parent::show($id) }`. Covered by `ShowTest::test_format_pdf`.

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
            ->with(['brand:id,name'])
            ->whereRaw("id in ($this->sql)", $this->bindings)
            ->getQueryWithRelations()
            ->lazyByIdDesc(10000);

        return new <Entity>ExcelExportWriter($data);
    }
}
```
The writer follows `ProductExcelExportWriter`: `$maxColumn`, then `beforeFilling()`, `fillHeaderCells()`, `fillBodyCells()` with `sanitizeRow()`, and `afterFilling()` with `tryToHideColumns()`.

## Don't
- Don't write a custom export `handle()`, file storage or delivery. `ExcelExportJob` and the notification download cover them.
- Don't pass id arrays or models into export jobs. Only `ExcelExportHelper::process()` builds the job.
- Don't save imported rows in the request or controller. Use `ExcelImportJob` with the `<Entity>Import` model.
- Don't write export files with phpspreadsheet. It is used only for import reading, `downloadExample()` and `Coordinate`.
- Don't skip `sanitizeRow()`, and don't hardcode header strings. Use `__('fields.<column>')`.
- Don't use Blade or `resources/views` for PDF (the directory doesn't exist).
