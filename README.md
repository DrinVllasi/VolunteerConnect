# VolunteerConnect

A comprehensive volunteer management platform that connects volunteers with organizations and opportunities.

## Features

### For Volunteers
- **Browse Opportunities**: Discover volunteer opportunities by category and location
- **Express Interest**: Show interest in opportunities before applying
- **Smart Recommendations**: Get personalized opportunity suggestions based on preferences
- **Profile Management**: Set preferences, track volunteer hours, and earn badges
- **Level System**: Progress through volunteer levels (New → Active → Experienced → Master)
- **Skills Tracking**: Track and showcase volunteer skills and categories

### For Organizations
- **Post Opportunities**: Create and manage volunteer opportunities
- **Manage Applications**: Review and approve volunteer applications
- **Invite Volunteers**: Send invitations to suitable candidates
- **Track Progress**: Monitor event completion and volunteer hours

### Advanced Features
- **Two-Way Interest System**: Mutual matching between volunteers and organizations
- **Badge & Achievement System**: Gamification for volunteer engagement
- **Real-time Notifications**: Stay updated on applications and invites
- **Comprehensive Dashboard**: Full overview of volunteer activities

## Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: Bootstrap 5, HTML5, CSS3, JavaScript
- **Icons**: Bootstrap Icons

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/VolunteerConnect.git
   cd VolunteerConnect
   ```

2. **Set up XAMPP/WAMP**
   - Place the project in `htdocs` folder
   - Start Apache and MySQL services

3. **Database Setup**
   - Create database: `volunteerconnect`
   - Import `db/volunteerconnect_complete.sql` (includes all features and sample data)

4. **Configuration**
   - Update database credentials in `config/config.php`
   - Ensure proper file permissions

## Database Setup

Simply import the complete database file:

```sql
SOURCE db/volunteerconnect_complete.sql;
```

This includes all tables, indexes, and sample data for the full system.

## Usage

### Volunteer Workflow
1. Register/Login as a volunteer
2. Set preferences in "Volunteer Preferences"
3. Browse recommended opportunities
4. Express interest or apply directly
5. Track progress in profile

### Organization Workflow
1. Register/Login as an organization
2. Post volunteer opportunities
3. Review interested volunteers
4. Invite suitable candidates
5. Manage applications and hours

## File Structure

```
VolunteerConnect/
├── admin/                 # Admin panel
├── auth/                  # Authentication (login/register)
├── config/                # Configuration files
├── dashboard/             # Volunteer dashboard
├── db/
│   ├── volunteerconnect_complete.sql  # Complete database (recommended)
│   └── volunteer.sql                   # Basic database only
├── events/                # Event management
├── img/                   # Images and assets
├── includes/              # Shared PHP functions
├── index.php             # Landing page
├── interest_handler.php  # Two-way interest system handler
├── leaderboard.php       # Volunteer leaderboard
├── manage_events.php     # Organization event management
├── my_applications.php   # Volunteer application tracker
├── my_interests.php      # Volunteer interest tracker
├── post_opportunity.php  # Create new opportunities
├── profile.php           # Volunteer profile
├── public_browse.php     # Public opportunity browser
└── volunteer_preferences.php # Volunteer preference settings
```

## Key Features Documentation

- **[Matching System](./MATCHING_SYSTEM_SETUP.md)**: How the smart matching works
- **[Two-Way Interest](./TWO_WAY_INTEREST_IMPLEMENTATION.md)**: Mutual matching system
- **[Future Improvements](./MATCHING_SYSTEM_IMPROVEMENTS.md)**: Planned enhancements

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is open source and available under the MIT License.

## Contact

For questions or support, please open an issue on GitHub.
