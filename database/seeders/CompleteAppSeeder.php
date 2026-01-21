<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\Lead;
use App\Models\Engagement;
use App\Models\AutomationRule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompleteAppSeeder extends Seeder
{
    /**
     * Run the complete application seeder.
     */
    public function run(): void
    {
        // Create roles and users
        $this->seedRolesAndUsers();
        
        // Create social accounts
        $this->seedSocialAccounts();
        
        // Create posts (some scheduled, some published)
        $this->seedPosts();
        
        // Create leads from posts
        $this->seedLeads();
        
        // Create engagements
        $this->seedEngagements();
        
        // Create automation rules
        $this->seedAutomationRules();
        
        $this->command->info('Complete application seeder finished successfully!');
    }

    /**
     * Seed roles and users.
     */
    private function seedRolesAndUsers(): void
    {
        // Create Admin role
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'Full access to all system features',
                'permissions' => [
                    'manage_users',
                    'manage_social_accounts',
                    'manage_posts',
                    'manage_leads',
                    'manage_automation',
                    'view_analytics',
                    'manage_settings'
                ]
            ]
        );

        // Create Tenant role
        $tenantRole = Role::updateOrCreate(
            ['slug' => 'tenant'],
            [
                'name' => 'Tenant',
                'description' => 'Limited access for managing own social accounts and content',
                'permissions' => [
                    'manage_own_social_accounts',
                    'create_posts',
                    'view_own_leads',
                    'manage_own_automation',
                    'view_own_analytics'
                ]
            ]
        );

        // Create Admin User
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@socialdesk.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'timezone' => 'UTC',
            ]
        );
        $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);

        // Create Demo Users
        $users = [
            [
                'name' => 'John Smith',
                'email' => 'john@example.com',
                'password' => Hash::make('password'),
                'timezone' => 'America/New_York',
                'role' => 'tenant'
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah@example.com',
                'password' => Hash::make('password'),
                'timezone' => 'Europe/London',
                'role' => 'tenant'
            ],
            [
                'name' => 'Mike Wilson',
                'email' => 'mike@example.com',
                'password' => Hash::make('password'),
                'timezone' => 'America/Los_Angeles',
                'role' => 'tenant'
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'timezone' => $userData['timezone'],
                    'email_verified_at' => now(),
                ]
            );
            
            $role = Role::where('slug', $userData['role'])->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }

    /**
     * Seed social accounts for users.
     */
    private function seedSocialAccounts(): void
    {
        $users = User::all();
        
        foreach ($users as $user) {
            if ($user->email === 'admin@socialdesk.com') {
                // Admin gets multiple social accounts
                $this->createSocialAccount($user, 'facebook', 'SocialDesk Official', 'socialdesk_official', [
                    'picture' => 'https://picsum.photos/seed/socialdesk/200/200.jpg',
                    'followers' => 15420,
                    'verified' => true,
                ]);

                $this->createSocialAccount($user, 'instagram', '@socialdesk', 'socialdesk_insta', [
                    'picture' => 'https://picsum.photos/seed/socialdesk/200/200.jpg',
                    'followers' => 28950,
                    'verified' => true,
                ]);

                $this->createSocialAccount($user, 'twitter', 'SocialDesk', 'socialdesk_twitter', [
                    'picture' => 'https://picsum.photos/seed/socialdesk/200/200.jpg',
                    'followers' => 12300,
                    'verified' => true,
                ]);

                $this->createSocialAccount($user, 'linkedin', 'SocialDesk Inc', 'socialdesk_linkedin', [
                    'picture' => 'https://picsum.photos/seed/socialdesk/200/200.jpg',
                    'followers' => 8750,
                    'verified' => true,
                ]);
            } elseif ($user->email === 'john@example.com') {
                // John gets Facebook and Instagram
                $this->createSocialAccount($user, 'facebook', 'John Smith', 'john_smith_fb', [
                    'picture' => 'https://picsum.photos/seed/john/200/200.jpg',
                    'followers' => 850,
                    'verified' => false,
                ]);

                $this->createSocialAccount($user, 'instagram', '@johnsmith', 'john_smith_ig', [
                    'picture' => 'https://picsum.photos/seed/john/200/200.jpg',
                    'followers' => 1250,
                    'verified' => false,
                ]);
            } elseif ($user->email === 'sarah@example.com') {
                // Sarah gets Instagram and LinkedIn
                $this->createSocialAccount($user, 'instagram', '@sarahj_creates', 'sarah_j_ig', [
                    'picture' => 'https://picsum.photos/seed/sarah/200/200.jpg',
                    'followers' => 3400,
                    'verified' => false,
                ]);

                $this->createSocialAccount($user, 'linkedin', 'Sarah Johnson', 'sarah_j_linkedin', [
                    'picture' => 'https://picsum.photos/seed/sarah/200/200.jpg',
                    'followers' => 2100,
                    'verified' => false,
                ]);
            } elseif ($user->email === 'mike@example.com') {
                // Mike gets Twitter and Facebook
                $this->createSocialAccount($user, 'twitter', 'Mike Wilson', 'mike_w_twitter', [
                    'picture' => 'https://picsum.photos/seed/mike/200/200.jpg',
                    'followers' => 650,
                    'verified' => false,
                ]);

                $this->createSocialAccount($user, 'facebook', 'Mike Wilson', 'mike_w_fb', [
                    'picture' => 'https://picsum.photos/seed/mike/200/200.jpg',
                    'followers' => 420,
                    'verified' => false,
                ]);
            }
        }
    }
    

    /**
     * Create a social account for a user.
     */
    private function createSocialAccount(User $user, string $platform, string $accountName, string $accountId, array $profileData): SocialAccount
    {
        return SocialAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'account_id' => $accountId,
            ],
            [
                'account_name' => $accountName,
                'access_token' => 'fake_token_' . Str::random(20),
                'refresh_token' => 'fake_refresh_' . Str::random(20),
                'token_expires_at' => now()->addDays(60),
                'profile_data' => $profileData,
                'is_active' => true,
                'last_sync_at' => now()->subHours(rand(1, 24)),
            ]
        );
    }

    /**
     * Seed posts with various statuses and schedules.
     */
    private function seedPosts(): void
    {
        $socialAccounts = SocialAccount::all();
        
        foreach ($socialAccounts as $socialAccount) {
            $postCount = rand(5, 15);
            
            for ($i = 0; $i < $postCount; $i++) {
                $status = rand(0, 10) > 2 ? 'published' : 'scheduled';
                $scheduledAt = $status === 'scheduled' ? 
                    now()->addHours(rand(1, 72)) : 
                    now()->subDays(rand(1, 30));
                
                $publishedAt = $status === 'published' ? 
                    $scheduledAt : 
                    null;

                Post::create([
                    'user_id' => $socialAccount->user_id,
                    'social_account_id' => $socialAccount->id,
                    'title' => $this->generatePostTitle($socialAccount->platform),
                    'content' => $this->generatePostContent($socialAccount->platform),
                    'media_files' => rand(0, 10) > 7 ? $this->generateMediaFiles() : null,
                    'status' => $status,
                    'scheduled_at' => $scheduledAt,
                    'published_at' => $publishedAt,
                    'platform_specific_data' => $this->generatePlatformData($socialAccount->platform),
                    'likes_count' => $status === 'published' ? rand(0, 500) : 0,
                    'comments_count' => $status === 'published' ? rand(0, 50) : 0,
                    'shares_count' => $status === 'published' ? rand(0, 25) : 0,
                ]);
            }
        }
    }

    /**
     * Generate post title based on platform.
     */
    private function generatePostTitle(string $platform): string
    {
        $titles = [
            'facebook' => [
                'Exciting News from Our Team! 🎉',
                'Behind the Scenes Look',
                'Customer Success Story',
                'Industry Insights & Trends',
                'Product Update Announcement',
            ],
            'instagram' => [
                '✨ New Collection Drop!',
                'Daily Inspiration 💫',
                'Behind the Scenes',
                'Team Spotlight',
                'Weekend Vibes 🌟',
            ],
            'twitter' => [
                'Quick update: Big things coming! 🚀',
                'Industry thought of the day...',
                'Just shipped! 🎯',
                'Pro tip for developers',
                'Community shoutout! 🙌',
            ],
            'linkedin' => [
                'Professional Development Insights',
                'Leadership in Digital Age',
                'Industry Analysis & Trends',
                'Career Growth Strategies',
                'Business Innovation Update',
            ],
        ];

        return $titles[$platform][array_rand($titles[$platform])] ?? 'Exciting Update!';
    }

    /**
     * Generate post content based on platform.
     */
    private function generatePostContent(string $platform): string
    {
        $contents = [
            'facebook' => [
                "We're thrilled to share our latest milestone with our amazing community! 🎉 Your support has been incredible, and we couldn't have done it without you. Here's to many more achievements together! #Community #Success #Gratitude",
                
                "Behind every great product is an even greater team. Today we want to shine a spotlight on the talented individuals who make magic happen daily. Join us in celebrating their dedication! #TeamWork #CompanyCulture #BehindTheScenes",
                
                "Customer success stories fuel our passion! 🌟 Just received this amazing feedback from one of our clients and it made our day. Thank you for trusting us with your journey! #CustomerLove #Testimonial #SuccessStory",
            ],
            'instagram' => [
                "✨ New beginnings, endless possibilities! Sometimes the smallest steps lead to the biggest changes. What's one small goal you're working towards today? 🌱 #GrowthMindset #DailyInspiration #SmallStepsBigDreams",
                
                "Coffee in hand, ideas in mind ☕✨ There's something magical about early mornings and fresh perspectives. What time of day do you feel most creative? #MorningVibes #CreativeProcess #Productivity",
                
                "Weekend mode: ACTIVATED! 🌟 Time to recharge, explore, and make memories. Remember, rest isn't lazy - it's necessary for growth. Happy weekend everyone! #WeekendVibes #SelfCare #WorkLifeBalance",
            ],
            'twitter' => [
                "Just shipped a major update! 🚀 Performance improvements, new features, and bug fixes. Check it out and let us know what you think! #ProductLaunch #Update #Tech",
                
                "Hot take: The best code is no code. The second best is simple code. Complexity is the enemy of maintainability. #Programming #SoftwareDevelopment #BestPractices",
                
                "Community spotlight! 🙌 Shoutout to everyone who contributed to our open source project this week. Your contributions make us better every single day. #OpenSource #Community #DevLife",
            ],
            'linkedin' => [
                "In today's rapidly evolving digital landscape, adaptability isn't just a skill—it's a survival mechanism. Organizations that embrace change and foster learning cultures are the ones that thrive. #Leadership #DigitalTransformation #BusinessStrategy",
                
                "The most valuable asset in any organization isn't technology or processes—it's people. Investing in team development creates compound returns that exceed any other investment. #HumanResources #Leadership #OrganizationalCulture",
                
                "Data-driven decision making has moved from competitive advantage to business necessity. Organizations that master analytics and act on insights will lead their industries. #DataAnalytics #BusinessIntelligence #DigitalStrategy",
            ],
        ];

        return $contents[$platform][array_rand($contents[$platform])] ?? 'Exciting content coming soon!';
    }

    /**
     * Generate media files array.
     */
    private function generateMediaFiles(): array
    {
        return [
            'https://picsum.photos/seed/post' . rand(1, 1000) . '/800/600.jpg',
            'https://picsum.photos/seed/post' . rand(1, 1000) . '/800/600.jpg',
        ];
    }

    /**
     * Generate platform-specific data.
     */
    private function generatePlatformData(string $platform): array
    {
        $data = [
            'facebook' => [
                'post_type' => 'status_update',
                'privacy' => 'public',
                'targeting' => ['age_range' => '18-65', 'interests' => ['technology', 'business']],
            ],
            'instagram' => [
                'media_type' => 'carousel',
                'filter' => 'normal',
                'hashtags' => ['#business', '#technology', '#innovation'],
            ],
            'twitter' => [
                'tweet_type' => 'original',
                'hashtags' => ['#tech', '#startup', '#innovation'],
                'mentions' => [],
            ],
            'linkedin' => [
                'post_type' => 'article',
                'industry' => 'Technology',
                'professional_categories' => ['Software Development', 'Business Strategy'],
            ],
        ];

        return $data[$platform] ?? [];
    }

    /**
     * Seed leads from posts.
     */
    private function seedLeads(): void
    {
        $posts = Post::where('status', 'published')->get();
        
        foreach ($posts as $post) {
            // Generate 0-3 leads per post
            $leadCount = rand(0, 3);
            
            for ($i = 0; $i < $leadCount; $i++) {
                $firstName = $this->generateFirstName();
                $lastName = $this->generateLastName();
                $email = strtolower($firstName) . '.' . strtolower($lastName) . '@example.com';
                
                Lead::create([
                    'user_id' => $post->user_id,
                    'social_account_id' => $post->social_account_id,
                    'post_id' => $post->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => rand(0, 10) > 3 ? $email : null,
                    'phone' => rand(0, 10) > 5 ? '+1' . rand(2000000000, 9999999999) : null,
                    'platform' => $post->socialAccount->platform,
                    'platform_lead_id' => 'lead_' . Str::random(10),
                    'source_type' => 'post_comment',
                    'message' => $this->generateLeadMessage($post->socialAccount->platform),
                    'platform_data' => [
                        'comment_id' => 'comment_' . Str::random(10),
                        'thread_id' => 'thread_' . Str::random(10),
                        'user_profile' => [
                            'avatar' => 'https://picsum.photos/seed/lead' . rand(1, 1000) . '/100/100.jpg',
                            'followers' => rand(10, 5000),
                        ],
                    ],
                    'status' => rand(0, 10) > 7 ? 'contacted' : 'new',
                    'contacted_at' => rand(0, 10) > 7 ? now()->subHours(rand(1, 48)) : null,
                    'converted_at' => null,
                    'custom_fields' => [
                        'source' => 'social_media',
                        'interest_level' => rand(1, 5),
                        'budget_range' => '$' . (rand(1, 10) * 1000) . ' - $' . (rand(11, 50) * 1000),
                    ],
                    'score' => rand(20, 95),
                    'qualification' => ['cold', 'warm', 'hot'][array_rand(['cold', 'warm', 'hot'])],
                    'followup_count' => rand(0, 3),
                    'automation_history' => [],
                ]);
            }
        }
    }

    /**
     * Generate random first name.
     */
    private function generateFirstName(): string
    {
        $names = ['James', 'Mary', 'Robert', 'Patricia', 'John', 'Jennifer', 'Michael', 'Linda', 'David', 'Elizabeth', 'William', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica', 'Thomas', 'Sarah', 'Charles', 'Karen', 'Christopher', 'Nancy'];
        return $names[array_rand($names)];
    }

    /**
     * Generate random last name.
     */
    private function generateLastName(): string
    {
        $names = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee'];
        return $names[array_rand($names)];
    }

    /**
     * Generate lead message based on platform.
     */
    private function generateLeadMessage(string $platform): string
    {
        $messages = [
            'facebook' => [
                "This looks amazing! I'm very interested in learning more. Could you send me more information?",
                "Hi! I've been following your page for a while and love what you're doing. How can I get started?",
                "Great content! Do you offer any special deals for new customers?",
            ],
            'instagram' => [
                "Wow! This is exactly what I've been looking for! 🔥",
                "Amazing work! Can you DM me with details?",
                "Love this! How do I sign up? 🙏",
            ],
            'twitter' => [
                "This is brilliant! Need to connect with your team ASAP.",
                "Mind blowing stuff! When can we schedule a call?",
                "Game changer! Where do I invest?",
            ],
            'linkedin' => [
                "Excellent insights! I'd like to discuss potential collaboration opportunities.",
                "This aligns perfectly with our current initiatives. Would love to explore partnership.",
                "Impressive work! I'm interested in learning more about your services for our organization.",
            ],
        ];

        return $messages[$platform][array_rand($messages[$platform])] ?? "I'm interested in learning more!";
    }

    /**
     * Seed engagements for posts and leads.
     */
    private function seedEngagements(): void
    {
        $posts = Post::where('status', 'published')->get();
        
        foreach ($posts as $post) {
            // Generate 5-25 engagements per post
            $engagementCount = rand(5, 25);
            
            for ($i = 0; $i < $engagementCount; $i++) {
                $engagementTypes = ['like', 'comment', 'share', 'mention'];
                $type = $engagementTypes[array_rand($engagementTypes)];
                
                Engagement::create([
                    'user_id' => $post->user_id,
                    'social_account_id' => $post->social_account_id,
                    'post_id' => $post->id,
                    'engagement_type' => $type,
                    'platform' => $post->socialAccount->platform,
                    'platform_engagement_id' => 'eng_' . Str::random(10),
                    'actor_username' => '@' . strtolower($this->generateFirstName()) . '_' . rand(100, 999),
                    'actor_full_name' => $this->generateFirstName() . ' ' . $this->generateLastName(),
                    'actor_platform_id' => 'user_' . Str::random(10),
                    'content' => $type === 'comment' ? $this->generateEngagementContent() : null,
                    'engagement_score' => rand(1, 10),
                    'platform_data' => [
                        'timestamp' => now()->subMinutes(rand(1, 1440))->toISOString(),
                        'device' => ['mobile', 'desktop', 'tablet'][array_rand(['mobile', 'desktop', 'tablet'])],
                    ],
                    'is_automated_response' => rand(0, 10) > 8,
                    'engaged_at' => now()->subMinutes(rand(1, 1440)),
                    'is_spam' => false,
                    'spam_reason' => null,
                    'is_duplicate' => false,
                    'duplicate_of' => null,
                    'content_hash' => md5(Str::random(20)),
                    'processed_at' => rand(0, 10) > 3 ? now() : null,
                ]);
            }
        }
    }

    /**
     * Generate engagement content.
     */
    private function generateEngagementContent(): string
    {
        $contents = [
            "This is fantastic! Keep up the great work! 👏",
            "Interesting perspective. Thanks for sharing! 🤔",
            "Completely agree with this approach! 💯",
            "This really helped me understand the concept better. Thank you!",
            "Amazing insights! Looking forward to more content like this.",
            "First time seeing this approach. Very innovative! 🚀",
            "This deserves more recognition. Sharing with my network! 📤",
            "Perfect timing for this message. Really needed to hear this today.",
            "The quality of your content keeps getting better! 🔥",
            "This changed my perspective on things. Mind = blown! 🤯",
        ];

        return $contents[array_rand($contents)];
    }

    /**
     * Seed automation rules for users.
     */
    private function seedAutomationRules(): void
    {
        $users = User::all();
        
        foreach ($users as $user) {
            $socialAccounts = $user->socialAccounts;
            
            foreach ($socialAccounts as $socialAccount) {
                // Create 1-3 automation rules per social account
                $ruleCount = rand(1, 3);
                
                for ($i = 0; $i < $ruleCount; $i++) {
                    $triggerTypes = ['new_follower', 'mention', 'comment', 'direct_message', 'lead_generation'];
                    $actionTypes = ['auto_reply', 'send_notification', 'create_lead', 'assign_tag'];
                    
                    $triggerType = $triggerTypes[array_rand($triggerTypes)];
                    $actionType = $actionTypes[array_rand($actionTypes)];
                    
                    AutomationRule::create([
                        'user_id' => $user->id,
                        'social_account_id' => $socialAccount->id,
                        'name' => $this->generateAutomationRuleName($triggerType, $actionType),
                        'trigger_type' => $triggerType,
                        'trigger_conditions' => $this->generateTriggerConditions($triggerType),
                        'action_type' => $actionType,
                        'action_parameters' => $this->generateActionParameters($actionType),
                        'is_active' => rand(0, 10) > 2,
                        'execution_count' => rand(0, 100),
                        'last_executed_at' => rand(0, 10) > 5 ? now()->subHours(rand(1, 24)) : null,
                        'platform_filter' => $socialAccount->platform,
                    ]);
                }
            }
        }
    }

    /**
     * Generate automation rule name.
     */
    private function generateAutomationRuleName(string $triggerType, string $actionType): string
    {
        $names = [
            'new_follower' => [
                'auto_reply' => 'Auto-reply to new followers',
                'send_notification' => 'Notify on new followers',
                'create_lead' => 'Create lead from new followers',
                'assign_tag' => 'Tag new followers',
            ],
            'mention' => [
                'auto_reply' => 'Auto-reply to mentions',
                'send_notification' => 'Notify on mentions',
                'create_lead' => 'Create lead from mentions',
                'assign_tag' => 'Tag mentions',
            ],
            'comment' => [
                'auto_reply' => 'Auto-reply to comments',
                'send_notification' => 'Notify on comments',
                'create_lead' => 'Create lead from comments',
                'assign_tag' => 'Tag comments',
            ],
            'direct_message' => [
                'auto_reply' => 'Auto-reply to DMs',
                'send_notification' => 'Notify on DMs',
                'create_lead' => 'Create lead from DMs',
                'assign_tag' => 'Tag DMs',
            ],
            'lead_generation' => [
                'auto_reply' => 'Auto-qualify new leads',
                'send_notification' => 'Notify on new leads',
                'create_lead' => 'Process new leads',
                'assign_tag' => 'Tag new leads',
            ],
        ];

        return $names[$triggerType][$actionType] ?? 'General Automation Rule';
    }

    /**
     * Generate trigger conditions.
     */
    private function generateTriggerConditions(string $triggerType): array
    {
        $conditions = [
            'new_follower' => [
                'min_followers' => rand(10, 1000),
                'exclude_verified' => rand(0, 10) > 5,
                'keywords' => ['business', 'professional', 'entrepreneur'],
            ],
            'mention' => [
                'min_follower_count' => rand(100, 10000),
                'exclude_spam' => true,
                'keywords' => ['help', 'question', 'interested'],
            ],
            'comment' => [
                'min_length' => rand(5, 50),
                'exclude_keywords' => ['spam', 'fake', 'bot'],
                'require_mention' => rand(0, 10) > 5,
            ],
            'direct_message' => [
                'min_length' => rand(10, 100),
                'exclude_keywords' => ['unsubscribe', 'stop'],
                'business_hours_only' => rand(0, 10) > 5,
            ],
            'lead_generation' => [
                'min_score' => rand(20, 80),
                'qualification' => ['hot', 'warm', 'cold'][array_rand(['hot', 'warm', 'cold'])],
                'source_types' => ['post_comment', 'direct_message', 'mention'],
            ],
        ];

        return $conditions[$triggerType] ?? [];
    }

    /**
     * Generate action parameters.
     */
    private function generateActionParameters(string $actionType): array
    {
        $parameters = [
            'auto_reply' => [
                'message' => $this->generateAutoReplyMessage(),
                'delay_minutes' => rand(5, 60),
                'include_emoji' => rand(0, 10) > 3,
            ],
            'send_notification' => [
                'channels' => ['email', 'push', 'sms'],
                'message_template' => 'New {trigger_type} detected: {details}',
                'urgent_only' => rand(0, 10) > 7,
            ],
            'create_lead' => [
                'source_tag' => 'automation',
                'priority' => ['high', 'medium', 'low'][array_rand(['high', 'medium', 'low'])],
                'assign_to' => 'current_user',
            ],
            'assign_tag' => [
                'tags' => ['hot_lead', 'vip_customer', 'priority_response'],
                'overwrite_existing' => false,
                'auto_categorize' => true,
            ],
        ];

        return $parameters[$actionType] ?? [];
    }

    /**
     * Generate auto-reply message.
     */
    private function generateAutoReplyMessage(): string
    {
        $messages = [
            "Thank you for reaching out! We've received your message and will get back to you within 24 hours. 🙏",
            "Hi there! Thanks for your interest. Our team is reviewing your message and will respond shortly! ✨",
            "Great to hear from you! We've logged your request and someone from our team will be in touch soon! 🚀",
            "Appreciate you reaching out! Your message is important to us. Expect a response within one business day! 💼",
            "Thanks for connecting! We're excited to chat with you. Our team will respond as soon as possible! 🤝",
        ];

        return $messages[array_rand($messages)];
    }
}
