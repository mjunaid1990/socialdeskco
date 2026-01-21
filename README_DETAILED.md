# Social Media Automation Platform - Technical Documentation

## Overview

A comprehensive social media automation platform built with Laravel 12 that enables businesses to manage multiple social media accounts, schedule content, automate engagement responses, capture leads, and track performance across various platforms from a single dashboard.

## 🚀 Features

### Core Functionality
- **Multi-Platform Support**: Facebook, Instagram, LinkedIn, Twitter, and WhatsApp Business API
- **User Authentication**: Complete authentication system with role-based access control
- **Multi-Brand/Multi-Client Support**: Each user manages their own accounts and content
- **OAuth Integration**: Secure API connections with token refresh capabilities

### Content Management
- **Post Scheduling**: Schedule posts for future publication across all connected platforms
- **Bulk Upload**: Support for multiple media files per post
- **Cross-Platform Publishing**: Single post can be published across multiple platforms
- **Time-Zone Handling**: Proper scheduling based on user's time zone
- **Performance Metrics**: Track likes, comments, and shares for each post

### Engagement Automation
- **Intelligent Auto-Reply System**: Respond to comments and messages based on keywords
- **Multiple Trigger Types**: Keyword matching, engagement type, time-based, frequency controls
- **Action Framework**: Auto-reply, auto-Direct Message, auto-like, lead tagging, CRM forwarding
- **Spam Protection**: Frequency-based controls to prevent automated spam
- **Human-like Delays**: Configurable delays for natural-looking responses

### Lead Management
- **Lead Capture**: From comments, DMs, and lead forms
- **Contact Information**: Store names, emails, phone numbers, and custom fields
- **Status Tracking**: Manage leads through new, contacted, converted, and lost stages
- **Source Attribution**: Track which engagement or platform generated each lead
- **CRM Integration**: Forward leads to external CRM systems

### Analytics & Monitoring
- **Activity Logging**: Comprehensive logging of all platform interactions
- **Error Tracking**: Detailed error reporting with retry mechanisms
- **Performance Metrics**: Engagement and conversion tracking
- **Audit Trail**: Complete record of automated actions

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 12 (PHP)
- **Database**: MySQL/PostgreSQL with Eloquent ORM
- **Queues**: Redis/RabbitMQ for background job processing
- **HTTP Client**: Laravel HTTP client for API integrations
- **Authentication**: Laravel's built-in authentication with custom middleware

