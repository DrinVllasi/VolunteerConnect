<?php
/**
 * Volunteer Matching Engine
 * Calculates compatibility scores between volunteers and opportunities
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Extract category from opportunity title/description
 */
function extractCategory($title, $description) {
    $text = strtolower($title . ' ' . $description);
    
    $categories = [
        'Environment' => ['clean', 'environment', 'recycle', 'beach', 'park', 'tree', 'green', 'nature', 'garden'],
        'Education' => ['teach', 'education', 'tutor', 'school', 'learn', 'student', 'reading', 'literacy'],
        'Food Service' => ['food', 'kitchen', 'meal', 'hunger', 'drive', 'soup', 'pantry'],
        'Healthcare' => ['health', 'care', 'hospital', 'senior', 'elderly', 'medical', 'wellness'],
        'Community' => ['community', 'event', 'festival', 'outreach', 'neighborhood'],
        'Children' => ['children', 'kids', 'youth', 'camp', 'playground', 'after school'],
        'Animals' => ['animal', 'pet', 'shelter', 'rescue', 'dog', 'cat'],
        'Disaster Relief' => ['disaster', 'emergency', 'relief', 'crisis', 'aid']
    ];
    
    foreach ($categories as $cat => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return $cat;
            }
        }
    }
    
    return 'General';
}

/**
 * Get volunteer's experience in a category
 */
function getVolunteerCategoryExperience($conn, $volunteer_id, $category) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(a.hours_logged), 0) as hours
        FROM applications a
        JOIN opportunities o ON a.opportunity_id = o.id
        WHERE a.volunteer_id = ? 
            AND a.status = 'confirmed' 
            AND a.hours_logged > 0
            AND o.category = ?
    ");
    $stmt->execute([$volunteer_id, $category]);
    return $stmt->fetchColumn();
}

/**
 * Get volunteer preferences
 */
function getVolunteerPreferences($conn, $volunteer_id) {
    $stmt = $conn->prepare("SELECT * FROM volunteer_preferences WHERE volunteer_id = ?");
    $stmt->execute([$volunteer_id]);
    $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($prefs) {
        $prefs['preferred_categories'] = json_decode($prefs['preferred_categories'] ?? '[]', true);
        $prefs['skills'] = json_decode($prefs['skills'] ?? '[]', true);
    }
    
    return $prefs;
}

/**
 * Calculate compatibility score between volunteer and opportunity
 * Returns score from 0-100
 */
