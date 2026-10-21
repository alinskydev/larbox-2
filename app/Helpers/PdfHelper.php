<?php

namespace App\Helpers;

use App\Helpers\Controller\ControllerStreamHelper;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfHelper
{
    public static function create(string $path, string $name, array $params = []): Dompdf
    {
        $viewNamespace = 'pdf_' . md5($path);

        View::replaceNamespace($viewNamespace, base_path($path));
        $html = view("$viewNamespace::$name", $params);

        $options = new Options();
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsJavascriptEnabled(false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf;
    }

    public static function download(string $path, string $name, array $params = []): StreamedResponse
    {
        $pdf = static::create($path, $name, $params);

        return ControllerStreamHelper::download(
            name: StringHelper::uuid() . '.pdf',
            content: $pdf->output(),
        );
    }
}