### Frontend
- **Template Engine**: Blade templates
- **CSS Framework**: Tailwind CSS (via Laravel's frontend scaffolding)
- **JavaScript**: Vite for asset building

### Social Media APIs
- **Facebook Graph API**
- **Instagram Graph API**
- **LinkedIn UGC API**
- **Twitter API v2**
- **WhatsApp Business API**

## 📁 Directory Structure

```
app/
├── Console/
│   └── Commands/
│       └── ProcessScheduledPosts.php      # Scheduled post processor
├── Http/
│   ├── Controllers/
│   │   ├── Auth/                         # Authentication controllers
│   │   └── Backend/                      # Main application controllers
│   │       ├── AutomationRuleController.php
│   │       ├── DashboardController.php
│   │       ├── EngagementController.php
│   │       ├── LeadController.php
│   │       ├── PostController.php
│   │       └── SocialAccountController.php
│   └── Middleware/
│       ├── AdminMiddleware.php           # Admin role protection
│       └── TenantMiddleware.php          # Tenant role protection
├── Jobs/
│   ├── ProcessAutomationRuleJob.php      # Process engagement automation
│   └── PublishPostJob.php               # Publish scheduled posts
├── Models/
│   ├── AutomationRule.php               # Automation rule definitions
│   ├── Engagement.php                   # Social media engagement tracking
│   ├── Lead.php                         # Lead management
│   ├── Post.php                         # Content scheduling and management
│   ├── SocialAccount.php                # Connected social media accounts
│   ├── User.php                         # User authentication and roles
│   └── Role.php                         # Role-based access control
├── Services/
│   ├── AutomationService.php            # Automation logic engine
│   └── SocialMediaService.php           # Platform API integrations
└── Providers/
    └── AppServiceProvider.php
```

## 🗄️ Database Schema

### Key Tables

#### `users`
- Standard Laravel users table with additional role relationships
- Foreign key relationships to all major entities

#### `social_accounts`
- Platform identification and credentials
- OAuth tokens with refresh capabilities
- Profile data stored in JSON format
- Active status tracking

#### `posts`
- Complete post content and metadata
- Scheduling with `scheduled_at` and `published_at` timestamps
- Performance metrics (likes, comments, shares)
- Media files array for bulk uploads

#### `engagements`
- Detailed engagement tracking with actor information
- Platform-specific engagement IDs
- Automated response tracking
- Timestamps for analytics

#### `leads`
- Complete contact information (name, email, phone)
- Source tracking for attribution
- Status lifecycle management
- Custom fields for flexibility

#### `automation_rules`
- Flexible trigger and action configuration
- Conditional logic support
- Execution tracking and statistics

#### `roles`
- Multi-tenant role system
- Role-user many-to-many relationships

## 🔌 API Integrations

### Platform-Specific Implementations

#### Facebook/Instagram
- OAuth 2.0 authentication
- Feed posting and comment management
- Page and user token management
- Rate limit handling

#### LinkedIn
- UGC (User Generated Content) API
- Professional network posting
- OAuth 2.0 with refresh tokens

#### Twitter
- API v2 integration
- Tweet posting and DM handling
- OAuth 2.0 with PKCE

#### WhatsApp Business API
- Meta's WhatsApp Business API
- Message sending and receipt tracking
- Template-based messaging

## ⚙️ Automation Engine

### Trigger Types
1. **Keyword Match**: Trigger actions when specific keywords appear in content
2. **Engagement Type**: Different responses for likes, comments, shares, DMs
3. **Time-Based**: Business hours detection and response timing
4. **Engagement Frequency**: Prevent spam by limiting response frequency

### Action Types
1. **Auto-Reply**: Platform-specific comment replies
2. **Auto-DM**: Direct message sending capabilities
3. **Auto-Like**: Automated engagement with content
4. **Lead Tagging**: Automatic lead categorization
5. **CRM Forwarding**: Integration with external CRM systems

### Processing Pipeline
1. New engagement detected
2. Automation rules evaluated against engagement
3. Matching rules' actions executed
4. Actions processed asynchronously via job queue
5. Results logged and tracked

## 🕐 Scheduling & Background Processing

### Scheduled Tasks
- **Post Processing**: Runs every minute to publish scheduled posts
- **Token Refresh**: Automatic OAuth token refresh
- **Cleanup Jobs**: Periodic maintenance tasks

### Job Queue System
- **PublishPostJob**: Handles scheduled post publishing with retry logic
- **ProcessAutomationRuleJob**: Processes engagements through automation rules
- **Retry Mechanisms**: Configurable retry attempts with exponential backoff
- **Error Handling**: Comprehensive error logging and recovery

## 🔐 Authentication & Authorization

### Role System
- **Admin**: Full system access
- **Tenant**: Access limited to owned resources
- **Multi-Tenant**: Each user sees only their own data

### Security Features
- **Input Validation**: All user inputs validated and sanitized
- **CSRF Protection**: Built-in Laravel CSRF protection
- **Rate Limiting**: API rate limiting to prevent abuse
- **Data Encryption**: Sensitive data encrypted at rest

## 🚀 Deployment

### Requirements
- PHP 8.2+
- Laravel 12
- Database (MySQL/PostgreSQL)
- Redis (for queues)
- Queue worker process

### Setup Instructions
1. Install dependencies: `composer install`
2. Set up environment: `cp .env.example .env` and configure
3. Generate app key: `php artisan key:generate`
4. Run migrations: `php artisan migrate`
5. Start queue workers: `php artisan queue:work`
6. Configure scheduler in crontab: `* * * * * cd /path-to-your-project && php artisan schedule:run`

### Environment Configuration
```env
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
INSTAGRAM_CLIENT_ID=
INSTAGRAM_CLIENT_SECRET=
LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=
TWITTER_CLIENT_ID=
TWITTER_CLIENT_SECRET=
WHATSAPP_API_KEY=
```

## 🧪 Testing

The application is designed with testability in mind:
- Service classes are decoupled from controllers
- Dependency injection used throughout
- Repository pattern ready for easy mocking
- Event system for decoupled operations

## 📊 Analytics & Reporting

### Built-in Metrics
- Post performance (engagement rates, reach)
- Automation effectiveness (response rates)
- Lead conversion tracking
- Platform-specific insights

### Logging
- Comprehensive activity logging
- Error tracking and alerting
- Performance monitoring
- Audit trails for compliance

## 🔄 Extensibility

### Adding New Platforms
The architecture makes it easy to add new social media platforms:
1. Add platform-specific API methods to `SocialMediaService`
2. Update the switch statements in relevant methods
3. Add platform-specific token management

### Custom Automation Rules
- New trigger types can be added to `AutomationService`
- Additional action types are supported
- Conditional logic is extensible

## 🛡️ Security Considerations

- OAuth tokens stored securely with encryption
- API rate limiting to prevent abuse
- Input sanitization and validation
- Proper error handling without information disclosure
- Secure session management

## 📋 License

This project is open-source software licensed under the MIT license.