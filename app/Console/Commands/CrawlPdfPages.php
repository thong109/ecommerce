<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Request;
use Spatie\Browsershot\Browsershot;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader;


class CrawlPdfPages extends Command
{
    protected $signature = 'crawl:pdf';
    protected $description = 'Crawl and save PDF pages from a given print-view URL';

    public function handle($url)
    {

        // $this->info("Bắt đầu crawl PDF...");

        $filename = 'page.pdf';
        $path = storage_path('app/public/pdfs/' . $filename);

        try {
            Browsershot::url($url)
                ->waitUntilNetworkIdle()
                ->showBackground()
                ->format('A4')
                ->setOption('printBackground', true)
                ->setOption('displayHeaderFooter', false)
                ->noSandbox()
                ->timeout(3600)
                ->waitUntilNetworkIdle()
                ->savePdf($path);

            $this->info("Đã lưu: $filename");
        } catch (\Exception $e) {
            // $this->error("Lỗi ở trang $i: " . $e->getMessage());
        }

        sleep(1); // chống DDoS
    }
}
