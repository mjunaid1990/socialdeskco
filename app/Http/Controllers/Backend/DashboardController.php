<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\Lead;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'connected_accounts' => SocialAccount::count(),
            'scheduled_posts' => Post::where('status', 'scheduled')->count(),
            'engagements' => $this->getTotalEngagements(),
            'new_leads' => Lead::whereDate('created_at', today())->count(),
        ];

        return view('backend.dashboard.index', compact('stats'));
    }

    private function getTotalEngagements(): int
    {
        // This would typically come from your analytics data
        // For now, we'll count published posts as a proxy
        return Post::where('status', 'published')->count();
    }
}