function calculateMatchScore($conn, $volunteer_id, $opportunity) {
    $score = 0;
    $maxScore = 100;
    $factors = [];
    
    // Get volunteer data
    $vol_stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(a.hours_logged), 0) as total_hours,
            COUNT(a.id) as total_events
        FROM applications a
        WHERE a.volunteer_id = ? AND a.status = 'confirmed' AND a.hours_logged > 0
    ");
    $vol_stmt->execute([$volunteer_id]);
    $vol_data = $vol_stmt->fetch(PDO::FETCH_ASSOC);
    $total_hours = $vol_data['total_hours'] ?? 0;
    
    // Factor 1: Category Match (30 points)
    $category = $opportunity['category'] ?? extractCategory($opportunity['title'], $opportunity['description']);
    $category_experience = getVolunteerCategoryExperience($conn, $volunteer_id, $category);
    
    if ($category_experience > 0) {
        // Has experience in this category
        $category_score = min(30, 15 + ($category_experience / 10)); // 15-30 points
        $score += $category_score;
        $factors[] = ['name' => 'Category Experience', 'score' => round($category_score), 'max' => 30];
    } else {
        // Check if category is in preferences
        $prefs = getVolunteerPreferences($conn, $volunteer_id);
        if ($prefs && in_array($category, $prefs['preferred_categories'] ?? [])) {
            $score += 20;
            $factors[] = ['name' => 'Preferred Category', 'score' => 20, 'max' => 30];
        } else {
            $score += 5; // Basic match
            $factors[] = ['name' => 'Category Match', 'score' => 5, 'max' => 30];
        }
    }
    
    // Factor 2: Volunteer Level (25 points)
    $vol_level = 1;
    if ($total_hours >= 100) $vol_level = 4;
    elseif ($total_hours >= 50) $vol_level = 3;
    elseif ($total_hours >= 25) $vol_level = 2;
    
    $level_score = ($vol_level / 4) * 25; // 6.25, 12.5, 18.75, or 25 points
    $score += $level_score;
    $factors[] = ['name' => 'Volunteer Level', 'score' => round($level_score), 'max' => 25];
    
    // Factor 3: Reliability Score (20 points)
    // Based on completion rate and consistency
    $reliability_stmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
            COUNT(*) as total
        FROM applications
        WHERE volunteer_id = ?
    ");
    $reliability_stmt->execute([$volunteer_id]);
    $reliability = $reliability_stmt->fetch(PDO::FETCH_ASSOC);
    $completion_rate = $reliability['total'] > 0 ? ($reliability['confirmed'] / $reliability['total']) : 0;
    $reliability_score = $completion_rate * 20;
    $score += $reliability_score;
    $factors[] = ['name' => 'Reliability', 'score' => round($reliability_score), 'max' => 20];
    
    // Factor 4: Recent Activity (15 points)
    // Volunteers who are active recently get bonus
    $recent_stmt = $conn->prepare("
        SELECT COUNT(*) as recent_events
        FROM applications a
        JOIN opportunities o ON a.opportunity_id = o.id
        WHERE a.volunteer_id = ? 
            AND a.status = 'confirmed'
            AND o.date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
    ");
    $recent_stmt->execute([$volunteer_id]);
    $recent = $recent_stmt->fetchColumn();
    $activity_score = min(15, $recent * 3); // 3 points per recent event, max 15
    $score += $activity_score;
    $factors[] = ['name' => 'Recent Activity', 'score' => round($activity_score), 'max' => 15];
    
    // Factor 5: Skills Match (10 points)
    $prefs = getVolunteerPreferences($conn, $volunteer_id);
    if ($prefs && !empty($prefs['skills'])) {
        // Simple keyword matching in description
        $desc_lower = strtolower($opportunity['description'] ?? '');
        $matched_skills = 0;
        foreach ($prefs['skills'] as $skill) {
            if (strpos($desc_lower, strtolower($skill)) !== false) {
                $matched_skills++;
            }
        }
        $skills_score = min(10, $matched_skills * 3);
        $score += $skills_score;
        $factors[] = ['name' => 'Skills Match', 'score' => round($skills_score), 'max' => 10];
    } else {
        $factors[] = ['name' => 'Skills Match', 'score' => 0, 'max' => 10];
    }
    
    // Ensure score is between 0-100
    $score = min(100, max(0, $score));
    
    return [
        'score' => round($score),
        'factors' => $factors,
        'level' => $vol_level,
        'category_experience' => $category_experience
    ];
}

/**
 * Get matched volunteers for an opportunity (sorted by match score)
 */
function getMatchedVolunteers($conn, $opportunity_id, $limit = 20) {
    $stmt = $conn->prepare("SELECT * FROM opportunities WHERE id = ?");
    $stmt->execute([$opportunity_id]);
    $opportunity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$opportunity) return [];
    
    // Get all volunteers who haven't applied yet
    $vol_stmt = $conn->prepare("
        SELECT u.id, u.name, u.email
        FROM users u
        WHERE u.role IN ('user', 'volunteer')
        AND u.id NOT IN (
            SELECT volunteer_id FROM applications WHERE opportunity_id = ?
        )
    ");
    $vol_stmt->execute([$opportunity_id]);
    $volunteers = $vol_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $matches = [];
    foreach ($volunteers as $vol) {
        $match_data = calculateMatchScore($conn, $vol['id'], $opportunity);
        $matches[] = [
            'volunteer_id' => $vol['id'],
            'name' => $vol['name'],
            'email' => $vol['email'],
            'match_score' => $match_data['score'],
            'match_factors' => $match_data['factors'],
            'level' => $match_data['level'],
            'category_experience' => $match_data['category_experience']
        ];
    }
    
    // Sort by match score descending
    usort($matches, function($a, $b) {
        return $b['match_score'] - $a['match_score'];
    });
    
    return array_slice($matches, 0, $limit);
}

