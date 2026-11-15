<?php
declare(strict_types=1);

namespace App\Controllers\Tourist;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Database;

final class PreferencesController
{
    public function save(): void
    {
        Auth::requireRole('tourist');
        CSRF::checkOrAbort($_POST['_csrf'] ?? null);

        $userId = Auth::id();
        $pdo = Database::connection();

        $budget = $_POST['budget'] ?? '';
        $month = $_POST['travel_month'] ?? '';
        $interests = $_POST['interests'] ?? [];

        if (!is_numeric($budget) || (float)$budget < 0) {
            flash('error', 'Budget must be a valid number greater than or equal to 0.');
            redirect('tourist/dashboard');
        }
        if ($month !== '' && (!filter_var($month, FILTER_VALIDATE_INT) || (int)$month < 1 || (int)$month > 12)) {
            flash('error', 'Invalid travel month.');
            redirect('tourist/dashboard');
        }
        if (empty($interests)) {
            flash('error', 'Please choose at least one interest.');
            redirect('tourist/dashboard');
        }

        $validInterests = array_map('intval', $pdo->query("SELECT id FROM interest_types")->fetchAll(\PDO::FETCH_COLUMN));
        $interests = array_filter($interests, fn($id) => in_array((int)$id, $validInterests, true));
        
        if (empty($interests)) {
            flash('error', 'Invalid interests selected.');
            redirect('tourist/dashboard');
        }

        $stmt = $pdo->prepare("
            INSERT INTO tourist_profiles (user_id, budget_npr, travel_month) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE budget_npr = VALUES(budget_npr), travel_month = VALUES(travel_month)
        ");
        $stmt->execute([$userId, (float)$budget, $month !== '' ? (int)$month : null]);

        $stmt = $pdo->prepare("SELECT id FROM tourist_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profileId = (int)$stmt->fetchColumn();

        $pdo->prepare("DELETE FROM tourist_interests WHERE tourist_profile_id = ?")->execute([$profileId]);
        $ins = $pdo->prepare("INSERT INTO tourist_interests (tourist_profile_id, interest_type_id) VALUES (?, ?)");
        foreach ($interests as $tid) {
            $ins->execute([$profileId, $tid]);
        }

        flash('success', 'Preferences saved.');
        redirect('tourist/dashboard?rec=1');
    }
}