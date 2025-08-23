<?php
/**
 * SHGM Exam System – User Model (Güvenli Sürüm)
 * --------------------------------------------------------------
 * - findByEmail / find
 * - loginWithEmailPassword: legacy SHA-256 → otomatik modern re-hash
 * - updatePasswordHash / createMinimal
 * - sanitizeUserRow
 * 
 * Tablo: users
 * Kolon: id, name, email, password, role, status, last_login, created_at, updated_at
 */

class TN_UserModel
{
    /** @var PDO */
    private PDO $pdo;

    public function __construct()
    {
        // Projenizde TN_Database::getInstance() veya tn_db() helper var.
        // En uyumlu olanı kullanıyoruz:
        $this->pdo = function_exists('tn_db') ? tn_db() : TN_Database::getInstance();

        // Güvenli PDO modları (çifte güvence)
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
    }

    /** Benzersiz e-posta ile kullanıcı getir */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM `users` WHERE `email` = :email LIMIT 1";
        $st  = $this->pdo->prepare($sql);
        $st->execute([':email' => $email]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** ID ile kullanıcı getir */
    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare("SELECT * FROM `users` WHERE `id` = :id LIMIT 1");
        $st->execute([':id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Giriş başarılıysa son giriş zamanını güncelle */
    public function touchLastLogin(int $userId): void
    {
        $st = $this->pdo->prepare("UPDATE `users` SET `last_login` = NOW() WHERE `id` = :id LIMIT 1");
        $st->execute([':id' => $userId]);
    }

    /** Parola alanı DB’de güvenli hash ile güncellenir */
    public function updatePasswordHash(int $userId, string $newHash): void
    {
        $st = $this->pdo->prepare("UPDATE `users` SET `password` = :ph WHERE `id` = :id LIMIT 1");
        $st->execute([':ph' => $newHash, ':id' => $userId]);
    }

    /**
     * Kısa yol: minimum alanlarla kullanıcı oluştur (parola otomatik hash’lenir)
     * NOT: email benzersiz olmalı (unique index) – var ise hata fırlatır.
     */
    public function createMinimal(string $name, string $email, string $plainPassword, string $role = 'student', int $status = 1): int
    {
        $hash = tn_password_hash($plainPassword);

        $sql = "INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`)
                VALUES (:n, :e, :p, :r, :s, NOW(), NOW())";
        $st  = $this->pdo->prepare($sql);
        $st->execute([
            ':n' => $name,
            ':e' => $email,
            ':p' => $hash,
            ':r' => $role,
            ':s' => $status,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Şifresiz (güvenli) kullanıcı objesi döndür */
    public function sanitizeUserRow(array $row): array
    {
        unset($row['password']);
        return $row;
    }

    /**
     * Parola doğrulama + otomatik re-hash akışı
     * - status = 1 (aktif) değilse giriş reddedilir
     * - Hash modern değilse (veya cost değişmişse) başarılı girişte otomatik re-hash
     * - Başarılıysa sanitize edilmiş kullanıcı döner, değilse null
     */
    public function loginWithEmailPassword(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        // 1=aktif, 0=inactive, -1=suspended, 2=pending ...
        if ((int)$user['status'] !== 1) {
            return null;
        }

        $hash = (string)($user['password'] ?? '');

        // Doğrulama (modern ya da legacy)
        if (!tn_password_verify($password, $hash)) {
            return null;
        }

        // Başarılı doğrulamadan SONRA: modern değilse veya cost değiştiyse re-hash et
        if (tn_password_needs_rehash($hash)) {
            $newHash = tn_password_hash($password);
            $this->updatePasswordHash((int)$user['id'], $newHash);
        }

        // Login yan etkileri
        $this->touchLastLogin((int)$user['id']);

        // Şifresiz kullanıcı objesi dön
        return $this->sanitizeUserRow($user);
    }
}
