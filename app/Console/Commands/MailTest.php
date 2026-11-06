<?php

namespace App\Console\Commands;

use App\Mail\ResetCodeMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Uji pengiriman email (SMTP) untuk kode reset password.
 * Pemakaian: php artisan scp:mail-test alamat@email.com
 * Galat pengiriman di aplikasi hanya dicatat di log, jadi perintah ini menampilkannya langsung.
 */
class MailTest extends Command
{
    protected $signature = 'scp:mail-test {to : Alamat email tujuan uji}';
    protected $description = 'Kirim email uji (kode reset contoh) untuk memeriksa konfigurasi SMTP';

    public function handle(): int
    {
        $mailer = config('mail.default');
        $this->line('Mailer : ' . $mailer);
        if ($mailer === 'log' || $mailer === 'array') {
            $this->warn('MAIL_MAILER=' . $mailer . ': email TIDAK dikirim sungguhan (hanya dicatat). Ubah ke smtp di .env lalu jalankan php artisan config:clear.');
        }
        $this->line('Host   : ' . config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port'));
        $this->line('Akun   : ' . (config('mail.mailers.smtp.username') ?: '(kosong)'));
        $this->line('Dari   : ' . config('mail.from.address'));

        try {
            Mail::to($this->argument('to'))->send(new ResetCodeMail('Pengguna Uji', '123456', 10));
        } catch (Throwable $e) {
            $this->error('GAGAL: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Terkirim ke ' . $this->argument('to') . '. Periksa kotak masuk (dan folder spam).');
        return self::SUCCESS;
    }
}
