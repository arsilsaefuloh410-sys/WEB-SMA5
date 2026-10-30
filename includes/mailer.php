<?php
/**
 * ==========================================================
 * Pengirim Email (Mailer) + Utilitas URL Aplikasi
 * ==========================================================
 * Menggunakan PHPMailer (vendor/PHPMailer) dengan SMTP.
 * Jika MAIL_USERNAME kosong, fallback ke mail() bawaan PHP.
 */

require_once __DIR__ . '/config.php';

// Muat pustaka PHPMailer tanpa Composer (autoload manual)
require_once dirname(__DIR__) . '/vendor/PHPMailer/src/Exception.php';
require_once dirname(__DIR__) . '/vendor/PHPMailer/src/PHPMailer.php';
require_once dirname(__DIR__) . '/vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Membangun URL absolut (http/https://host/...) dari jalur relatif.
 * Dibutuhkan karena link reset kata sandi dikirim melalui email.
 */
function app_url(string $path = ''): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');

    return $scheme . '://' . $host . base_url($path);
}

/**
 * Mengirim email HTML melalui SMTP (atau mail() jika SMTP belum diisi).
 *
 * @param string $to Alamat email tujuan
 * @param string $subject Subjek email
 * @param string $html Isi email format HTML
 * @param string $text Isi email versi teks polos (opsional, cadangan)
 * @return array [status => bool, message => string]
 */
function send_mail(string $to, string $subject, string $html, string $text = ''): array
{
    $mail = new PHPMailer(true);

    try {
        // Identitas pengirim
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $text !== '' ? $text : trim(strip_tags($html));

        if (MAIL_USERNAME !== '') {
            // Koneksi SMTP (disarankan: Gmail App Password, dll.)
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->Port = (int)MAIL_PORT;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;

            $enc = strtolower(MAIL_ENCRYPTION);
            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->SMTPAutoTLS = false;
            $mail->CharSet = 'UTF-8';
        } else {
            // Fallback: fungsi mail() bawaan PHP (butuh sendmail di XAMPP)
            $mail->isMail();
            $mail->CharSet = 'UTF-8';
        }

        $mail->send();

        return ['status' => true, 'message' => 'Email berhasil dikirim.'];
    } catch (MailException $e) {
        error_log('[MAIL ERROR] ' . $mail->ErrorInfo);
        return ['status' => false, 'message' => 'Gagal mengirim email: ' . $mail->ErrorInfo];
    } catch (Throwable $e) {
        error_log('[MAIL ERROR] ' . $e->getMessage());
        return ['status' => false, 'message' => 'Gagal mengirim email: ' . $e->getMessage()];
    }
}

/**
 * Membuat tampilan HTML email reset kata sandi yang rapi.
 */
function password_reset_email_html(string $nama, string $reset_url, int $expiry_minutes): string
{
    $safe_url = e($reset_url);
    $safe_nama = e($nama);
    $app = e(APP_NAME);
    $school = e(APP_SCHOOL);

    return '
    <div style="margin:0;padding:0;background-color:#eef2f7;font-family:Segoe UI,Arial,Helvetica,sans-serif;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2f7;padding:32px 12px;">
        <tr><td align="center">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(22,41,74,0.08);">
            <tr>
              <td style="background:linear-gradient(135deg,#2A4D88,#1C3A6E);padding:28px 36px;">
                <div style="font-size:20px;font-weight:700;color:#ffffff;letter-spacing:0.06em;">' . $app . '</div>
                <div style="font-size:12px;color:rgba(255,255,255,0.75);margin-top:4px;">' . $school . '</div>
              </td>
            </tr>
            <tr>
              <td style="padding:32px 36px 12px;">
                <h2 style="margin:0 0 16px;font-size:20px;color:#16294A;">Reset Kata Sandi</h2>
                <p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#475569;">
                  Halo <strong>' . $safe_nama . '</strong>, kami menerima permintaan pengaturan ulang kata sandi
                  untuk akun Anda di portal bahan ajar.
                </p>
                <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#475569;">
                  Tekan tombol di bawah ini untuk membuat kata sandi baru:
                </p>
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 22px;">
                  <tr>
                    <td style="border-radius:10px;background:#1E3F73;">
                      <a href="' . $safe_url . '" style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:10px;">Buat Kata Sandi Baru</a>
                    </td>
                  </tr>
                </table>
                <p style="margin:0 0 8px;font-size:13px;line-height:1.7;color:#64748b;">
                  Link ini hanya berlaku selama <strong>' . $expiry_minutes . ' menit</strong> sejak email dikirim
                  dan hanya dapat digunakan satu kali.
                </p>
                <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
                  Jika tombol tidak berbentuk link, salin dan tempel alamat berikut ke peramban Anda:
                </p>
                <p style="margin:8px 0 0;font-size:12px;word-break:break-all;">
                  <a href="' . $safe_url . '" style="color:#2A4D88;">' . $safe_url . '</a>
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding:20px 36px 30px;border-top:1px solid #eef2f7;">
                <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
                  Email ini dikirim otomatis oleh sistem <strong>' . $app . '</strong>.
                  Anda tidak meminta reset kata sandi? Abaikan email ini — kata sandi Anda tetap aman.
                </p>
              </td>
            </tr>
          </table>
        </td></tr>
      </table>
    </div>';
}

