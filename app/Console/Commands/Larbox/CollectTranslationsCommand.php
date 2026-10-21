<?php

namespace App\Console\Commands\Larbox;

use App\Base\Model;
use App\Helpers\ReflectionHelper;
use App\Requests\ResourceFormRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class CollectTranslationsCommand extends Command
{
    protected $signature = 'larbox:collect_translations';

    protected $description = 'Collect translations';

    private array $languages;

    public function handle(): void
    {
        setUser(1);

        $this->languages = array_keys(config('larbox.languages'));

        $this->collectTextTranslations();
        $this->collectFieldTranslations();

        $this->info('Successfully done');
    }

    private function collectTextTranslations(): void
    {
        $folders = [
            'app',
            'http',
            'integrations',
            'modules',
        ];

        $newTranslations = [];

        foreach ($folders as $folder) {
            $files = File::allFiles(base_path($folder));

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') continue;

                $content = $file->getContents();

                if (preg_match_all("/__\('([^']+)'/", $content, $matches)) {
                    $newTranslations[] = $matches[1];
                }

                if (preg_match_all('/__\("((?:[^"\\\\]|\\\\.)+)"/', $content, $matches)) {
                    $newTranslations[] = array_map('stripcslashes', $matches[1]);
                }
            }
        }

        $newTranslations = Arr::collapse($newTranslations);
        $newTranslations = array_unique($newTranslations);
        $newTranslations = array_filter($newTranslations, function ($value) {
            return !str_starts_with($value, 'fields.')
                && !str_starts_with($value, 'validation.')
                && !str_starts_with($value, 'user_notifications.');
        });

        $newTranslations = array_combine($newTranslations, $newTranslations);

        foreach ($this->languages as $language) {
            $filePath = base_path("lang/$language.json");

            $oldTranslations = file_get_contents($filePath);
            $oldTranslations = json_decode($oldTranslations, true);

            $allTranslations = array_replace($newTranslations, $oldTranslations);
            ksort($allTranslations);

            file_put_contents($filePath, json_encode($allTranslations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }

    private function collectFieldTranslations(): void
    {
        $newModel = new Model();
        $newTranslations = [];

        $files = File::allFiles(base_path('http'));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;

            $content = $file->getContents();

            if (!preg_match("/class [A-Za-z0-9]+Request extends/", $content)) continue;

            $classReflection = ReflectionHelper::classByFile($file);

            $formRequest = $classReflection->newInstanceWithoutConstructor();

            if ($formRequest instanceof ResourceFormRequest) {
                $formRequest->model = $newModel;
            }

            $formRequest->__construct();

            $fields = array_values($formRequest->attributes());
            $fields = array_map(fn($value) => preg_replace('/^fields\./', '', $value), $fields);

            $newTranslations[] = $fields;
        }

        $newTranslations = Arr::collapse($newTranslations);
        $newTranslations = array_unique($newTranslations);

        $newTranslations = array_combine($newTranslations, $newTranslations);

        foreach ($this->languages as $language) {
            $filePath = base_path("lang/$language/fields.php");

            $oldTranslations = require $filePath;

            $allTranslations = array_replace($newTranslations, $oldTranslations);
            ksort($allTranslations);

            $allTranslations = implode("\n", [
                "<?php\n",
                'return [',
                ...Arr::map($allTranslations, fn($value, $key) => "    '$key' => '$value',"),
                "];\n",
            ]);

            file_put_contents($filePath, $allTranslations);
        }
    }
}