/**
 * Get recommended opportunities for a volunteer (sorted by match score)
 */
function getRecommendedOpportunities($conn, $volunteer_id, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT o.*, u.name as org_name,
            (o.slots - COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END)) as spots_left
        FROM opportunities o
        LEFT JOIN users u ON o.organization_id = u.id
        LEFT JOIN applications a ON o.id = a.opportunity_id
        WHERE o.date >= CURDATE()
            AND o.id NOT IN (SELECT opportunity_id FROM applications WHERE volunteer_id = ?)
        GROUP BY o.id
        HAVING spots_left > 0
        ORDER BY o.date ASC
    ");
    $stmt->execute([$volunteer_id]);
    $opportunities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $recommendations = [];
    foreach ($opportunities as $opp) {
        $match_data = calculateMatchScore($conn, $volunteer_id, $opp);
        $recommendations[] = [
            'opportunity' => $opp,
            'match_score' => $match_data['score'],
            'match_factors' => $match_data['factors']
        ];
    }
    
    // Sort by match score descending
    usort($recommendations, function($a, $b) {
        return $b['match_score'] - $a['match_score'];
    });
    
    return array_slice($recommendations, 0, $limit);
}

/**
 * Check if volunteer has expressed interest in an opportunity
 */
function hasVolunteerInterest($conn, $volunteer_id, $opportunity_id) {
    $stmt = $conn->prepare("SELECT id FROM volunteer_interests WHERE volunteer_id = ? AND opportunity_id = ?");
    $stmt->execute([$volunteer_id, $opportunity_id]);
    return $stmt->fetch() !== false;
}

/**
 * Check if organization has invited volunteer
 */
function hasOrganizationInvite($conn, $organization_id, $volunteer_id, $opportunity_id) {
    $stmt = $conn->prepare("SELECT id, status FROM organization_invites WHERE organization_id = ? AND volunteer_id = ? AND opportunity_id = ?");
    $stmt->execute([$organization_id, $volunteer_id, $opportunity_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get mutual matches (both interested)
 */
function getMutualMatches($conn, $opportunity_id) {
    $stmt = $conn->prepare("
        SELECT vi.volunteer_id, u.name, u.email, oi.status as invite_status
        FROM volunteer_interests vi
        INNER JOIN organization_invites oi ON vi.volunteer_id = oi.volunteer_id AND vi.opportunity_id = oi.opportunity_id
        INNER JOIN users u ON vi.volunteer_id = u.id
        WHERE vi.opportunity_id = ? AND oi.status = 'pending'
    ");
    $stmt->execute([$opportunity_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get opportunities where volunteer expressed interest
 */
function getVolunteerInterests($conn, $volunteer_id) {
    $stmt = $conn->prepare("
        SELECT o.*, u.name as org_name, vi.interested_at,
            (SELECT COUNT(*) FROM organization_invites oi WHERE oi.opportunity_id = o.id AND oi.volunteer_id = vi.volunteer_id AND oi.status = 'pending') as has_invite
        FROM volunteer_interests vi
        JOIN opportunities o ON vi.opportunity_id = o.id
        LEFT JOIN users u ON o.organization_id = u.id
        WHERE vi.volunteer_id = ? AND o.date >= CURDATE()
        ORDER BY vi.interested_at DESC
    ");
    $stmt->execute([$volunteer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get volunteers who expressed interest in an opportunity
 */
function getInterestedVolunteers($conn, $opportunity_id) {
    $stmt = $conn->prepare("
        SELECT vi.volunteer_id, vi.interested_at, u.name, u.email,
            (SELECT COUNT(*) FROM organization_invites oi WHERE oi.volunteer_id = vi.volunteer_id AND oi.opportunity_id = vi.opportunity_id AND oi.status = 'pending') as has_invite
        FROM volunteer_interests vi
        JOIN users u ON vi.volunteer_id = u.id
        WHERE vi.opportunity_id = ?
        ORDER BY vi.interested_at DESC
    ");
    $stmt->execute([$opportunity_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

