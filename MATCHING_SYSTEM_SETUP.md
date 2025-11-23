# Volunteer Matching System - Setup Guide

## Overview
The matching system helps organizations find the right volunteers and helps volunteers discover opportunities that match their skills and interests.

## Features
1. **Smart Matching Algorithm** - Calculates compatibility scores (0-100%) based on:
   - Category experience (30 points)
   - Volunteer level (25 points)
   - Reliability score (20 points)
   - Recent activity (15 points)
   - Skills match (10 points)

2. **For Organizations**:
   - See matched volunteers who haven't applied yet
   - View compatibility scores and match factors
   - Make informed decisions about outreach

3. **For Volunteers**:
   - Get personalized recommendations
   - Set preferences (categories, skills, availability)
   - See match scores on opportunities

## Setup Instructions

### Step 1: Run Database Migration
Execute the SQL file to add the necessary tables and columns:

```sql
-- Run this in your MySQL/phpMyAdmin
SOURCE db/matching_system.sql;
```

Or manually run the SQL commands from `db/matching_system.sql`

### Step 2: Update Existing Opportunities
For existing opportunities without categories, the system will auto-detect categories from titles/descriptions. However, you can manually update them:

```sql
-- Example: Update a specific opportunity
UPDATE opportunities SET category = 'Environment' WHERE id = 1;
```

### Step 3: Test the System

1. **As a Volunteer**:
   - Go to your profile page
   - Click "Set Preferences" or visit `volunteer_preferences.php`
   - Set your preferred categories, skills, and availability
   - Browse opportunities - you'll see "Recommended for You" section

2. **As an Organization**:
   - Create a new opportunity and select a category
   - Go to "Manage Events"
   - View "Top Matched Volunteers" section for each event
   - See compatibility scores and match factors

## How It Works

### Matching Algorithm
The system calculates a compatibility score based on:

1. **Category Experience** (30 pts): Hours volunteered in the same category
2. **Volunteer Level** (25 pts): Based on total hours (Level 1-4)
3. **Reliability** (20 pts): Completion rate of past applications
4. **Recent Activity** (15 pts): Events in the last 3 months
5. **Skills Match** (10 pts): Skills mentioned in opportunity description

### Categories
- Environment
- Education
- Food Service
- Healthcare
- Community
- Children
- Animals
- Disaster Relief
- General

## Files Created/Modified

**New Files:**
- `db/matching_system.sql` - Database schema
- `includes/matching_engine.php` - Core matching algorithm
- `volunteer_preferences.php` - Volunteer preferences page

**Modified Files:**
- `post_opportunity.php` - Added category selection
- `manage_events.php` - Shows matched volunteers
- `public_browse.php` - Shows recommended opportunities
- `profile.php` - Added link to preferences

## Usage Tips

1. **For Volunteers**: The more complete your profile and preferences, the better matches you'll get
2. **For Organizations**: Use categories accurately - it significantly improves matching
3. **Match Scores**: Scores above 60% are considered good matches, above 80% are excellent

## Future Enhancements
- Location-based matching (distance calculation)
- Time-based availability matching
- Machine learning for improved recommendations
- Volunteer feedback/ratings system

