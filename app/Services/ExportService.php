<?php

namespace App\Services;

use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * ExportService
 *
 * Base service for exporting data to PDF and Excel
 */
class ExportService
{
    /**
     * Generate PDF from view
     *
     * @param string $view View name
     * @param array $data Data to pass to view
     * @param string $filename Output filename
     * @param array $options PDF options
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePDF(string $view, array $data, string $filename, array $options = [])
    {
        $defaultOptions = [
            'format' => 'A4',
            'orientation' => 'portrait',
            'margin_top' => 10,
            'margin_right' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
        ];

        $options = array_merge($defaultOptions, $options);

        $pdf = Pdf::loadView($view, $data)
            ->setPaper($options['format'], $options['orientation'])
            ->setOption('margin-top', $options['margin_top'])
            ->setOption('margin-right', $options['margin_right'])
            ->setOption('margin-bottom', $options['margin_bottom'])
            ->setOption('margin-left', $options['margin_left']);

        return $pdf;
    }

    /**
     * Download PDF
     */
    public function downloadPDF(string $view, array $data, string $filename, array $options = [])
    {
        $pdf = $this->generatePDF($view, $data, $filename, $options);
        return $pdf->download($filename);
    }

    /**
     * Stream PDF (view in browser)
     */
    public function streamPDF(string $view, array $data, string $filename, array $options = [])
    {
        $pdf = $this->generatePDF($view, $data, $filename, $options);
        return $pdf->stream($filename);
    }

    /**
     * Generate Excel from array data
     *
     * @param array $data Array of arrays (rows)
     * @param array $headers Column headers
     * @param string $filename Output filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function generateExcel(array $data, array $headers, string $filename)
    {
        $callback = function() use ($data, $headers) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel to recognize UTF-8 encoding
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write headers
            fputcsv($file, $headers);

            // Write data rows
            foreach ($data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Format date for display
     */
    protected function formatDate($date, string $format = 'd.m.Y'): string
    {
        if (!$date) return '-';

        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }

        return $date->format($format);
    }

    /**
     * Format datetime for display
     */
    protected function formatDateTime($datetime, string $format = 'd.m.Y H:i'): string
    {
        if (!$datetime) return '-';

        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime);
        }

        return $datetime->format($format);
    }

    /**
     * Format number
     */
    protected function formatNumber($number, int $decimals = 2): string
    {
        if ($number === null) return '-';
        return number_format($number, $decimals, '.', ' ');
    }

    /**
     * Get current timestamp for filename
     */
    protected function getTimestamp(): string
    {
        return date('Y-m-d_His');
    }
}
