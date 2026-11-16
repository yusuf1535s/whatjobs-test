<?php
/**
 * User & Admin Authentication and Account Management
 */

require_once __DIR__ . '/../config/db.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function register(string $name, string $email, string $password, ?string $phone = null, ?string $title = null, ?string $location = null): array {
        // Validate email uniqueness
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'An account with this email address already exists.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (full_name, email, password, phone, current_job_title, location, status) 
                VALUES (:name, :email, :password, :phone, :title, :location, 'active')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hash,
            ':phone' => $phone,
            ':title' => $title,
            ':location' => $location
        ]);

        $userId = (int)$this->db->lastInsertId();
        return ['success' => true, 'user_id' => $userId];
    }

    public function authenticate(string $email, string $password): array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email address or password.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your account has been deactivated or suspended. Please contact support.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid email address or password.'];
        }

        return ['success' => true, 'user' => $user];
    }

    public function authenticateAdmin(string $identifier, string $password): array {
        $stmt = $this->db->prepare("SELECT * FROM admins WHERE username = :id1 OR email = :id2 LIMIT 1");
        $stmt->execute([':id1' => $identifier, ':id2' => $identifier]);
        $admin = $stmt->fetch();

        if (!$admin) {
            return ['success' => false, 'error' => 'Invalid administrator credentials.'];
        }

        if (!password_verify($password, $admin['password'])) {
            return ['success' => false, 'error' => 'Invalid administrator credentials.'];
        }

        return ['success' => true, 'admin' => $admin];
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, full_name, email, phone, current_job_title, location, resume_path, bio, status, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function updateProfile(int $id, array $data): bool {
        $sql = "UPDATE users SET 
                    full_name = :name,
                    phone = :phone,
                    current_job_title = :title,
                    location = :location,
                    bio = :bio
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['full_name'],
            ':phone' => $data['phone'] ?? null,
            ':title' => $data['current_job_title'] ?? null,
            ':location' => $data['location'] ?? null,
            ':bio' => $data['bio'] ?? null
        ]);
    }

    public function updateResume(int $id, string $resumePath): bool {
        $stmt = $this->db->prepare("UPDATE users SET resume_path = :path WHERE id = :id");
        return $stmt->execute([':path' => $resumePath, ':id' => $id]);
    }

    public function updatePassword(int $id, string $oldPassword, string $newPassword): array {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $currentHash = $stmt->fetchColumn();

        if (!$currentHash || !password_verify($oldPassword, $currentHash)) {
            return ['success' => false, 'error' => 'Current password does not match.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->execute([':password' => $newHash, ':id' => $id]);
        return ['success' => true];
    }

    public function getAllUsers(int $limit = 50, int $offset = 0): array {
        $sql = "SELECT u.*, 
                       (SELECT COUNT(*) FROM applications a WHERE a.user_id = u.id) as applications_count,
                       (SELECT COUNT(*) FROM saved_jobs s WHERE s.user_id = u.id) as saved_count
                FROM users u 
                ORDER BY u.id DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAllUsers(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function toggleUserStatus(int $id): string {
        $stmt = $this->db->prepare("SELECT status FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $current = $stmt->fetchColumn();
        if (!$current) return 'not_found';
        $new = $current === 'active' ? 'suspended' : 'active';
        $stmt = $this->db->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $new, ':id' => $id]);
        return $new;
    }
}

