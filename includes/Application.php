<?php
/**
 * Application Model & Management
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

class Application {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function apply(
        int $jobId,
        ?int $userId,
        string $name,
        string $email,
        string $phone,
        ?string $coverLetter,
        string $resumePath
    ): array {
        // Prevent duplicate application if user is logged in
        if ($userId && $this->hasUserApplied($jobId, $userId)) {
            return ['success' => false, 'error' => 'You have already submitted an application for this job posting.'];
        }

        $sql = "INSERT INTO applications (job_id, user_id, applicant_name, applicant_email, applicant_phone, cover_letter, resume_path, status)
                VALUES (:job_id, :user_id, :name, :email, :phone, :cover_letter, :resume_path, 'pending')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':job_id' => $jobId,
            ':user_id' => $userId,
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':cover_letter' => $coverLetter,
            ':resume_path' => $resumePath
        ]);

        return ['success' => true, 'application_id' => (int)$this->db->lastInsertId()];
    }

    public function hasUserApplied(int $jobId, int $userId): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM applications WHERE job_id = :jid AND user_id = :uid LIMIT 1");
        $stmt->execute([':jid' => $jobId, ':uid' => $userId]);
        return (bool)$stmt->fetchColumn();
    }

    public function getByUserId(int $userId): array {
        $sql = "SELECT a.*, 
                       j.title AS job_title, j.slug AS job_slug, j.location AS job_location,
                       j.salary_min, j.salary_max, j.salary_type, j.is_remote,
                       c.name AS company_name, c.logo_url AS company_logo
                FROM applications a
                JOIN jobs j ON a.job_id = j.id
                JOIN companies c ON j.company_id = c.id
                WHERE a.user_id = :uid
                ORDER BY a.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $sql = "SELECT a.*, 
                       j.title AS job_title, j.slug AS job_slug, j.location AS job_location,
                       c.name AS company_name, c.logo_url AS company_logo
                FROM applications a
                JOIN jobs j ON a.job_id = j.id
                JOIN companies c ON j.company_id = c.id
                WHERE a.id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getAllForAdmin(array $filters = [], int $limit = 50, int $offset = 0): array {
        $params = [];
        $where = ["1=1"];

        if (!empty($filters['status'])) {
            $where[] = "a.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['job_id'])) {
            $where[] = "a.job_id = :job_id";
            $params[':job_id'] = (int)$filters['job_id'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $where[] = "(a.applicant_name LIKE :s1 OR a.applicant_email LIKE :s2 OR j.title LIKE :s3 OR c.name LIKE :s4)";
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT a.*, 
                       j.title AS job_title, j.slug AS job_slug,
                       c.name AS company_name
                FROM applications a
                JOIN jobs j ON a.job_id = j.id
                JOIN companies c ON j.company_id = c.id
                WHERE $whereClause
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAllForAdmin(array $filters = []): int {
        $params = [];
        $where = ["1=1"];

        if (!empty($filters['status'])) {
            $where[] = "a.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['job_id'])) {
            $where[] = "a.job_id = :job_id";
            $params[':job_id'] = (int)$filters['job_id'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $where[] = "(a.applicant_name LIKE :s1 OR a.applicant_email LIKE :s2 OR j.title LIKE :s3 OR c.name LIKE :s4)";
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
            $params[':s4'] = $term;
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*)
                FROM applications a
                JOIN jobs j ON a.job_id = j.id
                JOIN companies c ON j.company_id = c.id
                WHERE $whereClause";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function updateStatus(int $id, string $status, ?string $adminNotes = null): bool {
        $sql = "UPDATE applications SET status = :status, admin_notes = :notes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':notes' => $adminNotes
        ]);
    }

    public function getDashboardStats(): array {
        $totalJobs = (int)$this->db->query("SELECT COUNT(*) FROM jobs WHERE status = 'active'")->fetchColumn();
        $totalApplications = (int)$this->db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
        $pendingApplications = (int)$this->db->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
        $totalUsers = (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        return [
            'total_jobs' => $totalJobs,
            'total_applications' => $totalApplications,
            'pending_applications' => $pendingApplications,
            'total_users' => $totalUsers
        ];
    }
}

