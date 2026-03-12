<?php

namespace App\Http\Controllers;

use App\Models\Announcement;

class AnnouncementController extends Controller
{
    /**
     * 获取最新一条已显示的公告（小程序公开调用）
     * GET /api/announcement/latest
     */
    public function latest()
    {
        $item = Announcement::where('status', 1)
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->first();

        return $this->success($item);
    }
}
