<?php
/**
 * SHGM Exam System – Student Model
 * ---------------------------------------------------------------
 * - Öğrenciyi email ile getirir
 * - Parola doğrular (modern hash + eski SHA2 geri-uyum desteği)
 * - Son giriş saatini günceller
 *
 * Tablo: `students`
 */

class TN_StudentModel
{
    /** @var PDO */
    private PDO $pdo;

    public function __construct()
    {
        // Tek erişim noktası (TN_Database → PDO Singleton)
        $this->pdo = TN_Database::getInstance();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /** E-posta benzersiz kabul edilir */
    public function findByEmail(string $email): ?array
    {
        $st = $this->pdo->prepare("SELECT * FROM `students` WHERE `email` = :e LIMIT 1");
        $st->execute([':e' => $email]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Parola doğrulama (modern hash öncelikli, SHA2 geri uyum opsiyonel) */
    public function verifyPassword(string $plain, array $row): bool
    {
        $hash = (string)($row['password'] ?? '');

        // 1) Modern hash (bcrypt/argon)
        if (preg_match('~^\$2y\$|\$argon2~', $hash)) {
            return password_verify($plain, $hash);
        }

        // 2) Eski dump’lar için SHA2(…,256) uyumluluğu
        $st = $this->pdo->prepare("SELECT 1 FROM `students` WHERE `id` = :id AND `password` = SHA2(:p, 256) LIMIT 1");
        $st->execute([':id' => $row['id'], ':p' => $plain]);
        return (bool)$st->fetchColumn();
    }

    /** last_login alanını güncelle */
    public function touchLastLogin(int $id): void
    {
        $st = $this->pdo->prepare("UPDATE `students` SET `last_login` = NOW() WHERE `id` = :id LIMIT 1");
        $st->execute([':id' => $id]);
    }

    /** Parolasız güvenli öğrenci objesi döndür */
    public function sanitize(array $row): array
    {
        unset($row['password']);
        return $row;
    }

    /** Giriş akışı: aktif mi + parola doğru mu? */
    public function loginWithEmailPassword(string $email, string $password): ?array
    {
        $s = $this->findByEmail($email);
        if (!$s) return null;

        // 1=aktif, 0=pasif vb.
        if ((int)($s['status'] ?? 0) !== 1) return null;

        if (!$this->verifyPassword($password, $s)) return null;

        $this->touchLastLogin((int)$s['id']);
        return $this->sanitize($s);
    }
}
