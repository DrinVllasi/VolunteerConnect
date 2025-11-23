# Two-Way Interest System - Implementation Guide

## 🎯 What This Adds

A **mutual matching system** where:
- **Volunteers** can "express interest" in opportunities (without full application)
- **Organizations** can "invite" volunteers they think are a good fit
- When both sides show interest → **Mutual Match!** (highlighted priority)

## 📋 Files Created

1. **`db/two_way_interest_system.sql`** - Database tables
2. **`includes/interest_handler.php`** - Backend handler for interests/invites
3. **Updated `includes/matching_engine.php`** - Added helper functions

## 🚀 Setup Steps

### Step 1: Run Database Migration
```sql
-- Execute in your database
SOURCE db/two_way_interest_system.sql;
```

### Step 2: Add UI Components

#### For Volunteers (in `public_browse.php` or opportunity cards):
```php
<?php
require_once 'includes/matching_engine.php';
$has_interest = hasVolunteerInterest($conn, $_SESSION['user_id'], $opp['id']);
$has_invite = hasOrganizationInvite($conn, $opp['organization_id'], $_SESSION['user_id'], $opp['id']);
?>

<!-- Express Interest Button -->
<?php if (!$has_interest && !hasApplied): ?>
    <form method="POST" action="includes/interest_handler.php" class="d-inline">
        <input type="hidden" name="action" value="express_interest">
        <input type="hidden" name="opportunity_id" value="<?= $opp['id'] ?>">
        <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-heart"></i> Express Interest
        </button>
    </form>
<?php elseif ($has_interest): ?>
    <span class="badge bg-info">
        <i class="bi bi-heart-fill"></i> Interested
    </span>
<?php endif; ?>

<!-- Show if organization invited them -->
<?php if ($has_invite && $has_invite['status'] === 'pending'): ?>
    <div class="alert alert-success">
        <i class="bi bi-star-fill"></i> You've been invited! 
        <a href="events/event.php?id=<?= $opp['id'] ?>">View Details</a>
    </div>
<?php endif; ?>
```

#### For Organizations (in `manage_events.php`):
```php
<?php
require_once 'includes/matching_engine.php';
$interested_volunteers = getInterestedVolunteers($conn, $event['id']);
$mutual_matches = getMutualMatches($conn, $event['id']);
?>

<!-- Show Interested Volunteers -->
<?php if (!empty($interested_volunteers)): ?>
    <div class="alert alert-info">
        <h6><i class="bi bi-heart-fill"></i> <?= count($interested_volunteers) ?> volunteers expressed interest</h6>
        <?php foreach ($interested_volunteers as $vol): ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong><?= htmlspecialchars($vol['name']) ?></strong>
                    <?php if ($vol['has_invite']): ?>
                        <span class="badge bg-success">Mutual Match!</span>
                    <?php endif; ?>
                </div>
                <?php if (!$vol['has_invite']): ?>
                    <form method="POST" action="includes/interest_handler.php" class="d-inline">
                        <input type="hidden" name="action" value="invite_volunteer">
                        <input type="hidden" name="volunteer_id" value="<?= $vol['volunteer_id'] ?>">
                        <input type="hidden" name="opportunity_id" value="<?= $event['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-envelope"></i> Invite
                        </button>
                    </form>
                <?php else: ?>
                    <span class="badge bg-success">Invited</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

### Step 3: Create "My Interests" Page (Optional)

Create `my_interests.php` to show all opportunities where volunteer expressed interest:

```php
<?php
require_once 'includes/matching_engine.php';
$interests = getVolunteerInterests($conn, $_SESSION['user_id']);
?>

<!-- Display opportunities with interest status -->
```

## 🎨 Visual Indicators

- **🟢 "Interested" badge** - Volunteer expressed interest
- **🔵 "Invited" badge** - Organization invited volunteer  
- **⭐ "Mutual Match!" badge** - Both sides interested (priority!)
- **💚 Heart icon** - Express interest button

## 💡 Benefits

1. **Reduces Application Spam** - Volunteers show interest first
2. **Better Matching** - Organizations see genuine interest
3. **Mutual Matches** - Highlighted when both sides interested
4. **Engagement** - More interactive than just applying
5. **Quality** - Better matches, fewer rejections

## 🔄 Workflow

1. Volunteer browses opportunities
2. Clicks "Express Interest" on opportunities they like
3. Organization sees interested volunteers in manage events
4. Organization can "Invite" volunteers (especially high match scores)
5. If both interested → Mutual Match! (highlighted)
6. Volunteer can then apply (with priority if mutual match)

## 📊 Database Tables

- `volunteer_interests` - Tracks volunteer interests
- `organization_invites` - Tracks organization invites
- Both have indexes for fast queries

## 🚀 Next Steps

1. Run the SQL migration
2. Add UI buttons to opportunity cards
3. Add "Interested Volunteers" section to manage_events.php
4. Test the flow!
5. Optionally create "My Interests" page

This creates a much more engaging and effective matching system!