/**
 * Membuat tampilan HTML email verifikasi pendaftaran akun guru yang rapi.
 */
function registration_verify_email_html(string $nama, string $verify_url, int $expiry_minutes): string
{
    $safe_url = e($verify_url);
    $safe_nama = e($nama);
    $app = e(APP_NAME);
    $school = e(APP_SCHOOL);

    return '
    <div style="margin:0;padding:0;background-color:#eef2f7;font-family:Segoe UI,Arial,Helvetica,sans-serif;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2f7;padding:32px 12px;">
        <tr><td align="center">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(22,41,74,0.08);">
            <tr>
              <td style="background:linear-gradient(135deg,#2A4D88,#1C3A6E);padding:28px 36px;">
                <div style="font-size:20px;font-weight:700;color:#ffffff;letter-spacing:0.06em;">' . $app . '</div>
                <div style="font-size:12px;color:rgba(255,255,255,0.75);margin-top:4px;">' . $school . '</div>
              </td>
            </tr>
            <tr>
              <td style="padding:32px 36px 12px;">
                <h2 style="margin:0 0 16px;font-size:20px;color:#16294A;">Verifikasi Email Pendaftaran</h2>
                <p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#475569;">
                  Halo <strong>' . $safe_nama . '</strong>, kami menerima permintaan pendaftaran akun guru
                  di portal bahan ajar dengan alamat email ini.
                </p>
                <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#475569;">
                  Tekan tombol di bawah ini untuk memverifikasi bahwa email ini benar-benar milik Anda:
                </p>
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 22px;">
                  <tr>
                    <td style="border-radius:10px;background:#1E3F73;">
                      <a href="' . $safe_url . '" style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:10px;">Verifikasi Email Sekarang</a>
                    </td>
                  </tr>
                </table>
                <p style="margin:0 0 8px;font-size:13px;line-height:1.7;color:#64748b;">
                  Setelah verifikasi, akun Anda akan dibuat dengan status <strong>menunggu persetujuan administrator</strong>
                  sebelum dapat digunakan untuk masuk.
                </p>
                <p style="margin:0 0 8px;font-size:13px;line-height:1.7;color:#64748b;">
                  Tautan ini hanya berlaku selama <strong>' . $expiry_minutes . ' menit</strong> sejak email dikirim
                  dan hanya dapat digunakan satu kali.
                </p>
                <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
                  Jika tombol tidak berbentuk link, salin dan tempel alamat berikut ke peramban Anda:
                </p>
                <p style="margin:8px 0 0;font-size:12px;word-break:break-all;">
                  <a href="' . $safe_url . '" style="color:#2A4D88;">' . $safe_url . '</a>
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding:20px 36px 30px;border-top:1px solid #eef2f7;">
                <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
                  Email ini dikirim otomatis oleh sistem <strong>' . $app . '</strong>.
                  Anda tidak mendaftar? Abaikan email ini — tidak ada akun yang dibuat.
                </p>
              </td>
            </tr>
          </table>
        </td></tr>
      </table>
    </div>';
}
