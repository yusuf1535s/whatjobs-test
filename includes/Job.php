<?php
/**
 * Job Model & Data Access Object
 */

require_once __DIR__ . '/../config/db.php';

class Job {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function search(array $filters = [], int $limit = 10, int $offset = 0): array {
        $params = [];
        $where = ["j.status = 'active'"];

        if (!empty($filters['what'])) {
            $term = '%' . trim($filters['what']) . '%';
            $where[] = "(j.title LIKE :what_t OR j.description LIKE :what_d OR c.name LIKE :what_c)";
            $params[':what_t'] = $term;
            $params[':what_d'] = $term;
            $params[':what_c'] = $term;
        }

        if (!empty($filters['where'])) {
            $loc = '%' . trim($filters['where']) . '%';
            $where[] = "(j.location LIKE :where_l OR j.zip_code LIKE :where_z)";
            $params[':where_l'] = $loc;
            $params[':where_z'] = $loc;
        }

        if (!empty($filters['remote'])) {
            $where[] = "j.is_remote = 1";
        }

        if (!empty($filters['category_id'])) {
            $where[] = "j.category_id = :cat_id";
            $params[':cat_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['category_slug'])) {
            $where[] = "cat.slug = :cat_slug";
            $params[':cat_slug'] = $filters['category_slug'];
        }

        if (!empty($filters['job_type_id'])) {
            $where[] = "j.job_type_id = :type_id";
            $params[':type_id'] = (int)$filters['job_type_id'];
        }

        if (!empty($filters['job_type_slug'])) {
            $where[] = "jt.slug = :type_slug";
            $params[':type_slug'] = $filters['job_type_slug'];
        }

        if (!empty($filters['experience_level'])) {
            $where[] = "j.experience_level = :exp_lvl";
            $params[':exp_lvl'] = $filters['experience_level'];
        }

        if (!empty($filters['salary_min'])) {
            $where[] = "(j.salary_min >= :sal_min OR j.salary_max >= :sal_min)";
            $params[':sal_min'] = (float)$filters['salary_min'];
        }

        $orderBy = "j.is_featured DESC, j.created_at DESC";
        if (!empty($filters['sort'])) {
            if ($filters['sort'] === 'salary') {
                $orderBy = "j.salary_max DESC, j.created_at DESC";
            } elseif ($filters['sort'] === 'popular') {
                $orderBy = "j.views_count DESC, j.created_at DESC";
            }
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT j.*, 
                       c.name AS company_name, c.logo_url AS company_logo, c.slug AS company_slug,
                       cat.name AS category_name, cat.slug AS category_slug,
                       jt.name AS job_type_name, jt.slug AS job_type_slug
                FROM jobs j
                JOIN companies c ON j.company_id = c.id
                JOIN categories cat ON j.category_id = cat.id
                JOIN job_types jt ON j.job_type_id = jt.id
                WHERE $whereClause
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function count(array $filters = []): int {
        $params = [];
        $where = ["j.status = 'active'"];

        if (!empty($filters['what'])) {
            $term = '%' . trim($filters['what']) . '%';
            $where[] = "(j.title LIKE :what_t OR j.description LIKE :what_d OR c.name LIKE :what_c)";
            $params[':what_t'] = $term;
            $params[':what_d'] = $term;
            $params[':what_c'] = $term;
        }

        if (!empty($filters['where'])) {
            $loc = '%' . trim($filters['where']) . '%';
            $where[] = "(j.location LIKE :where_l OR j.zip_code LIKE :where_z)";
            $params[':where_l'] = $loc;
            $params[':where_z'] = $loc;
        }

        if (!empty($filters['remote'])) {
            $where[] = "j.is_remote = 1";
        }

        if (!empty($filters['category_id'])) {
            $where[] = "j.category_id = :cat_id";
            $params[':cat_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['category_slug'])) {
            $where[] = "cat.slug = :cat_slug";
            $params[':cat_slug'] = $filters['category_slug'];
        }

        if (!empty($filters['job_type_id'])) {
            $where[] = "j.job_type_id = :type_id";
            $params[':type_id'] = (int)$filters['job_type_id'];
        }

        if (!empty($filters['job_type_slug'])) {
            $where[] = "jt.slug = :type_slug";
            $params[':type_slug'] = $filters['job_type_slug'];
        }

        if (!empty($filters['experience_level'])) {
            $where[] = "j.experience_level = :exp_lvl";
            $params[':exp_lvl'] = $filters['experience_level'];
        }

        if (!empty($filters['salary_min'])) {
            $where[] = "(j.salary_min >= :sal_min OR j.salary_max >= :sal_min)";
            $params[':sal_min'] = (float)$filters['salary_min'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*)
                FROM jobs j
                JOIN companies c ON j.company_id = c.id
                JOIN categories cat ON j.category_id = cat.id
                JOIN job_types jt ON j.job_type_id = jt.id
                WHERE $whereClause";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getById(int $id): ?array {
        $sql = "SELECT j.*, 
                       c.name AS company_name, c.logo_url AS company_logo, c.website AS company_website,
                       c.slug AS company_slug, c.description AS company_description, c.company_size, c.industry,
                       cat.name AS category_name, cat.slug AS category_slug,
                       jt.name AS job_type_name, jt.slug AS job_type_slug
                FROM jobs j
                JOIN companies c ON j.company_id = c.id
                JOIN categories cat ON j.category_id = cat.id
                JOIN job_types jt ON j.job_type_id = jt.id
                WHERE j.id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getBySlug(string $slug): ?array {
        $sql = "SELECT j.*, 
                       c.name AS company_name, c.logo_url AS company_logo, c.website AS company_website,
                       c.slug AS company_slug, c.description AS company_description, c.company_size, c.industry,
                       cat.name AS category_name, cat.slug AS category_slug,
                       jt.name AS job_type_name, jt.slug AS job_type_slug
                FROM jobs j
                JOIN companies c ON j.company_id = c.id
                JOIN categories cat ON j.category_id = cat.id
                JOIN job_types jt ON j.job_type_id = jt.id
                WHERE j.slug = :slug
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    public function incrementViews(int $id): void {
        $stmt = $this->db->prepare("UPDATE jobs SET views_count = views_count + 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function getFeatured(int $limit = 6): array {
        return $this->search(['is_featured' => 1], $limit);
    }

    public function getLatest(int $limit = 8): array {
        return $this->search([], $limit);
    }

    public function getCategories(): array {
        $sql = "SELECT c.*, (SELECT COUNT(*) FROM jobs j WHERE j.category_id = c.id AND j.status = 'active') as active_count 
                FROM categories c 
                ORDER BY c.name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getJobTypes(): array {
        return $this->db->query("SELECT * FROM job_types ORDER BY id ASC")->fetchAll();
    }

    public function getCompanies(): array {
        return $this->db->query("SELECT * FROM companies ORDER BY name ASC")->fetchAll();
    }

    public function isSavedByUser(int $jobId, int $userId): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM saved_jobs WHERE user_id = :uid AND job_id = :jid LIMIT 1");
        $stmt->execute([':uid' => $userId, ':jid' => $jobId]);
        return (bool)$stmt->fetchColumn();
    }

    public function toggleSave(int $jobId, int $userId): bool {
        if ($this->isSavedByUser($jobId, $userId)) {
            $stmt = $this->db->prepare("DELETE FROM saved_jobs WHERE user_id = :uid AND job_id = :jid");
            $stmt->execute([':uid' => $userId, ':jid' => $jobId]);
            return false; // Now unsaved
        } else {
            $stmt = $this->db->prepare("INSERT INTO saved_jobs (user_id, job_id) VALUES (:uid, :jid)");
            $stmt->execute([':uid' => $userId, ':jid' => $jobId]);
            return true; // Now saved
        }
    }

    public function getSavedJobs(int $userId): array {
        $sql = "SELECT j.*, 
                       c.name AS company_name, c.logo_url AS company_logo,
                       cat.name AS category_name,
                       jt.name AS job_type_name,
                       sj.created_at AS saved_at
                FROM saved_jobs sj
                JOIN jobs j ON sj.job_id = j.id
                JOIN companies c ON j.company_id = c.id
                JOIN categories cat ON j.category_id = cat.id
                JOIN job_types jt ON j.job_type_id = jt.id
                WHERE sj.user_id = :uid
                ORDER BY sj.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function getAllForAdmin(array $filters = [], int $limit = 50, int $offset = 0): array {
        $params = [];
        $where = ["1=1"];

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $where[] = "(j.title LIKE :s1 OR c.name LIKE :s2 OR j.location LIKE :s3)";
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
        }

        if (!empty($filters['status'])) {
            $where[] = "j.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = "j.category_id = :cat_id";
            $params[':cat_id'] = (int)$filters['category_id'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT j.*, 
                       c.name AS company_name, 
                       cat.name AS category_name, 
                       jt.name AS job_type_name,
                       (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as applications_count
                FROM jobs j
                JOIN companies c ON j.company_id = c.id
                JOIN categories cat ON j.category_id = cat.id
                JOIN job_types jt ON j.job_type_id = jt.id
                WHERE $whereClause
                ORDER BY j.id DESC
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

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $where[] = "(j.title LIKE :s1 OR c.name LIKE :s2 OR j.location LIKE :s3)";
            $params[':s1'] = $term;
            $params[':s2'] = $term;
            $params[':s3'] = $term;
        }

        if (!empty($filters['status'])) {
            $where[] = "j.status = :status";
            $params[':status'] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM jobs j JOIN companies c ON j.company_id = c.id WHERE $whereClause";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): int {
        $slug = $this->generateUniqueSlug($data['title']);
        $sql = "INSERT INTO jobs (
                    company_id, category_id, job_type_id, title, slug, location, zip_code,
                    salary_min, salary_max, salary_type, is_salary_negotiable, is_remote,
                    is_featured, experience_level, description, responsibilities, requirements, benefits, status
                ) VALUES (
                    :company_id, :category_id, :job_type_id, :title, :slug, :location, :zip_code,
                    :salary_min, :salary_max, :salary_type, :is_salary_negotiable, :is_remote,
                    :is_featured, :experience_level, :description, :responsibilities, :requirements, :benefits, :status
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':company_id' => (int)$data['company_id'],
            ':category_id' => (int)$data['category_id'],
            ':job_type_id' => (int)$data['job_type_id'],
            ':title' => $data['title'],
            ':slug' => $slug,
            ':location' => $data['location'],
            ':zip_code' => $data['zip_code'] ?? null,
            ':salary_min' => !empty($data['salary_min']) ? (float)$data['salary_min'] : null,
            ':salary_max' => !empty($data['salary_max']) ? (float)$data['salary_max'] : null,
            ':salary_type' => $data['salary_type'] ?? 'year',
            ':is_salary_negotiable' => !empty($data['is_salary_negotiable']) ? 1 : 0,
            ':is_remote' => !empty($data['is_remote']) ? 1 : 0,
            ':is_featured' => !empty($data['is_featured']) ? 1 : 0,
            ':experience_level' => $data['experience_level'] ?? 'Mid Level',
            ':description' => $data['description'],
            ':responsibilities' => $data['responsibilities'] ?? null,
            ':requirements' => $data['requirements'] ?? null,
            ':benefits' => $data['benefits'] ?? null,
            ':status' => $data['status'] ?? 'active'
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE jobs SET 
                    company_id = :company_id,
                    category_id = :category_id,
                    job_type_id = :job_type_id,
                    title = :title,
                    location = :location,
                    zip_code = :zip_code,
                    salary_min = :salary_min,
                    salary_max = :salary_max,
                    salary_type = :salary_type,
                    is_salary_negotiable = :is_salary_negotiable,
                    is_remote = :is_remote,
                    is_featured = :is_featured,
                    experience_level = :experience_level,
                    description = :description,
                    responsibilities = :responsibilities,
                    requirements = :requirements,
                    benefits = :benefits,
                    status = :status
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':company_id' => (int)$data['company_id'],
            ':category_id' => (int)$data['category_id'],
            ':job_type_id' => (int)$data['job_type_id'],
            ':title' => $data['title'],
            ':location' => $data['location'],
            ':zip_code' => $data['zip_code'] ?? null,
            ':salary_min' => !empty($data['salary_min']) ? (float)$data['salary_min'] : null,
            ':salary_max' => !empty($data['salary_max']) ? (float)$data['salary_max'] : null,
            ':salary_type' => $data['salary_type'] ?? 'year',
            ':is_salary_negotiable' => !empty($data['is_salary_negotiable']) ? 1 : 0,
            ':is_remote' => !empty($data['is_remote']) ? 1 : 0,
            ':is_featured' => !empty($data['is_featured']) ? 1 : 0,
            ':experience_level' => $data['experience_level'] ?? 'Mid Level',
            ':description' => $data['description'],
            ':responsibilities' => $data['responsibilities'] ?? null,
            ':requirements' => $data['requirements'] ?? null,
            ':benefits' => $data['benefits'] ?? null,
            ':status' => $data['status'] ?? 'active'
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM jobs WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function toggleStatus(int $id): string {
        $job = $this->getById($id);
        if (!$job) return 'not_found';
        $newStatus = $job['status'] === 'active' ? 'paused' : 'active';
        $stmt = $this->db->prepare("UPDATE jobs SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $newStatus, ':id' => $id]);
        return $newStatus;
    }

    private function generateUniqueSlug(string $title): string {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $orig = $slug;
        $count = 1;
        while (true) {
            $stmt = $this->db->prepare("SELECT id FROM jobs WHERE slug = :slug LIMIT 1");
            $stmt->execute([':slug' => $slug]);
            if (!$stmt->fetch()) {
                break;
            }
            $slug = $orig . '-' . (++$count);
        }
        return $slug;
    }
}

