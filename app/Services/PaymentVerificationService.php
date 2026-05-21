<?php

namespace App\Services;

use App\Models\GatewayPayment;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class PaymentVerificationService
{
    /**
     * @return array{
     *     auto_check_status: string,
     *     auto_check_score: int,
     *     auto_check_result: array<string, mixed>,
     *     ocr_text: string,
     *     auto_checked_at: \Illuminate\Support\Carbon
     * }
     */
    public function verify(GatewayPayment $payment, string $absoluteImagePath): array
    {
        $payment->loadMissing('order');
        $order = $payment->order;

        $ocr = $this->extractText($absoluteImagePath);
        $text = $ocr['text'];
        $normalizedText = $this->normalizeText($text);

        $amountMatched = $this->amountMatched((float) $payment->amount, $normalizedText);
        $score = $amountMatched ? 100 : 0;

        $status = $amountMatched && $ocr['available'] ? 'auto_verified' : 'auto_failed';
        $reasons = [];

        if (!$ocr['available']) {
            $reasons[] = $ocr['error'] ?: 'OCR extraction failed.';
            $score = 0;
            $status = 'auto_failed';
        }

        if (!$amountMatched) {
            $reasons[] = 'Amount does not match.';
        }

        return [
            'auto_check_status' => $status,
            'auto_check_score' => $score,
            'auto_check_result' => [
                'amount_match' => $amountMatched,
                'account_match' => null,
                'account_number_match' => null,
                'account_name_match' => null,
                'success_text_found' => null,
                'reference_found' => null,
                'date_match' => null,
                'ocr_available' => $ocr['available'],
                'ocr_error' => $ocr['error'],
                'detected_amount' => $this->detectAmount($normalizedText),
                'expected_amount' => number_format((float) $payment->amount, 2, '.', ''),
                'score' => $score,
                'status' => $status,
                'reasons' => $reasons,
            ],
            'ocr_text' => $text,
            'auto_checked_at' => now(),
        ];
    }

    /**
     * @return array{available: bool, text: string, error: ?string}
     */
    private function extractText(string $absoluteImagePath): array
    {
        $binary = (string) config('services.ocr.tesseract_path', 'tesseract');

        if (!File::exists($absoluteImagePath)) {
            return [
                'available' => false,
                'text' => '',
                'error' => 'Screenshot file was not found on disk.',
            ];
        }

        try {
            $outputs = [];
            $lastError = null;

            foreach ([6, 11, 4, 12, 3] as $psm) {
                $result = $this->runTesseract($binary, $absoluteImagePath, $psm);

                if ($result['success']) {
                    $text = trim($result['text']);

                    if ($text !== '' && !in_array($text, $outputs, true)) {
                        $outputs[] = $text;
                    }

                    continue;
                }

                $lastError = $result['error'];
            }

            if ($outputs === []) {
                return [
                    'available' => false,
                    'text' => '',
                    'error' => $lastError ?: 'OCR command failed.',
                ];
            }

            return [
                'available' => true,
                'text' => implode(PHP_EOL.PHP_EOL, $outputs),
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                'available' => false,
                'text' => '',
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{success: bool, text: string, error: ?string}
     */
    private function runTesseract(string $binary, string $absoluteImagePath, int $psm): array
    {
        $process = new Process([$binary, $absoluteImagePath, 'stdout', '-l', 'eng', '--psm', (string) $psm]);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            return [
                'success' => false,
                'text' => '',
                'error' => trim($process->getErrorOutput()) ?: 'OCR command failed.',
            ];
        }

        return [
            'success' => true,
            'text' => $process->getOutput(),
            'error' => null,
        ];
    }

    private function amountMatched(float $amount, string $normalizedText): bool
    {
        $variants = [
            number_format($amount, 2, '.', ''),
            number_format($amount, 2, ',', ''),
            '$'.number_format($amount, 2, '.', ''),
            '-$'.number_format($amount, 2, '.', ''),
            preg_replace('/\.00$/', '', number_format($amount, 2, '.', '')),
        ];

        foreach (array_filter($variants) as $variant) {
            if (str_contains($normalizedText, strtolower((string) $variant))) {
                return true;
            }
        }

        return false;
    }

    private function detectAmount(string $normalizedText): ?string
    {
        if (preg_match('/-?\$?\d+[.,]\d{2}\b/', $normalizedText, $matches)) {
            return $matches[0];
        }

        return null;
    }

    private function normalizeText(string $text): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $text) ?? ''));
    }
}
