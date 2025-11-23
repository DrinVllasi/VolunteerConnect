<?php
session_start();
require_once 'config/config.php';

// Only volunteers can access
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['user', 'volunteer'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission BEFORE including header (to avoid headers already sent error)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_preferences'])) {
    $preferred_categories = json_encode($_POST['preferred_categories'] ?? []);
    $max_distance = (int)($_POST['max_distance'] ?? 50);
    $availability_days = $_POST['availability_days'] ?? 'any';
    $preferred_time = $_POST['preferred_time'] ?? 'any';
    $skills = json_encode(array_filter(array_map('trim', explode(',', $_POST['skills'] ?? ''))));
    $bio = trim($_POST['bio'] ?? '');
    
    // Check if preferences exist
    $check = $conn->prepare("SELECT id FROM volunteer_preferences WHERE volunteer_id = ?");
    $check->execute([$user_id]);
    
    if ($check->fetch()) {
        // Update
        $stmt = $conn->prepare("
            UPDATE volunteer_preferences 
            SET preferred_categories = ?, max_distance = ?, availability_days = ?, 
                preferred_time = ?, skills = ?, bio = ?
            WHERE volunteer_id = ?
        ");
        $stmt->execute([$preferred_categories, $max_distance, $availability_days, $preferred_time, $skills, $bio, $user_id]);
    } else {
        // Insert
        $stmt = $conn->prepare("
            INSERT INTO volunteer_preferences 
            (volunteer_id, preferred_categories, max_distance, availability_days, preferred_time, skills, bio)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $preferred_categories, $max_distance, $availability_days, $preferred_time, $skills, $bio]);
    }
    
    $_SESSION['pref_success'] = "Preferences saved successfully!";
    header('Location: volunteer_preferences.php');
    exit();
}

// Now include header after processing POST (if not redirecting)
include_once 'includes/header.php';

// Get current preferences
$pref_stmt = $conn->prepare("SELECT * FROM volunteer_preferences WHERE volunteer_id = ?");
$pref_stmt->execute([$user_id]);
$preferences = $pref_stmt->fetch(PDO::FETCH_ASSOC);

$preferred_categories = $preferences ? json_decode($preferences['preferred_categories'] ?? '[]', true) : [];
$skills_array = $preferences ? json_decode($preferences['skills'] ?? '[]', true) : [];
$skills_string = implode(', ', $skills_array);
?>

<style>
    :root{
        --accent-1: #6a8e3a;
        --accent-2: #b27a4b;
        --earth-1: #f2efe9;
    }
    
    .pref-card {
        background: white;
        border-radius: 14px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }
    
    .category-checkbox {
        display: inline-block;
        margin: 0.5rem;
    }
    
    .category-checkbox input[type="checkbox"] {
        display: none;
    }
    
    .category-checkbox label {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        background: var(--earth-1);
        border: 2px solid transparent;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
    }
    
    .category-checkbox input[type="checkbox"]:checked + label {
        background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
        color: white;
        border-color: var(--accent-1);
    }
</style>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="pref-card">
                <h2 class="fw-bold mb-4">Volunteer Preferences</h2>
                <p class="text-muted mb-4">Help us match you with the perfect opportunities by setting your preferences.</p>
                
                <?php if (isset($_SESSION['pref_success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['pref_success']) ?></div>
                    <?php unset($_SESSION['pref_success']); ?>
                <?php endif; ?>
                
                <form method="POST">
                    <!-- Preferred Categories -->
                    <div class="mb-4">
                        <label class="form-label fw-bold mb-3">Preferred Categories</label>
                        <div>
                            <?php 
                            $categories = ['Environment', 'Education', 'Food Service', 'Healthcare', 'Community', 'Children', 'Animals', 'Disaster Relief'];
                            foreach ($categories as $cat): 
                            ?>
                                <div class="category-checkbox">
                                    <input type="checkbox" name="preferred_categories[]" value="<?= $cat ?>" 
                                           id="cat_<?= strtolower(str_replace(' ', '_', $cat)) ?>"
                                           <?= in_array($cat, $preferred_categories) ? 'checked' : '' ?>>
                                    <label for="cat_<?= strtolower(str_replace(' ', '_', $cat)) ?>"><?= $cat ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Select categories you're interested in volunteering for</small>
                    </div>
                    
                    <!-- Skills -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Skills</label>
                        <input type="text" name="skills" class="form-control" 
                               placeholder="e.g., Teaching, Cooking, First Aid, Event Planning" 
                               value="<?= htmlspecialchars($skills_string) ?>">
                        <small class="text-muted">Separate skills with commas</small>
                    </div>
                    
                    <!-- Availability -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Availability</label>
                            <select name="availability_days" class="form-select">
                                <option value="any" <?= ($preferences['availability_days'] ?? 'any') === 'any' ? 'selected' : '' ?>>Any Day</option>
                                <option value="weekdays" <?= ($preferences['availability_days'] ?? '') === 'weekdays' ? 'selected' : '' ?>>Weekdays</option>
                                <option value="weekends" <?= ($preferences['availability_days'] ?? '') === 'weekends' ? 'selected' : '' ?>>Weekends</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Preferred Time</label>
                            <select name="preferred_time" class="form-select">
                                <option value="any" <?= ($preferences['preferred_time'] ?? 'any') === 'any' ? 'selected' : '' ?>>Any Time</option>
                                <option value="morning" <?= ($preferences['preferred_time'] ?? '') === 'morning' ? 'selected' : '' ?>>Morning</option>
                                <option value="afternoon" <?= ($preferences['preferred_time'] ?? '') === 'afternoon' ? 'selected' : '' ?>>Afternoon</option>
                                <option value="evening" <?= ($preferences['preferred_time'] ?? '') === 'evening' ? 'selected' : '' ?>>Evening</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Max Distance -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Maximum Travel Distance (km)</label>
                        <input type="number" name="max_distance" class="form-control" 
                               value="<?= htmlspecialchars($preferences['max_distance'] ?? 50) ?>" 
                               min="1" max="500">
                    </div>
                    
                    <!-- Bio -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Bio</label>
                        <textarea name="bio" class="form-control" rows="4" 
                                  placeholder="Tell organizations about yourself, your interests, and why you volunteer..."><?= htmlspecialchars($preferences['bio'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="save_preferences" class="btn btn-primary btn-lg">
                            Save Preferences
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <a href="profile.php" class="btn btn-link">← Back to Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

