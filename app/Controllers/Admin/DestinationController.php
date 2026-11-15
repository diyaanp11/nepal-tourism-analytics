<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\View;

final class DestinationController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        
        $destinations = Database::connection()->query("
            SELECT d.*, r.name as region_name 
            FROM destinations d 
            LEFT JOIN regions r ON r.id = d.region_id 
            ORDER BY d.status ASC, d.name ASC
        ")->fetchAll();

        $title = 'Destinations';
        $content = View::render('admin.destinations', ['destinations' => $destinations]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }

    public function edit(int $id): void
    {
        Auth::requireRole('admin');
        $pdo = Database::connection();
        
        $stmt = $pdo->prepare("
            SELECT d.*, r.name as region_name 
            FROM destinations d 
            LEFT JOIN regions r ON r.id = d.region_id 
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        $dest = $stmt->fetch();
        
        if (!$dest) {
            flash('error', 'Destination not found.');
            redirect('admin/destinations');
        }

        $regions = $pdo->query("SELECT * FROM regions ORDER BY name")->fetchAll();
        $types = $pdo->query("SELECT * FROM interest_types ORDER BY name")->fetchAll();
        
        $stmt = $pdo->prepare("SELECT interest_type_id FROM destination_types WHERE destination_id = ?");
        $stmt->execute([$id]);
        $destTypes = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("
            SELECT GROUP_CONCAT(ELT(month_number, 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec') ORDER BY month_number SEPARATOR ', ') 
            FROM destination_best_months WHERE destination_id = ?
        ");
        $stmt->execute([$id]);
        $bestMonths = $stmt->fetchColumn();
        if (empty($bestMonths)) {
            $bestMonths = 'Not enough data yet';
        }

        $title = 'Edit Destination: ' . $dest['name'];
        $content = View::render('admin.destination_edit', [
            'dest' => $dest, 'regions' => $regions, 'types' => $types,
            'destTypes' => $destTypes, 'bestMonths' => $bestMonths
        ]);
        require BASE_PATH . '/app/Views/layouts/main.php';
    }

    public function update(int $id): void
    {
        Auth::requireRole('admin');
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);

        $pdo = Database::connection();
        
        $description = trim($_POST['description'] ?? '');
        
        // Validate Cost
        $rawCost = $_POST['avg_cost_npr'] ?? '0';
        if (!is_numeric($rawCost)) {
            flash('error', 'Cost must be a valid number.');
            redirect('admin/destinations/edit/' . $id);
        }
        $avgCost = (float)$rawCost;
        if ($avgCost < 0) {
            flash('error', 'Cost must be 0 or more.');
            redirect('admin/destinations/edit/' . $id);
        }

        // Validate Lat/Lng
        $rawLat = $_POST['latitude'] ?? '';
        $lat = null;
        if ($rawLat !== '') {
            if (!is_numeric($rawLat) || (float)$rawLat < -90 || (float)$rawLat > 90) {
                flash('error', 'Latitude must be a valid number between -90 and 90.');
                redirect('admin/destinations/edit/' . $id);
            }
            $lat = (float)$rawLat;
        }

        $rawLng = $_POST['longitude'] ?? '';
        $lng = null;
        if ($rawLng !== '') {
            if (!is_numeric($rawLng) || (float)$rawLng < -180 || (float)$rawLng > 180) {
                flash('error', 'Longitude must be a valid number between -180 and 180.');
                redirect('admin/destinations/edit/' . $id);
            }
            $lng = (float)$rawLng;
        }

        $types = $_POST['types'] ?? [];

        // Fetch current data
        $stmt = $pdo->prepare("SELECT region_id, image, status FROM destinations WHERE id = ?");
        $stmt->execute([$id]);
        $currentData = $stmt->fetch();
        $currentRegionId = $currentData['region_id'];
        $status = $currentData['status'];
        $oldImage = $currentData['image'];

        // Handle Region
        $regionId = $currentRegionId;
        $newRegionName = trim($_POST['new_region'] ?? '');
        $regionToCreate = null;

        if (!$currentRegionId) {
            if (!empty($_POST['region_id'])) {
                $regionId = (int)$_POST['region_id'];
            } elseif ($newRegionName !== '') {
                $rStmt = $pdo->prepare("SELECT id FROM regions WHERE LOWER(name) = LOWER(?)");
                $rStmt->execute([$newRegionName]);
                $regionId = $rStmt->fetchColumn();
                if (!$regionId) {
                    $regionToCreate = $newRegionName;
                }
            }
        }

        // Handle Image
        $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';
        $imageValue = $oldImage;
        $newImagePath = null;
        $oldImagePath = null;

        if ($oldImage && str_starts_with($oldImage, 'assets/uploads/')) {
            $oldImagePath = BASE_PATH . '/' . $oldImage;
        }

        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_file'];
            if ($file['size'] > 2 * 1024 * 1024) {
                flash('error', 'Image size must not exceed 2MB.');
                redirect('admin/destinations/edit/' . $id);
            }
            $info = @getimagesize($file['tmp_name']);
            if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
                flash('error', 'Invalid image file. Only JPG, PNG, and WebP are allowed.');
                redirect('admin/destinations/edit/' . $id);
            }
            
            $uploadDir = BASE_PATH . '/assets/uploads/destinations/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = match($info[2]) {
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG  => 'png',
                IMAGETYPE_WEBP => 'webp',
            };
            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $imageValue = 'assets/uploads/destinations/' . $filename;
                $newImagePath = $targetPath;
            } else {
                flash('error', 'Failed to save uploaded image.');
                redirect('admin/destinations/edit/' . $id);
            }
        } elseif (!empty($_POST['image_url']) && !$removeImage) {
            $url = trim($_POST['image_url']);
            if (filter_var($url, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $url)) {
                $imageValue = $url;
            } else {
                flash('error', 'Invalid image URL.');
                redirect('admin/destinations/edit/' . $id);
            }
        } elseif ($removeImage) {
            $imageValue = null;
        }

        // Validation checks before database writes
        if ($status === 'active') {
            if ((!$regionId && !$regionToCreate) || $avgCost <= 0 || empty($types)) {
                flash('error', 'Active destinations require a region, a cost > 0, and at least one type.');
                redirect('admin/destinations/edit/' . $id);
            }
        }

        $pdo->beginTransaction();
        try {
            if ($regionToCreate) {
                $insR = $pdo->prepare("INSERT INTO regions (name) VALUES (?)");
                $insR->execute([$regionToCreate]);
                $regionId = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("UPDATE destinations SET region_id = ?, avg_cost_npr = ?, description = ?, latitude = ?, longitude = ?, image = ? WHERE id = ?");
            $stmt->execute([$regionId, $avgCost, $description, $lat, $lng, $imageValue, $id]);

            $pdo->prepare("DELETE FROM destination_types WHERE destination_id = ?")->execute([$id]);
            $stmt = $pdo->prepare("INSERT INTO destination_types (destination_id, interest_type_id) VALUES (?, ?)");
            foreach ($types as $typeId) {
                $stmt->execute([$id, $typeId]);
            }

            $pdo->commit();

            // Success: Now delete old file if we replaced it
            if ($oldImagePath && is_file($oldImagePath)) {
                if ($newImagePath || $removeImage || ($imageValue !== $oldImage)) {
                    unlink($oldImagePath);
                }
            }
            flash('success', 'Destination updated successfully.');
        } catch (\Exception $e) {
            $pdo->rollBack();

            // Failure: Delete the newly uploaded file to avoid orphans, keep the old one
            if ($newImagePath && is_file($newImagePath)) {
                unlink($newImagePath);
            }
            flash('error', 'Error updating destination.');
        }
        redirect('admin/destinations');
    }

    public function toggleStatus(int $id): void
    {
        Auth::requireRole('admin');
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);

        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT status, region_id, avg_cost_npr FROM destinations WHERE id = ?");
        $stmt->execute([$id]);
        $dest = $stmt->fetch();

        if ($dest['status'] === 'pending' || $dest['status'] === 'inactive') {
            $stmtType = $pdo->prepare("SELECT COUNT(*) FROM destination_types WHERE destination_id = ?");
            $stmtType->execute([$id]);
            $typeCount = (int)$stmtType->fetchColumn();
            
            if (!$dest['region_id'] || $dest['avg_cost_npr'] <= 0 || $typeCount === 0) {
                flash('error', 'Cannot activate: Destination requires region, cost > 0, and at least one type.');
                redirect('admin/destinations');
            }
            $newStatus = 'active';
        } else {
            $newStatus = 'inactive';
        }

        $stmt = $pdo->prepare("UPDATE destinations SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
        flash('success', 'Destination status changed to ' . $newStatus);
        redirect('admin/destinations');
    }
}