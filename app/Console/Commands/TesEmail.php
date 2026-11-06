<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TesEmail extends Command
{
    protected $signature = 'scp:tes-email {to : Alamat email tujuan}';

    protected $description = 'Kirim email uji untuk memeriksa pengaturan MAIL_* (SMTP)';

    public function handle(): int
    {
        $this->info('Mailer: ' . config('mail.default')
            . ' | Host: ' . config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port'));

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER masih "log": email tidak dikirim ke kotak masuk. Ubah ke smtp di .env lalu jalankan php artisan config:clear.');
        }

        try {
            Mail::raw(
                'Ini email uji dari School Class Progress. Jika Anda menerima email ini, pengaturan email sudah benar.',
                fn ($message) => $message->to($this->argument('to'))->subject('Tes email SCP')
            );
        } catch (Throwable $e) {
            $this->error('Gagal mengirim: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Email uji dikirim ke ' . $this->argument('to') . '. Periksa kotak masuk dan folder Spam.');
        return self::SUCCESS;
    }
}
