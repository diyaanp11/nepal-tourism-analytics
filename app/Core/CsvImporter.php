<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Exception;

final class CsvImporter
{
    public static function import(string $filePath, string $type, int $adminId): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return ['success' => false, 'message' => 'Cannot open file.'];
        }

        $pdo = Database::connection();
        $currentYear = (int)date('Y');
        $errors = [];
        $createdLookups = [];
        $affectedDestinations = [];
        $success = 0;
        $failed = 0;
        $lineNum = 0;

        $expectedHeaders = [
            'monthly_arrivals'        => ['year', 'month', 'arrivals'],
            'arrivals_by_nationality' => ['year', 'country', 'arrivals'],
            'arrivals_by_purpose'     => ['year', 'purpose', 'arrivals'],
            'destination_demand'      => ['destination_slug', 'destination_name', 'region_name', 'year', 'month', 'arrivals']
        ];

        if (!isset($expectedHeaders[$type])) {
            fclose($handle);
            return ['success' => false, 'message' => 'Invalid import type.'];
        }

        $header = fgetcsv($handle);
        $lineNum++;
        if (!$header) {
            fclose($handle);
            return ['success' => false, 'message' => 'File is empty.'];
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $header = array_map('strtolower', array_map('trim', $header));

        if ($header !== $expectedHeaders[$type]) {
            fclose($handle);
            return ['success' => false, 'message' => 'Header mismatch. Expected: ' . implode(',', $expectedHeaders[$type])];
        }

        $pdo->beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $lineNum++;
                
                if (count($row) === 1 && empty($row[0])) continue;

                if (count($row) !== count($expectedHeaders[$type])) {
                    $msg = "Line {$lineNum}: Expected " . count($expectedHeaders[$type]) . " columns, got " . count($row);
                    $errors[] = $msg;
                    self::logRejection($adminId, $msg);
                    $failed++;
                    continue;
                }

                $data = array_combine($expectedHeaders[$type], array_map('trim', $row));

                $year = $data['year'];
                $month = $data['month'] ?? null;
                $arrivals = $data['arrivals'];

                if (!preg_match('/^\d{4}$/', $year) || (int)$year < 1990 || (int)$year > $currentYear) {
                    $msg = "Line {$lineNum}: Invalid year '{$year}' (must be 1990-{$currentYear}).";
                    $errors[] = $msg; self::logRejection($adminId, $msg); $failed++; continue;
                }
                
                if ($month !== null && (!filter_var($month, FILTER_VALIDATE_INT) || (int)$month < 1 || (int)$month > 12)) {
                    $msg = "Line {$lineNum}: Invalid month '{$month}' (must be 1-12).";
                    $errors[] = $msg; self::logRejection($adminId, $msg); $failed++; continue;
                }

                $arrivalsValid = filter_var($arrivals, FILTER_VALIDATE_INT);
                if ($arrivalsValid === false || (int)$arrivals < 0) {
                    $msg = "Line {$lineNum}: Invalid arrivals '{$arrivals}' (must be whole number >= 0).";
                    $errors[] = $msg; self::logRejection($adminId, $msg); $failed++; continue;
                }

                if ($type === 'monthly_arrivals') {
                    $stmt = $pdo->prepare("INSERT INTO monthly_arrivals (year, month, arrivals) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE arrivals = VALUES(arrivals)");
                    $stmt->execute([(int)$year, (int)$month, (int)$arrivals]);
                    $success++;
                } 
                elseif ($type === 'arrivals_by_nationality') {
                    $countryName = $data['country'];
                    if (empty($countryName)) {
                        $msg = "Line {$lineNum}: Empty country name.";
                        $errors[] = $msg; self::logRejection($adminId, $msg); $failed++; continue;
                    }
                    
                    $cStmt = $pdo->prepare("SELECT id FROM countries WHERE LOWER(name) = LOWER(?)");
                    $cStmt->execute([$countryName]);
                    $countryId = $cStmt->fetchColumn();
                    
                    if (!$countryId) {
                        $insC = $pdo->prepare("INSERT INTO countries (name) VALUES (?)");
                        $insC->execute([$countryName]);
                        $countryId = $pdo->lastInsertId();
                        $createdLookups[] = "Country: {$countryName}";
                        self::logLookupCreation($adminId, "Country: {$countryName}");
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO arrivals_by_nationality (year, country_id, arrivals) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE arrivals = VALUES(arrivals)");
                    $stmt->execute([(int)$year, $countryId, (int)$arrivals]);
                    $success++;
                }
                elseif ($type === 'arrivals_by_purpose') {
                    $purpose = $data['purpose'];
                    if (empty($purpose)) {
                        $msg = "Line {$lineNum}: Empty purpose.";
                        $errors[] = $msg; self::logRejection($adminId, $msg); $failed++; continue;
                    }
                    
                    $pStmt = $pdo->prepare("SELECT id FROM purposes WHERE LOWER(name) = LOWER(?)");
                    $pStmt->execute([$purpose]);
                    $purposeId = $pStmt->fetchColumn();
                    
                    if (!$purposeId) {
                        $insP = $pdo->prepare("INSERT INTO purposes (name) VALUES (?)");
                        $insP->execute([ucfirst(strtolower($purpose))]);
                        $purposeId = $pdo->lastInsertId();
                        $createdLookups[] = "Purpose: {$purpose}";
                        self::logLookupCreation($adminId, "Purpose: {$purpose}");
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO arrivals_by_purpose (year, purpose_id, arrivals) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE arrivals = VALUES(arrivals)");
                    $stmt->execute([(int)$year, $purposeId, (int)$arrivals]);
                    $success++;
                }
                elseif ($type === 'destination_demand') {
                    $slug = trim($data['destination_slug'] ?? '');
                    $name = trim($data['destination_name'] ?? '');
                    $regionName = trim($data['region_name'] ?? '');

                    if (empty($slug) || empty($name)) {
                        $msg = "Line {$lineNum}: Empty destination slug or name.";
                        $errors[] = $msg; self::logRejection($adminId, $msg); $failed++; continue;
                    }
                    
                    $dStmt = $pdo->prepare("
                        SELECT id FROM destinations WHERE slug = ? 
                        UNION 
                        SELECT id FROM destinations WHERE LOWER(name) = LOWER(?) 
                        UNION 
                        SELECT destination_id FROM destination_aliases WHERE LOWER(alias) = LOWER(?)
                        LIMIT 1
                    ");
                    $dStmt->execute([$slug, $name, $name]);
                    $destId = $dStmt->fetchColumn();

                    if (!$destId) {
                        $regionId = null;
                        if ($regionName) {
                            $rStmt = $pdo->prepare("SELECT id FROM regions WHERE LOWER(name) = LOWER(?)");
                            $rStmt->execute([$regionName]);
                            $regionId = $rStmt->fetchColumn() ?: null;
                            
                            if (!$regionId) {
                                $insR = $pdo->prepare("INSERT INTO regions (name) VALUES (?)");
                                $insR->execute([$regionName]);
                                $regionId = $pdo->lastInsertId();
                                $createdLookups[] = "Region: {$regionName}";
                                self::logLookupCreation($adminId, "Region: {$regionName}");
                            }
                        }
                        $insDest = $pdo->prepare("INSERT INTO destinations (slug, name, region_id, status, created_by) VALUES (?, ?, ?, 'pending', ?)");
                        $insDest->execute([$slug, $name, $regionId, $adminId]);
                        $destId = $pdo->lastInsertId();
                    }

                    $stmt = $pdo->prepare("INSERT INTO destination_demand (destination_id, year, month, arrivals) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE arrivals = VALUES(arrivals)");
                    $stmt->execute([$destId, (int)$year, (int)$month, (int)$arrivals]);
                    $success++;
                    
                    $affectedDestinations[$destId] = true;
                }
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            fclose($handle);
            return ['success' => false, 'message' => 'Database error during import: ' . $e->getMessage()];
        }

        fclose($handle);

        $cleanFilename = preg_replace('/^[a-zA-Z0-9]+_/', '', basename($filePath));
        $log = $pdo->prepare("INSERT INTO import_history (admin_id, filename, rows_total, rows_accepted, rows_rejected, status) VALUES (?, ?, ?, ?, ?, ?)");
        $status = $failed > 0 ? ($success > 0 ? 'partial' : 'failed') : 'success';
        $log->execute([$adminId, $cleanFilename, $success + $failed, $success, $failed, $status]);

        return [
            'success' => true, 
            'inserted' => $success, 
            'failed' => $failed,
            'errors' => array_slice($errors, 0, 10),
            'created_lookups' => $createdLookups,
            'affected_destination_ids' => array_keys($affectedDestinations)
        ];
    }

    private static function logRejection(int $adminId, string $reason): void
    {
        $stmt = Database::connection()->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, 'csv_row_rejected', ?)");
        $stmt->execute([$adminId, $reason]);
    }

    private static function logLookupCreation(int $adminId, string $details): void
    {
        $stmt = Database::connection()->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, 'lookup_created', ?)");
        $stmt->execute([$adminId, $details]);
    }
}